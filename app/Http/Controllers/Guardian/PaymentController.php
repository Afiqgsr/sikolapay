<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function create($id)
    {
        $guardian = Auth::user()->guardian;

        $bill = Bill::where('id', $id)
            ->whereHas('student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->with('student')
            ->firstOrFail();

        // Cek apakah sudah ada pembayaran yang berhasil
        $paidPayment = $bill->payments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        if ($paidPayment) {
            return redirect()
                ->route('guardian.payments.show', $paidPayment->id);
        }

        // Cek apakah ada pembayaran yang masih pending
        $pendingPayment = $bill->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {
            return redirect()
                ->route('guardian.payments.show', $pendingPayment->id);
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('guardian.payments.create', compact(
            'bill',
            'paymentMethods'
        ));
    }
    public function store(Request $request)
    {
        $request->validate([
            'bill_id' => ['required', 'exists:bills,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ]);

        $guardian = Auth::user()->guardian;

        $bill = Bill::where('id', $request->bill_id)
            ->whereHas('student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->firstOrFail();
        
        $paidPayment = $bill->payments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        if ($paidPayment) {
            return redirect()
                ->route('guardian.payments.show', $paidPayment->id)
                ->with('error', 'Tagihan ini sudah dibayar.');
        }

        $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)
            ->where('is_active', true)
            ->firstOrFail();

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'payer_id' => Auth::id(),
            'payment_method_id' => $paymentMethod->id,
            'payment_number' => 'PAY-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
            'amount' => $bill->amount,
            'status' => 'pending',
        ]);        

        return redirect()
            ->route('guardian.payments.show', $payment->id);

    }

    public function show($id)
    {
        $guardian = Auth::user()->guardian;

        $payment = Payment::where('id', $id)
            ->whereHas('bill.student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->with([
                'bill.student',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();

        return view('guardian.payments.show', compact('payment'));
    }

    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'proof_of_payment' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ]);

        $guardian = Auth::user()->guardian;

        $payment = Payment::where('id', $id)
            ->whereHas('bill.student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->where('status', 'pending')
            ->firstOrFail();

        $path = $request->file('proof_of_payment')
            ->store('payments/proofs', 'public');

        $payment->update([
            'proof_of_payment' => $path,
            'proof_uploaded_at' => now(),
        ]);

        return redirect()
            ->route('guardian.payments.show', $payment->id)
            ->with('success', 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi admin.');
    }

}