<?php

namespace App\Actions\Payments;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RejectPaymentAction
{
    /**
     * Menolak bukti pembayaran transfer dengan mencatat alasan penolakan,
     * status pembayaran tetap 'pending' agar user dapat mengunggah bukti perbaikan.
     *
     * @param  int|string  $paymentId  ID pembayaran yang ditolak
     * @param  int  $adminId  ID Admin yang memproses penolakan
     * @param  string  $note  Alasan penolakan bukti pembayaran
     */
    public function execute(int|string $paymentId, int $adminId, string $note): Payment
    {
        // Jalankan seluruh proses penolakan dalam satu transaksi database (Database Transaction)
        // untuk memastikan data verifikasi tercatat secara konsisten dan aman dari kegagalan parsial.
        return DB::transaction(function () use ($paymentId, $adminId, $note): Payment {
            // Cari data pembayaran yang akan ditolak:
            // 1. whereKey($paymentId) mencari berdasarkan primary key kolom 'id'.
            // 2. where('status', 'pending') memastikan pembayaran masih berstatus menunggu verifikasi.
            // 3. whereNotNull('proof_of_payment') memastikan pembayaran memang memiliki bukti transfer untuk diperiksa.
            // 4. lockForUpdate() mengunci baris data di level database (Pessimistic Locking)
            //    selama transaksi berlangsung untuk mencegah dua Admin memproses penolakan/verifikasi secara bersamaan.
            // 5. firstOrFail() melempar ModelNotFoundException (HTTP 404) jika pembayaran tidak memenuhi kriteria di atas.
            $payment = Payment::query()
                ->whereKey($paymentId)
                ->where('status', 'pending')
                ->whereNotNull('proof_of_payment')
                ->lockForUpdate()
                ->firstOrFail();

            // Ambil status verifikasi paling baru dari riwayat verifikasi pembayaran ini
            $latestVerificationStatus = $payment
                ->latestVerification()
                ->value('status');

            // Ambil waktu kapan verifikasi terakhir diproses oleh Admin
            $latestProcessedAt = $payment
                ->latestVerification()
                ->value('processed_at');

            // Mengambil nilai asli (raw string) dari kolom proof_uploaded_at di database sebelum mutator/casting Model
            $proofUploadedAt = $payment->getRawOriginal('proof_uploaded_at');

            // Pembayaran dianggap sudah mengirim ulang bukti (resubmitted) jika:
            // 1. Verifikasi terakhir berstatus 'rejected' (pernah ditolak sebelumnya).
            // 2. Tanggal upload bukti ($proofUploadedAt) dan tanggal proses terakhir ($latestProcessedAt) valid.
            // 3. Waktu pengunggahan bukti baru LEBIH BARU (gt = greater than) dibanding waktu penolakan Admin sebelumnya.
            $isResubmitted = $latestVerificationStatus === 'rejected'
                && is_string($proofUploadedAt)
                && is_string($latestProcessedAt)
                && Carbon::parse($proofUploadedAt)->gt(
                    Carbon::parse((string) $latestProcessedAt)
                );

            // Jika bukti pembayaran sebelumnya sudah pernah ditolak ('rejected') dan user BELUM mengunggah bukti baru,
            // cegah Admin menolak pembayaran yang sama berkali-kali untuk bukti lama yang sama (HTTP 422).
            if (
                $latestVerificationStatus === 'rejected'
                && ! $isResubmitted
            ) {
                abort(422, 'Bukti pembayaran ini sudah ditolak.');
            }

            // Buat record audit trail baru di tabel payment_verifications
            // Status pembayaran utama (Payment) tetap dipertahankan 'pending' agar siswa/wali murid dapat mengunggah bukti ulang
            $payment->verifications()->create([
                'admin_id' => $adminId, // ID Admin yang melakukan penolakan
                'status' => 'rejected', // Status verifikasi adalah ditolak
                'note' => $note, // Alasan kenapa bukti pembayaran ditolak
                'verified_at' => null, // Bernilai null karena pembayaran tidak diverifikasi/disetujui
                'processed_at' => now(), // Waktu aksi penolakan dicatat
            ]);

            return $payment;
        });
    }
}
