<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $student = Student::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $query = Payment::query()
            ->whereHas('bill', function ($billQuery) use ($student) {
                $billQuery->where('student_id', $student->id);
            })
            ->with([
                'bill',
                'paymentMethod',
                'latestVerification',
            ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($paymentQuery) use ($search) {
                $paymentQuery
                    ->where('payment_number', 'like', "%{$search}%")
                    ->orWhereHas('bill', function ($billQuery) use ($search) {
                        $billQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('paymentMethod', function ($methodQuery) use ($search) {
                        $methodQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('type')) {
            $type = (string) $request->input('type');

            if (in_array($type, Bill::TYPES, true)) {
                $query->whereHas('bill', function ($billQuery) use ($type) {
                    $billQuery->where('name', $type);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'rejected') {
                $query->whereHas('latestVerification', function ($verificationQuery) {
                    $verificationQuery->where('status', 'rejected');
                });
            } elseif ($request->status === 'pending') {
                $query
                    ->where('status', 'pending')
                    ->where(function ($pendingQuery) {
                        $pendingQuery
                            ->whereDoesntHave('latestVerification')
                            ->orWhereHas('latestVerification', function ($verificationQuery) {
                                $verificationQuery->where('status', '!=', 'rejected');
                            });
                    });
            } else {
                $query->where('status', $request->status);
            }
        }

        $payments = $query
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $statQuery = Payment::query()
            ->whereHas('bill', function ($billQuery) use ($student) {
                $billQuery->where('student_id', $student->id);
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

        return view('student.payment-history', [
            'student' => $student,
            'payments' => $payments,
            'totalTransactions' => $totalTransactions,
            'totalPaid' => $totalPaid,
            'lastPayment' => $lastPayment,
            'billTypes' => Bill::TYPES,
        ]);
    }
}
