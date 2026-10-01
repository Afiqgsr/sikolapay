<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Support\Facades\Auth;

class BillController extends Controller
{
    public function index()
    {
        $student = Auth::user()->student;

        $status = request('status');

        $query = Bill::where('student_id', $student->id)
            ->with([
                'payments',
                'latestPayment.latestVerification',
            ]);

        /* Filter status */

        if ($status === 'unpaid') {

            $query->where('status', 'unpaid')
                ->whereDoesntHave('payments', function ($paymentQuery) {
                    $paymentQuery->whereIn('status', ['pending', 'paid']);
                });

        } elseif ($status === 'pending') {

            $query->whereHas('payments', function ($paymentQuery) {
                $paymentQuery
                    ->where('status', 'pending')
                    ->where(function ($query) {
                        $query
                            ->whereDoesntHave('latestVerification')
                            ->orWhereHas('latestVerification', function ($verification) {
                                $verification->where('status', '!=', 'rejected');
                            });
                    });
            });

        } elseif ($status === 'paid') {

            $query->where('status', 'paid');

        } elseif ($status === 'rejected') {

            $query->whereHas('latestPayment.latestVerification', function ($verification) {
                $verification->where('status', 'rejected');
            });
        }

        /* Ambil tagihan */

        $bills = $query
            ->orderByDesc('due_date')
            ->get();

        /* Tagihan belum dibayar yang eligible untuk dibayar sekarang (tidak termasuk future period dan pending/paid) */

        $payableBills = Bill::where('student_id', $student->id)
            ->where('status', 'unpaid')
            ->billingPeriodStarted()
            ->whereDoesntHave('payments', function ($paymentQuery) {
                $paymentQuery->whereIn('status', ['pending', 'paid']);
            })
            ->get();

        $unpaidBills = $payableBills;
        $unpaidTotal = $payableBills->sum('amount');

        return view('student.bills.index', [
            'bills' => $bills,
            'unpaidBills' => $unpaidBills,
            'unpaidTotal' => $unpaidTotal,
            'status' => $status,
        ]);
    }

    public function show($id)
    {
        $student = Auth::user()->student;

        $bill = Bill::where('student_id', $student->id)
            ->with([
                'student.classRoom',
                'student.guardian',
                'payments.paymentMethod',
                'latestPayment.latestVerification',
            ])
            ->findOrFail($id);

        return view('student.bill-detail', [
            'bill' => $bill,
            'student' => $student,
        ]);
    }
}
