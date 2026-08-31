<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Halaman pembayaran satu tagihan
     */
    public function create($id)
    {
        $student = Auth::user()->student;

        $bill = Bill::where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        // Ambil semua metode pembayaran yang aktif
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('student.payment', [
            'student'        => $student,
            'bill'           => $bill,
            'paymentMethods' => $paymentMethods,
        ]);
    }


    /**
     * Konfirmasi pembayaran satu tagihan
     */
    public function confirm(Request $request, $id)
    {
        $student = Auth::user()->student;

        $bill = Bill::where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        // Validasi input pembayaran
        $request->validate([
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],

            'proof_of_payment' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ]);

        // Ambil payment method yang aktif
        $paymentMethod = PaymentMethod::where(
            'id',
            $request->payment_method_id
        )
            ->where('is_active', true)
            ->firstOrFail();

        // Cegah pembayaran ganda
        $existingPayment = Payment::where('bill_id', $bill->id)
            ->whereIn('status', ['pending', 'paid'])
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan ini sudah memiliki pembayaran.'
            ], 422);
        }

        // Generate nomor pembayaran
        $paymentNumber =
            'PAY-' .
            now()->format('YmdHis') .
            '-' .
            strtoupper(Str::random(5));

        // Simpan bukti pembayaran
        $proofPath = $request->file('proof_of_payment')
            ->store('payment-proofs', 'public');

        // Simpan pembayaran
        $payment = Payment::create([
            'bill_id'            => $bill->id,
            'payer_id'           => Auth::id(),
            'payment_method_id'  => $paymentMethod->id,
            'payment_number'     => $paymentNumber,
            'amount'             => $bill->amount,
            'proof_of_payment'   => $proofPath,
            'proof_uploaded_at'  => now(),
            'status'             => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dikonfirmasi.',
            'payment' => [
                'id'             => $payment->id,
                'payment_number' => $payment->payment_number,
                'date'           => $payment->created_at
                    ->translatedFormat('d F Y'),
                'proof'          => $payment->proof_of_payment,
            ],
        ]);
    }

        /**
     * Halaman Bayar Semua Tagihan
     */
    public function all()
    {
        $student = Auth::user()->student;

        $unpaidBills = Bill::where('student_id', $student->id)
            ->where('status', 'unpaid')
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'paid']);
            })
            ->get();

        if ($unpaidBills->isEmpty()) {
            return redirect()
                ->route('student.bills.index')
                ->with(
                    'error',
                    'Tidak ada tagihan yang dapat dibayar.'
                );
        }

        $total = $unpaidBills->sum('amount');

        return view('student.payment-all', [
            'student'     => $student,
            'unpaidBills' => $unpaidBills,
            'total'       => $total,
        ]);
    }

        /**
     * Halaman konfirmasi pembayaran semua tagihan
     */
    public function allConfirm()
    {
        $student = Auth::user()->student;

        $unpaidBills = Bill::where('student_id', $student->id)
            ->where('status', 'unpaid')
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'paid']);
            })
            ->orderBy('due_date')
            ->get();

        if ($unpaidBills->isEmpty()) {
            return redirect()
                ->route('student.bills.index')
                ->with(
                    'error',
                    'Tidak ada tagihan yang dapat dibayar.'
                );
        }

        $total = $unpaidBills->sum('amount');

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('student.payment-all-confirm', [
            'student'        => $student,
            'unpaidBills'    => $unpaidBills,
            'total'           => $total,
            'paymentMethods' => $paymentMethods,
        ]);
    }

        /**
     * Proses pembayaran semua tagihan
     */
    public function confirmAll(Request $request)
    {
        $student = Auth::user()->student;

        $request->validate([
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],

            'proof_of_payment' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ]);

        $paymentMethod = PaymentMethod::where(
            'id',
            $request->payment_method_id
        )
            ->where('is_active', true)
            ->firstOrFail();

        $unpaidBills = Bill::where('student_id', $student->id)
            ->where('status', 'unpaid')
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'paid']);
            })
            ->get();

        if ($unpaidBills->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tagihan yang dapat dibayar.'
            ], 422);
        }

        $proofPath = $request->file('proof_of_payment')
            ->store('payment-proofs', 'public');

        $batchNumber =
            'PAY-ALL-' .
            now()->format('YmdHis') .
            '-' .
            strtoupper(Str::random(5));

        $payments = [];

        foreach ($unpaidBills as $bill) {

            $paymentNumber =
                $batchNumber .
                '-' .
                str_pad($bill->id, 3, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'bill_id'            => $bill->id,
                'payer_id'           => Auth::id(),
                'payment_method_id'  => $paymentMethod->id,
                'payment_number'     => $paymentNumber,
                'amount'             => $bill->amount,
                'proof_of_payment'   => $proofPath,
                'proof_uploaded_at'  => now(),
                'status'             => 'pending',
            ]);

            $payments[] = $payment;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran semua tagihan berhasil dikonfirmasi.',
            'payment' => [
                'payment_number' => $batchNumber,
                'count'          => count($payments),
                'total'          => $unpaidBills->sum('amount'),
                'date'           => now()->translatedFormat('d F Y'),
            ],
        ]);
    }

}