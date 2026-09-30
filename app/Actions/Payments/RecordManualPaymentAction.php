<?php

namespace App\Actions\Payments;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RecordManualPaymentAction
{
    /**
     * Mencatat pembayaran tagihan secara manual (misal: pembayaran tunai di tata usaha sekolah)
     * dan langsung menandai tagihan serta pembayaran sebagai lunas ('paid').
     *
     * @param  array<string, mixed>  $validated  Data input yang sudah lolos validasi Form Request
     * @param  int  $adminId  ID Admin yang mencatat transaksi dari sesi autentikasi
     */
    public function execute(array $validated, int $adminId): Payment
    {
        // Jalankan seluruh proses dalam satu transaksi database (Database Transaction).
        // Jika salah satu tahapan gagal atau terjadi error di tengah jalan, seluruh perubahan
        // pada tabel bills, payments, dan payment_verifications akan dibatalkan secara otomatis (rollback).
        return DB::transaction(function () use ($validated, $adminId): Payment {
            // Cari tagihan berdasarkan ID dan pastikan tagihan tersebut benar-benar milik siswa yang dipilih.
            // lockForUpdate() digunakan untuk mengunci baris tagihan sementara (Pessimistic Locking)
            // agar request lain tidak bisa memproses tagihan yang sama secara bersamaan (mencegah race condition).
            // firstOrFail() akan menghasilkan HTTP 404 jika kombinasi tagihan dan siswa tidak ditemukan.
            $bill = Bill::query()
                ->whereKey($validated['bill_id'])
                ->where('student_id', $validated['student_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Jika tagihan ternyata sudah berstatus lunas, batalkan proses dengan status HTTP 422
            if ($bill->status === 'paid') {
                abort(422, 'Tagihan ini sudah lunas.');
            }

            // Periksa apakah tagihan masih memiliki riwayat pembayaran transfer yang berstatus 'pending'
            $hasPendingPayment = Payment::query()
                ->where('bill_id', $bill->id)
                ->where('status', 'pending')
                ->exists();

            // Jika masih ada pembayaran pending, Admin tidak boleh mencatat pembayaran manual baru
            // untuk menghindari pembayaran ganda atas tagihan yang sama
            if ($hasPendingPayment) {
                abort(
                    422,
                    'Tagihan ini masih memiliki pembayaran yang menunggu verifikasi.'
                );
            }

            if (! $bill->hasBillingPeriodStarted()) {
                abort(422, 'Tagihan periode ini belum dapat dibayar.');
            }

            $billingPeriod = $bill->getAttribute('billing_period');
            $paidAt = Carbon::parse(
                (string) $validated['paid_at'],
                (string) config('app.timezone')
            );

            if (! $billingPeriod instanceof CarbonInterface || $paidAt->lessThan($billingPeriod)) {
                abort(422, 'Tanggal pembayaran tidak boleh lebih awal dari periode tagihan.');
            }

            // Cari metode pembayaran yang dipilih dan pastikan metode tersebut masih dalam kondisi aktif
            $paymentMethod = PaymentMethod::query()
                ->whereKey($validated['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();

            // Buat record pembayaran baru di tabel 'payments'
            $payment = Payment::create([
                // ID tagihan yang dibayarkan
                'bill_id' => $bill->id,

                // ID User akun Siswa yang memiliki tagihan (bukan ID Admin)
                'payer_id' => $bill->student()->value('user_id'),

                // ID Admin yang mencatat transaksi pembayaran manual ini
                'recorded_by' => $adminId,

                // ID metode pembayaran (misal: Tunai / Kasir Sekolah)
                'payment_method_id' => $paymentMethod->id,

                // Generate nomor referensi pembayaran unik secara otomatis
                'payment_number' => $this->generatePaymentNumber(),

                // Nominal pembayaran diambil langsung dari nominal tagihan resmi di database (bukan input bebas)
                'amount' => $bill->amount,

                // Field gateway bernilai null karena bukan pembayaran melalui payment gateway online
                'gateway_transaction_id' => null,
                'gateway_reference' => null,
                'gateway_status' => null,
                'payment_url' => null,

                // Bukti pembayaran bernilai null karena pembayaran tunai/manual diterima langsung oleh Admin
                'proof_of_payment' => null,

                // Status langsung 'paid' (lunas) karena uang sudah diterima oleh Admin
                'status' => 'paid',

                // Tanggal dan waktu pembayaran yang diinputkan oleh Admin
                'paid_at' => $validated['paid_at'],

                // Waktu upload bukti bernilai null karena tidak ada file yang diunggah
                'proof_uploaded_at' => null,
            ]);

            // Perbarui status tagihan menjadi 'paid' (lunas)
            $bill->update([
                'status' => 'paid',
            ]);

            // Buat catatan audit trail di tabel payment_verifications
            // yang merekam bahwa pembayaran ini diverifikasi dan dicatat langsung oleh Admin
            $payment->verifications()->create([
                'admin_id' => $adminId,
                'status' => 'verified',
                'note' => 'Pembayaran dicatat langsung oleh admin.',
                'verified_at' => now(),
                'processed_at' => now(),
            ]);

            return $payment;
        });
    }

    /**
     * Membuat nomor pembayaran unik dengan format: PAY-YYYYMMDD-XXXXXX
     * Menggunakan perulangan do...while untuk memastikan nomor yang digenerate belum pernah digunakan di database.
     */
    private function generatePaymentNumber(): string
    {
        do {
            $paymentNumber = 'PAY-'
                .now()->format('Ymd')
                .'-'
                .strtoupper(substr(uniqid(), -6));
        } while (Payment::where('payment_number', $paymentNumber)->exists());

        return $paymentNumber;
    }
}
