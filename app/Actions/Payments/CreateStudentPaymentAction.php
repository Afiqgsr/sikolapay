<?php

namespace App\Actions\Payments;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CreateStudentPaymentAction
{
    /**
     * Memproses konfirmasi pembayaran satu tagihan oleh Siswa (bisa berupa pembayaran baru atau pengunggahan ulang bukti yang ditolak).
     *
     * @param  Bill  $bill  Model tagihan yang akan dibayar
     * @param  int  $studentId  ID Siswa pemilik tagihan
     * @param  int  $paymentMethodId  ID metode pembayaran aktif yang dipilih
     * @param  string  $proofPath  Path lokasi penyimpanan file bukti pembayaran
     * @param  int  $payerId  ID User dari user yang sedang login (pembayar)
     * @return array{payment: Payment, is_resubmission: bool}
     */
    public function execute(
        Bill $bill,
        int $studentId,
        int $paymentMethodId,
        string $proofPath,
        int $payerId
    ): array {
        // Jalankan seluruh proses dalam database transaction agar perubahan data aman dan konsisten
        return DB::transaction(function () use ($bill, $studentId, $paymentMethodId, $proofPath, $payerId): array {
            // Cari kembali tagihan berdasarkan ID sekaligus memastikan tagihan tersebut benar-benar milik siswa yang login.
            // lockForUpdate() mengunci baris tagihan sementara selama transaksi berlangsung
            // agar dua request simultan tidak bisa memproses pembayaran untuk tagihan yang sama secara bersamaan.
            // firstOrFail() menghasilkan 404 jika tagihan tidak sesuai dengan kepemilikan siswa.
            $ownedBill = Bill::query()
                ->whereKey($bill->id)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->firstOrFail();

            // Jika tagihan sudah berstatus lunas, tolak pembayaran dengan HTTP 422
            if ($ownedBill->status === 'paid') {
                throw new HttpException(422, 'Tagihan ini sudah lunas.');
            }

            // Cari riwayat pembayaran paling baru untuk tagihan ini beserta data verifikasi terakhirnya
            $existingPayment = Payment::query()
                ->where('bill_id', $ownedBill->id)
                ->with('latestVerification')
                ->latest()
                ->first();

            // Jika sudah ada record pembayaran yang berstatus 'paid', tagihan tidak boleh dibayar lagi
            if (
                $existingPayment
                && $existingPayment->status === 'paid'
            ) {
                throw new HttpException(422, 'Tagihan ini sudah lunas.');
            }

            // Pembayaran dianggap 'rejected' jika status pembayaran masih 'pending'
            // namun status verifikasi audit terakhir oleh admin adalah 'rejected'
            $isRejected = $existingPayment
                && $existingPayment->status === 'pending'
                && $existingPayment->latestVerification?->status === 'rejected';

            // Jika sudah ada pembayaran pending dan statusnya BUKAN rejected (artinya masih menunggu verifikasi admin),
            // siswa tidak boleh mengirim pembayaran baru lagi sampai admin memprosesnya
            if (
                $existingPayment
                && $existingPayment->status === 'pending'
                && ! $isRejected
            ) {
                throw new HttpException(422, 'Pembayaran masih menunggu verifikasi.');
            }

            // Cari metode pembayaran yang dipilih dan pastikan masih berstatus aktif
            $paymentMethod = PaymentMethod::query()
                ->whereKey($paymentMethodId)
                ->where('is_active', true)
                ->firstOrFail();

            // Skenario 1: Jika pembayaran sebelumnya pernah ditolak oleh Admin,
            // gunakan kembali (recycle) record Payment yang lama dan perbarui bukti transfer serta waktunya
            if ($isRejected && $existingPayment) {
                $existingPayment->update([
                    'payment_method_id' => $paymentMethod->id,
                    'proof_of_payment' => $proofPath,
                    'proof_uploaded_at' => now(),
                    'status' => 'pending',
                ]);

                return [
                    'payment' => $existingPayment->fresh(),
                    'is_resubmission' => true,
                ];
            }

            // Skenario 2: Jika belum ada pembayaran atau pembayaran baru,
            // buat record Payment baru dengan status 'pending' menunggu verifikasi Admin
            $payment = Payment::create([
                'bill_id' => $ownedBill->id,
                'payer_id' => $payerId,
                'payment_method_id' => $paymentMethod->id,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $ownedBill->amount,
                'proof_of_payment' => $proofPath,
                'proof_uploaded_at' => now(),
                'status' => 'pending',
            ]);

            return [
                'payment' => $payment,
                'is_resubmission' => false,
            ];
        });
    }

    /**
     * Membuat format nomor pembayaran unik: PAY-YYYYMMDDHHIISS-XXXXX
     * Loop do...while memastikan nomor tidak mengalami tabrakan/duplikasi di database.
     */
    private function generatePaymentNumber(): string
    {
        do {
            $paymentNumber = 'PAY-'
                .now()->format('YmdHis')
                .'-'
                .strtoupper(Str::random(5));
        } while (Payment::where('payment_number', $paymentNumber)->exists());

        return $paymentNumber;
    }
}
