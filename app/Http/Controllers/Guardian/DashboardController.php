<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $guardian = $user?->guardian;

        $students = collect();

        $totalBills = 0;
        $unpaidBills = 0;
        $pendingBills = 0;
        $paidBills = 0;

        $latestBills = collect();


        if ($guardian) {

            $students = $guardian->students()
                ->with([
                    'classRoom',

                    'bills' => function ($query) {
                        $query->with([
                            'latestPayment.latestVerification',
                        ]);
                    },
                ])
                ->get();


            $allBills = $students
                ->flatMap(function ($student) {
                    return $student->bills;
                });


            $totalBills = $allBills->count();


            /* LUNAS */

            $paidBills = $allBills
                ->filter(function ($bill) {

                    return $bill->status === 'paid';

                })
                ->count();


            /* MENUNGGU VERIFIKASI */

            $pendingBills = $allBills
                ->filter(function ($bill) {

                    if ($bill->status === 'paid') {
                        return false;
                    }

                    return $bill->latestPayment?->status === 'pending';

                })
                ->count();


            /* BELUM BAYAR */

            $unpaidBills = $allBills
                ->filter(function ($bill) {

                    if ($bill->status === 'paid') {
                        return false;
                    }

                    if ($bill->latestPayment?->status === 'pending') {
                        return false;
                    }

                    return true;

                })
                ->count();


            /* TAGIHAN TERBARU */

            $latestBills = $allBills
                ->sortByDesc('created_at')
                ->take(5)
                ->values();
        }


        return view('guardian.dashboard', [
            'guardian' => $guardian,

            'students' => $students,

            'totalBills' => $totalBills,

            'unpaidBills' => $unpaidBills,

            'pendingBills' => $pendingBills,

            'paidBills' => $paidBills,

            'latestBills' => $latestBills,
        ]);
    }
}