<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Bill;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();

        $totalBills = Bill::count();

        $pendingPayments = Payment::where('status', 'pending')
            ->count();

        $paidPayments = Payment::where('status', 'paid')
            ->count();

        $latestPayments = Payment::with([
            'bill.student',
            'paymentMethod',
        ])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'totalStudents'   => $totalStudents,
            'totalBills'      => $totalBills,
            'pendingPayments' => $pendingPayments,
            'paidPayments'    => $paidPayments,
            'latestPayments'  => $latestPayments,
        ]);
    }
}