<?php

namespace App\Actions\Payments;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStudentBatchPaymentsAction
{
    /**
     * Memproses pembayaran serentak untuk seluruh tagihan siswa yang belum lunas (Batch Payment).
     *
     * @param  int  $studentId  ID Siswa yang tagihannya akan dibayarkan
     * @param  int  $paymentMethodId  ID metode pembayaran aktif yang dipilih
     * @param  string  $proofPath  Path file bukti transfer yang digunakan bersama untuk semua tagihan
     * @param  int  $payerId  ID User pembayar (user yang sedang login)
     * @return array{batch_number: string, payments: Collection<int, Payment>}
     */
    public function execute(
        int $studentId,
        int $paymentMethodId,
        string $proofPath,
        int $payerId
    ): array {
        // Jalankan seluruh pembuatan record pembayaran batch dalam satu transaksi database
        return DB::transaction(function () use ($studentId, $paymentMethodId, $proofPath, $payerId): array {
            // Pastikan data siswa valid dan terdaftar di sistem
            $student = Student::query()
                ->whereKey($studentId)
                ->firstOrFail();

            // Ambil seluruh tagihan milik siswa yang memenuhi kriteria:
            // 1. Tagihan berstatus belum lunas ('unpaid').
            // 2. Tidak memiliki Payment berstatus 'pending' atau 'paid'.
            //    Payment yang bukti terakhirnya ditolak Admin tetap memiliki status 'pending',
            //    sehingga tagihan tersebut tidak masuk pembayaran batch.
            //    Upload ulang bukti yang ditolak ditangani melalui alur pembayaran satu tagihan.
            // 3. Diurutkan berdasarkan tanggal jatuh tempo terdekat (due_date).
            // 4. lockForUpdate() mengunci seluruh baris tagihan yang dipilih selama transaksi agar tidak ada konflik data.
            $bills = Bill::query()
                ->where('student_id', $student->id)
                ->where('status', 'unpaid')
                ->whereDoesntHave('payments', function ($query) {
                    $query->whereIn('status', ['pending', 'paid']);
                })
                ->orderBy('due_date')
                ->lockForUpdate()
                ->get();

            // Jika tidak ada tagihan yang eligible untuk dibayar, batalkan proses dengan HTTP 422
            if ($bills->isEmpty()) {
                abort(422, 'Tidak ada tagihan yang dapat dibayar.');
            }

            // Pastikan metode pembayaran yang dipilih terdaftar dan dalam status aktif
            $paymentMethod = PaymentMethod::query()
                ->whereKey($paymentMethodId)
                ->where('is_active', true)
                ->firstOrFail();

            // Generate satu kode batch utama untuk mengelompokkan seluruh pembayaran dalam satu sesi transfer
            $batchNumber = 'PAY-ALL-'
                .now()->format('YmdHis')
                .'-'.strtoupper(Str::random(5));

            // Siapkan collection Eloquent untuk menampung record Payment yang berhasil dibuat
            $payments = new Collection;

            // Iterasi setiap tagihan dan buatkan masing-masing 1 record Payment di database
            foreach ($bills as $bill) {
                $payments->push(Payment::create([
                    'bill_id' => $bill->id,
                    'payer_id' => $payerId,
                    'payment_method_id' => $paymentMethod->id,
                    // Format nomor pembayaran per tagihan: KODE-BATCH-IDTAGIHAN (misal: PAY-ALL-20260928-ABCDE-001)
                    'payment_number' => $batchNumber.'-'.str_pad((string) $bill->id, 3, '0', STR_PAD_LEFT),
                    'amount' => $bill->amount,
                    'proof_of_payment' => $proofPath, // Seluruh pembayaran dalam batch menggunakan 1 bukti file yang sama
                    'proof_uploaded_at' => now(),
                    'status' => 'pending', // Status awal 'pending' menunggu verifikasi Admin
                ]));
            }

            return [
                'batch_number' => $batchNumber,
                'payments' => $payments,
            ];
        });
    }
}
