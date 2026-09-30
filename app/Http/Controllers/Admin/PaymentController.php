<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\RecordManualPaymentAction;
use App\Actions\Payments\RejectPaymentAction;
use App\Actions\Payments\VerifyPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payments\RejectPaymentRequest;
use App\Http\Requests\Admin\Payments\StoreManualPaymentRequest;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Menampilkan daftar antrean pembayaran yang siap diperiksa dan diverifikasi oleh Admin.
     */
    public function index(): View
    {
        // Ambil data pembayaran yang berstatus pending dan benar-benar siap diperiksa oleh Admin
        $payments = $this->getPendingVerifiablePayments();

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Menampilkan formulir untuk mencatat pembayaran manual (misal: pembayaran tunai di sekolah).
     */
    public function create(): View
    {
        // Ambil seluruh data siswa yang berstatus aktif beserta informasi kelasnya untuk dropdown siswa
        $students = Student::query()
            ->where('status', 'active')
            ->with('classRoom')
            ->orderBy('name')
            ->get();

        // Ambil tagihan yang berstatus belum lunas ('unpaid') dan tidak sedang memiliki pembayaran 'pending'
        // Hal ini penting agar Admin tidak mencatat pembayaran manual untuk tagihan yang sedang menunggu verifikasi transfer
        $bills = Bill::query()
            ->where('status', 'unpaid')
            ->whereDoesntHave('latestPayment', function (Builder $query) {
                $query->where('status', 'pending');
            })
            ->with('student.classRoom')
            ->orderBy('due_date')
            ->get();

        // Ambil daftar metode pembayaran yang aktif diurutkan berdasarkan nama
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.payments.create', compact(
            'students',
            'bills',
            'paymentMethods'
        ));
    }

    /**
     * Menyimpan data pencatatan pembayaran manual ke database.
     */
    public function storeManual(
        StoreManualPaymentRequest $request,
        RecordManualPaymentAction $action
    ): RedirectResponse {
        // Jalankan action pencatatan pembayaran manual dengan data tervalidasi dan ID Admin yang login
        $payment = $action->execute(
            $request->validated(),
            $this->authenticatedAdminId()
        );

        return redirect()
            ->route('admin.payments.show', $payment->id)
            ->with(
                'success',
                'Pembayaran manual berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan halaman detail pembayaran, bukti transfer, dan riwayat verifikasi.
     */
    public function show(int|string $id): View
    {
        // Cari data pembayaran berdasarkan ID dan lakukan eager loading (with)
        // Eager loading memuat relasi sekaligus di awal (tagihan, siswa, kelas, metode pembayaran,
        // user pembayar, admin pencatat, dan riwayat verifikasi) agar data langsung siap
        // digunakan pada tampilan Blade tanpa query berulang (mencegah N+1 query problem).
        $payment = Payment::with([
            'bill.student.classRoom',
            'paymentMethod',
            'payer',
            'recordedBy',
            'verifications.admin',
            'latestVerification.admin',
        ])
            ->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Memverifikasi pembayaran transfer yang diajukan oleh Siswa / Wali Murid.
     */
    public function verify(
        int|string $id,
        VerifyPaymentAction $action
    ): RedirectResponse {
        // Jalankan action verifikasi pembayaran dengan meneruskan ID pembayaran dan ID Admin yang login
        $payment = $action->execute($id, $this->authenticatedAdminId());

        return redirect()
            ->route('admin.payments.show', $payment->id)
            ->with(
                'success',
                'Pembayaran berhasil diverifikasi.'
            );
    }

    /**
     * Menolak bukti pembayaran transfer yang tidak valid dengan menyertakan alasan penolakan.
     */
    public function reject(
        RejectPaymentRequest $request,
        RejectPaymentAction $action,
        int|string $id
    ): RedirectResponse {
        // Jalankan action penolakan pembayaran dengan membawa ID pembayaran, ID Admin, dan catatan alasan penolakan
        $payment = $action->execute(
            $id,
            $this->authenticatedAdminId(),
            $request->validated('note')
        );

        return redirect()
            ->route('admin.payments.show', $payment->id)
            ->with(
                'success',
                'Bukti pembayaran berhasil ditolak.'
            );
    }

    /**
     * Helper untuk mengambil data pembayaran berstatus 'pending' yang siap diverifikasi oleh Admin.
     *
     * Kriteria antrean verifikasi:
     * 1. Status pembayaran harus 'pending'.
     * 2. Pembayaran wajib memiliki file bukti transfer (proof_of_payment tidak null).
     * 3. Pembayaran yang belum pernah ditolak (rejected) boleh langsung tampil.
     * 4. Jika pembayaran pernah ditolak sebelumnya, pembayaran hanya boleh tampil kembali jika
     *    user telah mengunggah bukti baru (proof_uploaded_at > processed_at verifikasi penolakan terakhir).
     * 5. Eager loading relasi yang dibutuhkan oleh tampilan Blade index.
     * 6. Diurutkan dari transaksi pembayaran yang paling baru (latest()).
     *
     * @return Collection<int, Payment>
     */
    private function getPendingVerifiablePayments(): Collection
    {
        return Payment::query()
            ->where('status', 'pending')
            ->whereNotNull('proof_of_payment')
            ->where(function ($query) {
                $query
                    ->whereDoesntHave('verifications', function ($verification) {
                        $verification->where('status', 'rejected');
                    })
                    ->orWhereHas('latestVerification', function ($verification) {
                        $verification
                            ->where('status', 'rejected')
                            ->whereColumn(
                                'payments.proof_uploaded_at',
                                '>',
                                'payment_verifications.processed_at'
                            );
                    });
            })
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'payer',
                'latestVerification',
            ])
            ->latest()
            ->get();
    }

    /**
     * Helper untuk mengambil ID user Admin yang sedang login secara aman dari session server.
     *
     * Penjelasan keamanan:
     * - Memastikan user sudah terautentikasi melalui Auth::check(). Jika belum, request dihentikan dengan HTTP 401.
     * - Auth::id() diambil langsung dari sesi autentikasi Laravel yang terenkripsi, bukan dari input form bebas user.
     * - ID ini menjadi identitas resmi Admin yang memproses verifikasi, penolakan, atau pencatatan manual.
     * - Casting (int) memastikan tipe data yang dikembalikan selalu berupa integer murni.
     */
    private function authenticatedAdminId(): int
    {
        abort_unless(Auth::check(), 401);

        return (int) Auth::id();
    }
}
