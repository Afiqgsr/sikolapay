<?php

namespace App\Http\Controllers\Student;

use App\Actions\Payments\CreateStudentBatchPaymentsAction;
use App\Actions\Payments\CreateStudentPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Payments\StoreStudentBatchPaymentRequest;
use App\Http\Requests\Student\Payments\StoreStudentPaymentRequest;
use App\Models\Bill;
use App\Models\PaymentMethod;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman formulir pembayaran untuk satu tagihan siswa.
     */
    public function create(int|string $id): View
    {
        // Ambil data siswa yang sedang login
        $student = $this->currentStudent();

        // Cari tagihan berdasarkan ID dan pastikan tagihan memang milik siswa yang login
        $bill = $this->findStudentBill($student, $id);

        // Ambil daftar metode pembayaran yang aktif untuk dipilih oleh siswa
        $paymentMethods = $this->getActivePaymentMethods();

        return view('student.payment', [
            'student' => $student,
            'bill' => $bill,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Memproses konfirmasi pembayaran untuk satu tagihan (AJAX / JSON).
     */
    public function confirm(
        StoreStudentPaymentRequest $request,
        int|string $id,
        CreateStudentPaymentAction $action
    ): JsonResponse {
        // Ambil data siswa yang sedang login
        $student = $this->currentStudent();

        // Cari tagihan dan pastikan tagihan tersebut milik siswa yang login
        $bill = $this->findStudentBill($student, $id);

        // Ambil data input yang sudah tervalidasi dari Form Request
        $validated = $request->validated();

        // Simpan file bukti transfer ke storage publik di folder 'payment-proofs'
        $proofPath = $this->storeProofOfPayment($request->file('proof_of_payment'));

        if ($proofPath === null) {
            return $this->proofUploadFailedResponse();
        }

        try {
            // Eksekusi action untuk membuat record pembayaran atau update bukti (jika resubmission)
            $result = $action->execute(
                $bill,
                $student->id,
                $validated['payment_method_id'],
                $proofPath,
                (int) Auth::id()
            );
        } catch (HttpException $exception) {
            // Jika terjadi kegagalan validasi business logic di Action, hapus file bukti yang baru diupload
            Storage::disk('public')->delete($proofPath);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        } catch (\Throwable $exception) {
            // Jika terjadi error sistem / database yang tidak terduga, bersihkan file lalu lemparkan exception
            Storage::disk('public')->delete($proofPath);

            throw $exception;
        }

        $payment = $result['payment'];
        $isResubmission = $result['is_resubmission'];

        // Kembalikan response JSON sukses untuk ditampilkan pada modal frontend siswa
        return response()->json([
            'success' => true,
            'message' => $isResubmission
                ? 'Bukti pembayaran berhasil dikirim ulang.'
                : 'Pembayaran berhasil dikonfirmasi.',
            'payment' => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'date' => now()->translatedFormat('d F Y'),
                'proof' => $payment->proof_of_payment,
            ],
        ]);
    }

    /**
     * Menampilkan halaman rekap awal untuk pembayaran semua tagihan sekaligus.
     */
    public function all(): RedirectResponse|View
    {
        // Ambil data siswa yang sedang login
        $student = $this->currentStudent();

        // Ambil seluruh tagihan siswa yang statusnya 'unpaid' dan belum memiliki pembayaran 'pending' atau 'paid'
        $unpaidBills = $this->getEligibleUnpaidBills($student);

        // Jika tidak ada tagihan yang bisa dibayar, kembalikan siswa ke halaman daftar tagihan dengan pesan error
        if ($unpaidBills->isEmpty()) {
            return redirect()
                ->route('student.bills.index')
                ->with('error', 'Tidak ada tagihan yang dapat dibayar.');
        }

        // Hitung total nominal rupiah dari semua tagihan yang akan dibayar
        $total = $unpaidBills->sum('amount');

        return view('student.payment-all', [
            'student' => $student,
            'unpaidBills' => $unpaidBills,
            'total' => $total,
        ]);
    }

    /**
     * Menampilkan halaman konfirmasi dan pemilihan metode pembayaran untuk semua tagihan.
     */
    public function allConfirm(): RedirectResponse|View
    {
        // Ambil data siswa yang sedang login
        $student = $this->currentStudent();

        // Ambil seluruh tagihan yang layak dibayar, diurutkan berdasarkan tanggal jatuh tempo
        $unpaidBills = $this->getEligibleUnpaidBills($student, orderedByDueDate: true);

        // Jika tidak ada tagihan yang bisa dibayar, kembalikan ke daftar tagihan
        if ($unpaidBills->isEmpty()) {
            return redirect()
                ->route('student.bills.index')
                ->with('error', 'Tidak ada tagihan yang dapat dibayar.');
        }

        // Hitung total nominal rupiah dari semua tagihan
        $total = $unpaidBills->sum('amount');

        // Ambil daftar metode pembayaran aktif
        $paymentMethods = $this->getActivePaymentMethods();

        return view('student.payment-all-confirm', [
            'student' => $student,
            'unpaidBills' => $unpaidBills,
            'total' => $total,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Memproses pembayaran serentak untuk seluruh tagihan eligible (AJAX / JSON).
     */
    public function confirmAll(
        StoreStudentBatchPaymentRequest $request,
        CreateStudentBatchPaymentsAction $action
    ): JsonResponse {
        // Ambil data siswa yang sedang login
        $student = $this->currentStudent();

        // Ambil data input yang sudah tervalidasi dari Form Request
        $validated = $request->validated();

        // Simpan file bukti transfer bersama ke storage publik di folder 'payment-proofs'
        $proofPath = $this->storeProofOfPayment($request->file('proof_of_payment'));

        if ($proofPath === null) {
            return $this->proofUploadFailedResponse();
        }

        try {
            // Eksekusi action untuk membuat record pembayaran batch di dalam database transaction
            $result = $action->execute(
                $student->id,
                $validated['payment_method_id'],
                $proofPath,
                (int) Auth::id()
            );
        } catch (HttpException $exception) {
            // Jika validasi bisnis gagal di Action, bersihkan file bukti pembayaran
            Storage::disk('public')->delete($proofPath);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        } catch (\Throwable $exception) {
            // Jika terjadi error sistem / database, bersihkan file bukti lalu lemparkan exception
            Storage::disk('public')->delete($proofPath);

            throw $exception;
        }

        $batchNumber = $result['batch_number'];
        $payments = $result['payments'];

        // Kembalikan response JSON sukses berisi informasi ringkasan batch pembayaran
        return response()->json([
            'success' => true,
            'message' => 'Pembayaran semua tagihan berhasil dikonfirmasi.',
            'payment' => [
                'payment_number' => $batchNumber,
                'count' => count($payments),
                'total' => $payments->sum(fn ($payment) => $payment->amount),
                'date' => now()->translatedFormat('d F Y'),
            ],
        ]);
    }

    private function storeProofOfPayment(mixed $proofOfPayment): ?string
    {
        if (! $proofOfPayment instanceof UploadedFile) {
            return null;
        }

        $proofPath = $proofOfPayment->store('payment-proofs', 'public');

        return $proofPath === false ? null : $proofPath;
    }

    private function proofUploadFailedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Bukti pembayaran gagal diunggah. Silakan coba lagi.',
        ], 500);
    }

    /**
     * Helper untuk mengambil data profil Siswa dari user yang sedang login.
     * Jika user bukan siswa atau tidak memiliki relasi siswa, hentikan request dengan HTTP 404.
     */
    private function currentStudent(): Student
    {
        /** @var ?Student $student */
        $student = Auth::user()?->student;

        abort_unless($student, 404);

        return $student;
    }

    /**
     * Helper untuk mencari tagihan berdasarkan ID dan memastikan tagihan tersebut milik siswa yang login.
     */
    private function findStudentBill(Student $student, int|string $id): Bill
    {
        return Bill::query()
            ->whereKey($id)
            ->where('student_id', $student->id)
            ->firstOrFail();
    }

    /**
     * Helper untuk mengambil seluruh tagihan siswa yang statusnya 'unpaid'
     * dan tidak memiliki riwayat pembayaran berstatus 'pending' (sedang diverifikasi) atau 'paid' (lunas).
     */
    private function getEligibleUnpaidBills(Student $student, bool $orderedByDueDate = false): Collection
    {
        $query = Bill::query()
            ->where('student_id', $student->id)
            ->where('status', 'unpaid')
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'paid']);
            });

        if ($orderedByDueDate) {
            $query->orderBy('due_date');
        }

        return $query->get();
    }

    /**
     * Helper untuk mengambil daftar metode pembayaran yang aktif dan mengurutkannya berdasarkan tipe dan nama.
     */
    private function getActivePaymentMethods(): Collection
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }
}
