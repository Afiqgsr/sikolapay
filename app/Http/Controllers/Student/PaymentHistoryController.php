<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $student = Student::where('user_id', Auth::id())
            ->firstOrFail();

        /*-- Query pembayaran --*/

        $query = Payment::whereHas('bill', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with([
            'bill',
            'paymentMethod',
            'latestVerification',
        ]);

        /*-- Filter pencarian --*/

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhereHas('bill', function ($bill) use ($search) {
                        $bill->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('paymentMethod', function ($method) use ($search) {
                        $method->where('name', 'like', "%{$search}%");
                    });
            });
        }

        /*-- Filter tahun --*/

        if ($request->filled('year')) {
            $query->whereYear(
                'paid_at',
                $request->year
            );
        }

        /*-- Filter jenis tagihan --*/

        if ($request->filled('type')) {
            $query->whereHas('bill', function ($bill) use ($request) {
                $bill->where('type', $request->type);
            });
        }

        /*-- Filter status --*/

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        /*-- Pagination --*/

        $payments = $query
            ->latest('paid_at')
            ->paginate(10)
            ->withQueryString();

        /*-- Statistik --*/

        $statQuery = Payment::whereHas('bill', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        });

        $totalTransactions = (clone $statQuery)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalPaid = (clone $statQuery)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $lastPayment = (clone $statQuery)
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with('bill')
            ->latest('paid_at')
            ->first();

        /*-- View --*/

        return view('student.payment-history', [
            'student' => $student,
            'payments' => $payments,
            'totalTransactions' => $totalTransactions,
            'totalPaid' => $totalPaid,
            'lastPayment' => $lastPayment,
        ]);
    }
}
