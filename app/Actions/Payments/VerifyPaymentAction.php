<?php

namespace App\Actions\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class VerifyPaymentAction
{
    /**
     * Memverifikasi pembayaran transfer yang diajukan oleh Siswa / Wali Murid,
     * mengubah status pembayaran dan tagihan menjadi 'paid', serta mencatat audit verifikasi.
     *
     * @param  int|string  $paymentId  ID pembayaran yang akan diverifikasi
     * @param  int  $adminId  ID Admin yang melakukan verifikasi dari sesi login
     */
    public function execute(int|string $paymentId, int $adminId): Payment
    {
        // Jalankan seluruh proses dalam satu database transaction.
        // Jika salah satu update gagal, semua perubahan status akan di-rollback agar database tetap konsisten.
        return DB::transaction(function () use ($paymentId, $adminId): Payment {
            // Cari data pembayaran yang memenuhi kriteria:
            // 1. Sesuai dengan ID pembayaran ($paymentId).
            // 2. Status saat ini harus 'pending' (menunggu verifikasi).
            // 3. Wajib memiliki file bukti transfer (proof_of_payment tidak null).
            // 4. Eager load relasi 'bill' untuk memperbarui status tagihan sekaligus.
            // 5. lockForUpdate() mengunci baris data di level database selama transaksi berjalan
            //    untuk mencegah race condition (misal dua admin menekan tombol verifikasi secara bersamaan).
            // 6. firstOrFail() melempar ModelNotFoundException (HTTP 404) jika data tidak ditemukan / tidak sesuai syarat.
            $payment = Payment::query()
                ->whereKey($paymentId)
                ->where('status', 'pending')
                ->whereNotNull('proof_of_payment')
                ->with('bill')
                ->lockForUpdate()
                ->firstOrFail();

            // Perbarui status pembayaran menjadi 'paid' (lunas) dan catat waktu pelunasan (paid_at)
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Perbarui status tagihan terkait menjadi 'paid' (lunas)
            $payment->bill->update([
                'status' => 'paid',
            ]);

            // Buat catatan audit trail di tabel payment_verifications
            // yang mencatat bahwa pembayaran ini disetujui (status: verified) oleh Admin terkait
            $payment->verifications()->create([
                'admin_id' => $adminId,
                'status' => 'verified',
                'note' => null, // Note bernilai null karena verifikasi disetujui tanpa catatan khusus
                'verified_at' => now(), // Waktu persetujuan verifikasi
                'processed_at' => now(), // Waktu aksi Admin diproses
            ]);

            return $payment;
        });
    }
}
