<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Data siswa

        $student = Student::with([
            'guardian',
            'classRoom.academicYear',
            'bills.payments',
            'bills.latestPayment',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();


        // Tagihan

        $bills = $student->bills;


        // Statistik

        $totalBills = $bills->count();

        $paidBills = $bills
            ->where('status', 'paid')
            ->count();


        // Benar-benar belum dibayar
        $payableBills = $bills->filter(function ($bill) {

            if ($bill->status !== 'unpaid') {
                return false;
            }

            $hasPendingOrPaidPayment = $bill->payments
                ->contains(function ($payment) {
                    return in_array(
                        $payment->status,
                        ['pending', 'paid']
                    );
                });

            return ! $hasPendingOrPaidPayment;
        });


        $unpaidBills = $payableBills->count();

        $totalAmount = $bills->sum('amount');


        // Tahun ajaran

        $academicYearName =
            $student->classRoom?->academicYear?->name
            ?? 'Tahun Ajaran Aktif';


        // Tagihan aktif
        // Tetap tampilkan pending supaya bisa terlihat "Menunggu"

        $activeBills = $bills
            ->where('status', 'unpaid')
            ->sortBy('due_date')
            ->values();


        // Tagihan terdekat yang benar-benar belum dibayar

        $nearestBill = $payableBills
            ->sortBy('due_date')
            ->first();


        return view('student.dashboard', [
            'student'          => $student,
            'totalBills'       => $totalBills,
            'paidBills'        => $paidBills,
            'unpaidBills'      => $unpaidBills,
            'totalAmount'      => $totalAmount,
            'academicYearName' => $academicYearName,
            'activeBills'      => $activeBills,
            'nearestBill'      => $nearestBill,
        ]);
    }
}