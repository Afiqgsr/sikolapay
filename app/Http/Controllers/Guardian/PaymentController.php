<?php

namespace App\Http\Controllers\Guardian;

use App\Actions\Payments\CreateGuardianPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\Payments\StoreGuardianPaymentRequest;
use App\Http\Requests\Guardian\Payments\UploadGuardianPaymentProofRequest;
use App\Models\Bill;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Menampilkan formulir pembayaran untuk satu tagihan anak dari Wali Murid.
     */
    public function create(int|string $id): RedirectResponse|View
    {
        // Ambil profil Guardian dari user yang sedang login
        $guardian = $this->currentGuardian();

        // Cari tagihan dan pastikan tagihan milik siswa yang berada di bawah asuhan Guardian ini
        $bill = $this->findGuardianBill($guardian, $id);

        // Jika tagihan sudah berstatus lunas (paid), langsung alihkan ke halaman detail pembayaran
        $paidPayment = $bill->payments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        if ($paidPayment) {
            return redirect()->route('guardian.payments.show', $paidPayment->id);
        }

        // Jika tagihan sudah memiliki pembayaran pending (menunggu verifikasi), alihkan ke detail
        $pendingPayment = $bill->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {
            return redirect()->route('guardian.payments.show', $pendingPayment->id);
        }

        // Ambil daftar metode pembayaran yang aktif
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->get();

        return view('guardian.payments.create', [
            'bill' => $bill,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Memproses pengiriman formulir pembayaran satu tagihan oleh Wali Murid.
     */
    public function store(
        StoreGuardianPaymentRequest $request,
        CreateGuardianPaymentAction $action
    ): RedirectResponse {
        // Ambil profil Guardian dari user yang sedang login
        $guardian = $this->currentGuardian();

        // Ambil data request yang telah divalidasi oleh Form Request
        $validated = $request->validated();

        // Cari tagihan dan pastikan kepemilikannya sesuai
        $bill = $this->findGuardianBill($guardian, $validated['bill_id']);

        // Cegah pembayaran ulang jika tagihan sudah berstatus lunas
        $paidPayment = $bill->payments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        if ($paidPayment) {
            return redirect()
                ->route('guardian.payments.show', $paidPayment->id)
                ->with('error', 'Tagihan ini sudah dibayar.');
        }

        // Cegah pembayaran ganda jika pembayaran sebelumnya masih menunggu verifikasi admin
        $pendingPayment = $bill->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {
            return redirect()
                ->route('guardian.payments.show', $pendingPayment->id)
                ->with('error', 'Pembayaran tagihan ini sedang menunggu verifikasi.');
        }

        // Pastikan metode pembayaran yang dipilih valid dan berstatus aktif
        $paymentMethod = PaymentMethod::query()
            ->where('id', $validated['payment_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        // Simpan file bukti transfer ke storage publik di folder 'payments/proofs'
        $proofPath = $request->file('proof_of_payment')->store('payments/proofs', 'public');

        try {
            // Jalankan action untuk membuat record Payment dalam database transaction
            $payment = $action->execute(
                $bill,
                $paymentMethod,
                (int) Auth::id(),
                $proofPath
            );
        } catch (\Throwable $exception) {
            // Jika proses database gagal, hapus file bukti yang baru saja diunggah agar tidak jadi file sampah
            Storage::disk('public')->delete($proofPath);
            throw $exception;
        }

        return redirect()
            ->route('guardian.payments.show', $payment->id)
            ->with('success', 'Pembayaran berhasil dikirim dan menunggu verifikasi admin.');
    }

    /**
     * Menampilkan detail pembayaran, bukti transfer, dan status verifikasi.
     */
    public function show(int|string $id): View
    {
        // Ambil profil Guardian dari user yang sedang login
        $guardian = $this->currentGuardian();

        // Cari data pembayaran dan pastikan siswa terkait terdaftar di bawah Guardian ini
        $payment = Payment::query()
            ->where('id', $id)
            ->whereHas('bill.student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();

        return view('guardian.payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Mengunggah ulang bukti pembayaran jika pembayaran masih berstatus 'pending'.
     */
    public function uploadProof(
        UploadGuardianPaymentProofRequest $request,
        int|string $id
    ): RedirectResponse {
        // Ambil profil Guardian dari user yang sedang login
        $guardian = $this->currentGuardian();

        // Cari data pembayaran yang berstatus pending milik siswa asuhan Guardian ini
        $payment = Payment::query()
            ->where('id', $id)
            ->whereHas('bill.student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->where('status', 'pending')
            ->firstOrFail();

        // Simpan path bukti lama untuk dihapus setelah update database berhasil
        $oldProof = $payment->proof_of_payment;

        // Simpan file bukti baru ke storage publik
        $newProofPath = $request->file('proof_of_payment')->store('payments/proofs', 'public');

        try {
            // Perbarui record payment dengan path bukti pembayaran yang baru
            $payment->update([
                'proof_of_payment' => $newProofPath,
                'proof_uploaded_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Jika update database gagal, hapus file baru yang baru saja tersimpan
            Storage::disk('public')->delete($newProofPath);

            throw $exception;
        }

        // Jika update database sukses dan file lama ada, hapus file lama dari storage
        if ($oldProof && $oldProof !== $newProofPath) {
            Storage::disk('public')->delete($oldProof);
        }

        return redirect()
            ->route('guardian.payments.show', $payment->id)
            ->with('success', 'Bukti pembayaran berhasil diperbarui dan menunggu verifikasi admin.');
    }

    /**
     * Menampilkan nota / kuitansi resmi untuk pembayaran yang sudah berstatus lunas ('paid').
     */
    public function receipt(int|string $id): View
    {
        // Ambil profil Guardian dari user yang sedang login
        $guardian = $this->currentGuardian();

        // Cari pembayaran lunas (paid) yang terhubung dengan anak asuhan Guardian ini
        $payment = Payment::query()
            ->where('id', $id)
            ->where('status', 'paid')
            ->whereHas('bill.student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();

        return view('guardian.payments.receipt', [
            'payment' => $payment,
        ]);
    }

    /**
     * Helper untuk mengambil profil Guardian dari user login.
     * Jika user tidak memiliki profil Guardian, hentikan request dengan HTTP 404.
     */
    private function currentGuardian(): Guardian
    {
        /** @var ?Guardian $guardian */
        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);

        return $guardian;
    }

    /**
     * Helper untuk mencari Bill berdasarkan ID dan memastikan tagihan tersebut milik siswa di bawah Guardian ini.
     */
    private function findGuardianBill(Guardian $guardian, int|string $billId): Bill
    {
        return Bill::query()
            ->where('id', $billId)
            ->whereHas('student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->with('student.classRoom')
            ->firstOrFail();
    }
}
