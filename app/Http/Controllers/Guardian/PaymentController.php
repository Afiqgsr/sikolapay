<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function create($id)
    {
        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);


        $bill = Bill::query()
            ->where('id', $id)
            ->whereHas('student', function ($query) use ($guardian) {
                $query->where(
                    'guardian_id',
                    $guardian->id
                );
            })
            ->with([
                'student.classRoom',
            ])
            ->firstOrFail();


        /* CEK SUDAH LUNAS */

        $paidPayment = $bill->payments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        if ($paidPayment) {

            return redirect()
                ->route(
                    'guardian.payments.show',
                    $paidPayment->id
                );
        }


        /* CEK MASIH PENDING */

        $pendingPayment = $bill->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {

            return redirect()
                ->route(
                    'guardian.payments.show',
                    $pendingPayment->id
                );
        }


        /* METODE PEMBAYARAN */

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->get();


        return view(
            'guardian.payments.create',
            [
                'bill' => $bill,
                'paymentMethods' => $paymentMethods,
            ]
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => [
                'required',
                'exists:bills,id',
            ],

            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],

            'proof_of_payment' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ]);


        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);


        /* CARI TAGIHAN */

        $bill = Bill::query()
            ->where('id', $validated['bill_id'])
            ->whereHas('student', function ($query) use ($guardian) {
                $query->where(
                    'guardian_id',
                    $guardian->id
                );
            })
            ->firstOrFail();


        /* CEK SUDAH LUNAS */

        $paidPayment = $bill->payments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        if ($paidPayment) {

            return redirect()
                ->route(
                    'guardian.payments.show',
                    $paidPayment->id
                )
                ->with(
                    'error',
                    'Tagihan ini sudah dibayar.'
                );
        }


        /* CEK PEMBAYARAN PENDING */

        $pendingPayment = $bill->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPayment) {

            return redirect()
                ->route(
                    'guardian.payments.show',
                    $pendingPayment->id
                )
                ->with(
                    'error',
                    'Pembayaran tagihan ini sedang menunggu verifikasi.'
                );
        }


        /* PAYMENT METHOD */

        $paymentMethod = PaymentMethod::query()
            ->where(
                'id',
                $validated['payment_method_id']
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();


        /* UPLOAD BUKTI */

        $proofPath = $request
            ->file('proof_of_payment')
            ->store(
                'payments/proofs',
                'public'
            );


        try {

            /* BUAT PAYMENT */

            $payment = Payment::create([
                'bill_id' => $bill->id,

                'payer_id' => Auth::id(),

                'payment_method_id' =>
                    $paymentMethod->id,

                'payment_number' =>
                    'PAY-'
                    . now()->format('YmdHis')
                    . '-'
                    . Str::upper(
                        Str::random(4)
                    ),

                'amount' => $bill->amount,

                'proof_of_payment' =>
                    $proofPath,

                'proof_uploaded_at' =>
                    now(),

                'status' =>
                    'pending',
            ]);

        } catch (\Throwable $exception) {

            Storage::disk('public')
                ->delete($proofPath);

            throw $exception;
        }


        return redirect()
            ->route(
                'guardian.payments.show',
                $payment->id
            )
            ->with(
                'success',
                'Pembayaran berhasil dikirim dan menunggu verifikasi admin.'
            );
    }


    public function show($id)
    {
        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);


        $payment = Payment::query()
            ->where('id', $id)
            ->whereHas(
                'bill.student',
                function ($query) use ($guardian) {

                    $query->where(
                        'guardian_id',
                        $guardian->id
                    );
                }
            )
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();


        return view(
            'guardian.payments.show',
            [
                'payment' => $payment,
            ]
        );
    }


    public function uploadProof(
        Request $request,
        $id
    ) {
        $request->validate([
            'proof_of_payment' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ]);


        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);


        $payment = Payment::query()
            ->where('id', $id)
            ->whereHas(
                'bill.student',
                function ($query) use ($guardian) {

                    $query->where(
                        'guardian_id',
                        $guardian->id
                    );
                }
            )
            ->where('status', 'pending')
            ->firstOrFail();


        /* HAPUS BUKTI LAMA */

        if ($payment->proof_of_payment) {

            Storage::disk('public')
                ->delete(
                    $payment->proof_of_payment
                );
        }


        /* SIMPAN BUKTI BARU */

        $path = $request
            ->file('proof_of_payment')
            ->store(
                'payments/proofs',
                'public'
            );


        $payment->update([
            'proof_of_payment' => $path,

            'proof_uploaded_at' => now(),
        ]);


        return redirect()
            ->route(
                'guardian.payments.show',
                $payment->id
            )
            ->with(
                'success',
                'Bukti pembayaran berhasil diperbarui dan menunggu verifikasi admin.'
            );
    }

    public function receipt($id)
    {
        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);


        $payment = Payment::query()
            ->where('id', $id)
            ->where('status', 'paid')
            ->whereHas(
                'bill.student',
                function ($query) use ($guardian) {

                    $query->where(
                        'guardian_id',
                        $guardian->id
                    );
                }
            )
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();


        return view(
            'guardian.payments.receipt',
            [
                'payment' => $payment,
            ]
        );
    }
}