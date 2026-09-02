<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Total admin
        $totalAdmins = User::query()
            ->where('role', 'admin')
            ->count();


        // Total siswa
        $totalStudents = Student::query()
            ->count();


        // Total tagihan
        $totalBills = Bill::query()
            ->count();


        // Total pembayaran
        $totalPayments = Payment::query()
            ->count();


        // Daftar admin terbaru
        $admins = User::query()
            ->where('role', 'admin')
            ->latest()
            ->take(5)
            ->get();


        return view(
            'super_admin.dashboard',
            [
                'totalAdmins' =>
                    $totalAdmins,

                'totalStudents' =>
                    $totalStudents,

                'totalBills' =>
                    $totalBills,

                'totalPayments' =>
                    $totalPayments,

                'admins' =>
                    $admins,
            ]
        );
    }
}