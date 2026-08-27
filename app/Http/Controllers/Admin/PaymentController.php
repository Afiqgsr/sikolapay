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
    $payments = Payment::where('status', 'pending')
    ->whereNotNull('proof_of_payment')
    ->where(function ($query) {
    // blum pernah ditolak
    $query->whereDoesntHave('verifications', function ($verification) {
    $verification->where('status', 'rejected');
    })

            // atau sudah ditolak, akan tetapi sudah upload bukti baru
            ->orWhereHas('latestVerification', function ($verification) {
                $verification->where('status', 'rejected')
                    ->whereColumn(
                        'payments.proof_uploaded_at',
                        '>',
                        'payment_verifications.processed_at'
                    );
            });
        })
        ->with([
            'bill.student',
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
        $payment = Payment::where('id', $id)
            ->with([
                'bill.student',
                'paymentMethod',
                'payer',
                'latestVerification',
            ])
            ->firstOrFail();

        return view('admin.payments.show', compact('payment'));
    }

    public function verify($id)
    {
        $payment = Payment::where('id', $id)
            ->where('status', 'pending')
            ->whereNotNull('proof_of_payment')
            ->firstOrFail();

        DB::transaction(function () use ($payment) {

            // Ubah status payment menjadi paid
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Ubah status tagihan menjadi paid
            $payment->bill->update([
                'status' => 'paid',
            ]);

            // Simpan riwayat verifikasi
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
    $request->validate([
    'note' => ['required', 'string', 'max:1000'],
    ]);

    $payment = Payment::where('id', $id)
        ->where('status', 'pending')
        ->whereNotNull('proof_of_payment')
        ->firstOrFail();

    DB::transaction(function () use ($payment, $request) {

        PaymentVerification::create([
            'payment_id' => $payment->id,
            'admin_id' => Auth::id(),
            'status' => 'rejected',
            'note' => $request->note,
            'verified_at' => null,
            'processed_at' => now(),
        ]);
    });

    return redirect()
        ->route('admin.payments.show', $payment->id)
        ->with('success', 'Bukti pembayaran berhasil ditolak.');


    }


}