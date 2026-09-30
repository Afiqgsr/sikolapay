<?php

namespace App\Actions\Payments;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CreateGuardianPaymentAction
{
    /**
     * Membuat record pembayaran tagihan anak oleh Wali Murid (Guardian) dan mencatat bukti transfernya.
     *
     * @param  Bill  $bill  Tagihan anak yang sudah diverifikasi kepemilikannya oleh Controller
     * @param  PaymentMethod  $paymentMethod  Metode pembayaran aktif yang sudah divalidasi
     * @param  int  $guardianId  ID Guardian pemilik relasi siswa pada tagihan
     * @param  int  $payerId  ID User milik akun Guardian yang melakukan pembayaran
     * @param  string  $proofPath  Path lokasi penyimpanan file bukti transfer di storage
     */
    public function execute(
        Bill $bill,
        PaymentMethod $paymentMethod,
        int $guardianId,
        int $payerId,
        string $proofPath
    ): Payment {
        // Jalankan pembuatan data pembayaran di dalam transaksi database
        // untuk menjamin proses penyimpanan data berlangsung aman dan konsisten
        return DB::transaction(function () use ($bill, $paymentMethod, $guardianId, $payerId, $proofPath): Payment {
            $ownedBill = Bill::query()
                ->whereKey($bill->getKey())
                ->whereHas('student', function (Builder $query) use ($guardianId) {
                    $query->where('guardian_id', $guardianId);
                })
                ->lockForUpdate()
                ->firstOrFail();

            if ($ownedBill->status === 'paid' || $ownedBill->payments()->where('status', 'paid')->exists()) {
                throw new HttpException(422, 'Tagihan ini sudah dibayar.');
            }

            if ($ownedBill->payments()->where('status', 'pending')->exists()) {
                throw new HttpException(422, 'Pembayaran tagihan ini sedang menunggu verifikasi.');
            }

            if (! $ownedBill->hasBillingPeriodStarted()) {
                throw new HttpException(422, 'Tagihan periode ini belum dapat dibayar.');
            }

            $activePaymentMethod = PaymentMethod::query()
                ->whereKey($paymentMethod->getKey())
                ->where('is_active', true)
                ->firstOrFail();

            // Buat record pembayaran baru di tabel 'payments'
            return Payment::create([
                // ID tagihan anak yang dibayarkan
                'bill_id' => $ownedBill->id,

                // ID User dari Wali Murid yang login dan melakukan pembayaran
                'payer_id' => $payerId,

                // ID metode pembayaran yang dipilih
                'payment_method_id' => $activePaymentMethod->id,

                // Nomor referensi pembayaran unik yang digenerate otomatis
                'payment_number' => $this->generatePaymentNumber(),

                // Nominal pembayaran mengikuti nominal resmi tagihan di database
                'amount' => $ownedBill->amount,

                // Path file bukti transfer yang telah disimpan di folder storage
                'proof_of_payment' => $proofPath,

                // Waktu pengunggahan bukti pembayaran dicatat saat ini
                'proof_uploaded_at' => now(),

                // Status awal adalah 'pending' karena menunggu proses verifikasi oleh Admin
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Membuat format nomor pembayaran unik: PAY-YYYYMMDDHHIISS-XXXX
     * Menggunakan do...while loop untuk memastikan nomor transaksi tidak duplikat di database.
     */
    private function generatePaymentNumber(): string
    {
        do {
            $paymentNumber = 'PAY-'
                .now()->format('YmdHis')
                .'-'
                .strtoupper(Str::random(4));
        } while (Payment::where('payment_number', $paymentNumber)->exists());

        return $paymentNumber;
    }
}
