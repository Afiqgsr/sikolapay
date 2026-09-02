<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::query()
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

        return view('admin.payments.index', compact('payments'));
    }

    public function show($id)
    {
        $payment = Payment::with([
                'bill.student.classRoom',
                'paymentMethod',
                'payer',
                'latestVerification',
            ])
            ->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }

    public function verify($id)
    {
        $payment = Payment::with('bill')
            ->where('status', 'pending')
            ->whereNotNull('proof_of_payment')
            ->findOrFail($id);

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $payment->bill->update([
                'status' => 'paid',
            ]);

            PaymentVerification::create([
                'payment_id' => $payment->id,
                'admin_id' => Auth::id(),
                'status' => 'verified',
                'note' => null,
                'verified_at' => now(),
                'processed_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.payments.show', $payment->id)
            ->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $payment = Payment::where('status', 'pending')
            ->whereNotNull('proof_of_payment')
            ->findOrFail($id);

        DB::transaction(function () use ($payment, $validated) {
            PaymentVerification::create([
                'payment_id' => $payment->id,
                'admin_id' => Auth::id(),
                'status' => 'rejected',
                'note' => $validated['note'],
                'verified_at' => null,
                'processed_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.payments.show', $payment->id)
            ->with('success', 'Bukti pembayaran berhasil ditolak.');
    }
}