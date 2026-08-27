<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // DATA SISWA

        $student = Student::with([
            'guardian',
            'classRoom.academicYear',
            'bills',
        ])
        ->where('user_id', Auth::id())
        ->firstOrFail();


        // TAGIHAN

        $bills = $student->bills;


        // STATISTIK TAGIHAN

        $totalBills = $bills->count();

        $paidBills = $bills
            ->where('status', 'paid')
            ->count();

        $unpaidBills = $bills
            ->where('status', 'unpaid')
            ->count();

        $totalAmount = $bills->sum('amount');


        // TAHUN AJARAN

        $academicYearName = $student->classRoom?->academicYear?->name
            ?? 'Tahun Ajaran Aktif';


        // TAGIHAN AKTIF

        $activeBills = $bills
            ->where('status', 'unpaid')
            ->sortBy('due_date')
            ->values();


        // TAGIHAN TERDEKAT

        $nearestBill = $activeBills->first();


        // VIEW

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