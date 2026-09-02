<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik

        $totalStudents = Student::count();

        $totalActiveBills = Bill::query()
            ->where('status', 'unpaid')
            ->count();

        $successfulPaymentsAmount = Payment::query()
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');


        // Tagihan yang benar-benar belum dibayar

        $unpaidBills = Bill::query()
            ->with([
                'latestPayment.latestVerification',
            ])
            ->where('status', 'unpaid')
            ->get()
            ->filter(function ($bill) {

                $payment = $bill->latestPayment;

                if (!$payment) {
                    return true;
                }

                if ($payment->status === 'paid') {
                    return false;
                }

                if ($payment->status !== 'pending') {
                    return true;
                }

                if (!$payment->proof_of_payment) {
                    return true;
                }

                $latestVerification =
                    $payment->latestVerification;

                $hasRejectedVerification =
                    $latestVerification?->status === 'rejected';

                $isResubmitted =
                    $hasRejectedVerification
                    && $payment->proof_uploaded_at
                    && $latestVerification?->processed_at
                    && $payment->proof_uploaded_at->gt(
                        $latestVerification->processed_at
                    );

                $isWaitingVerification =
                    !$hasRejectedVerification
                    || $isResubmitted;

                return !$isWaitingVerification;
            })
            ->count();


        // Pembayaran menunggu verifikasi

        $pendingPayments = Payment::query()
            ->with('latestVerification')
            ->where('status', 'pending')
            ->whereNotNull('proof_of_payment')
            ->get()
            ->filter(function ($payment) {

                $latestVerification =
                    $payment->latestVerification;

                if (!$latestVerification) {
                    return true;
                }

                if ($latestVerification->status !== 'rejected') {
                    return true;
                }

                if (
                    !$payment->proof_uploaded_at
                    || !$latestVerification->processed_at
                ) {
                    return false;
                }

                return $payment->proof_uploaded_at->gt(
                    $latestVerification->processed_at
                );
            })
            ->count();


        // Pembayaran terbaru

        $latestPayments = Payment::query()
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'latestVerification',
            ])
            ->latest()
            ->take(5)
            ->get();


        // Progress semua tagihan

        $bills = Bill::query()
            ->with([
                'latestPayment.latestVerification',
            ])
            ->get();

        $totalBills = $bills->count();

        $paidBills = 0;
        $pendingBills = 0;
        $unpaidProgressBills = 0;


        foreach ($bills as $bill) {

            $payment = $bill->latestPayment;


            // Lunas

            if (
                $bill->status === 'paid'
                || $payment?->status === 'paid'
            ) {
                $paidBills++;

                continue;
            }


            // Belum punya pembayaran

            if (!$payment) {
                $unpaidProgressBills++;

                continue;
            }


            // Payment bukan pending

            if ($payment->status !== 'pending') {
                $unpaidProgressBills++;

                continue;
            }


            // Pending tapi belum upload bukti

            if (!$payment->proof_of_payment) {
                $unpaidProgressBills++;

                continue;
            }


            $latestVerification =
                $payment->latestVerification;

            $hasRejectedVerification =
                $latestVerification?->status === 'rejected';


            // Belum pernah ditolak

            if (!$hasRejectedVerification) {
                $pendingBills++;

                continue;
            }


            // Sudah ditolak, cek apakah upload ulang

            $isResubmitted =
                $payment->proof_uploaded_at
                && $latestVerification?->processed_at
                && $payment->proof_uploaded_at->gt(
                    $latestVerification->processed_at
                );


            if ($isResubmitted) {

                $pendingBills++;

            } else {

                $unpaidProgressBills++;

            }
        }


        // Total yang sudah selesai

        $processedBills =
            $paidBills;


        // Persentase

        $paidPercentage =
            $totalBills > 0
                ? round(
                    ($paidBills / $totalBills) * 100
                )
                : 0;

        $pendingPercentage =
            $totalBills > 0
                ? round(
                    ($pendingBills / $totalBills) * 100
                )
                : 0;

        $unpaidPercentage =
            $totalBills > 0
                ? round(
                    ($unpaidProgressBills / $totalBills) * 100
                )
                : 0;

        $progressPercentage =
            $totalBills > 0
                ? round(
                    ($paidBills / $totalBills) * 100
                )
                : 0;


        return view('admin.dashboard', [

            'totalStudents' =>
                $totalStudents,

            'totalActiveBills' =>
                $totalActiveBills,

            'successfulPaymentsAmount' =>
                $successfulPaymentsAmount,

            'unpaidBills' =>
                $unpaidBills,

            'pendingPayments' =>
                $pendingPayments,

            'latestPayments' =>
                $latestPayments,

            'totalBills' =>
                $totalBills,

            'paidBills' =>
                $paidBills,

            'pendingBills' =>
                $pendingBills,

            'unpaidProgressBills' =>
                $unpaidProgressBills,

            'processedBills' =>
                $processedBills,

            'paidPercentage' =>
                $paidPercentage,

            'pendingPercentage' =>
                $pendingPercentage,

            'unpaidPercentage' =>
                $unpaidPercentage,

            'progressPercentage' =>
                $progressPercentage,
        ]);
    }
}