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
                'latestPayment',
            ]);

        /* FILTER STATUS */

        if ($status === 'unpaid') {

            $query->where('status', 'unpaid')
                ->whereDoesntHave('payments', function ($paymentQuery) {
                    $paymentQuery->whereIn('status', ['pending', 'paid']);
                });

        } elseif ($status === 'pending') {

            $query->whereHas('payments', function ($paymentQuery) {
                $paymentQuery->where('status', 'pending');
            });

        } elseif ($status === 'paid') {

            $query->where('status', 'paid');
        }

        /* AMBIL TAGIHAN */

        $bills = $query
            ->orderByDesc('due_date')
            ->get();

        /* TOTAL TAGIHAN BELUM DIBAYAR */

        $unpaidBills = Bill::where('student_id', $student->id)
            ->where('status', 'unpaid')
            ->whereDoesntHave('payments', function ($paymentQuery) {
                $paymentQuery->whereIn('status', ['pending', 'paid']);
            })
            ->get();

        $unpaidTotal = $unpaidBills->sum('amount');

        /* RETURN VIEW */

        return view('student.bills.index', [
            'bills'       => $bills,
            'unpaidBills' => $unpaidBills,
            'unpaidTotal' => $unpaidTotal,
            'status'      => $status,
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
                'latestPayment',
            ])
            ->findOrFail($id);

        return view('student.bill-detail', [
            'bill' => $bill,
            'student' => $student,
        ]);
    }
}