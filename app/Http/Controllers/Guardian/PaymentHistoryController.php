<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);

        $studentIds = $guardian->students()
            ->pluck('id');


        $query = Payment::query()
            ->whereHas(
                'bill',
                function ($billQuery) use ($studentIds) {

                    $billQuery->whereIn(
                        'student_id',
                        $studentIds
                    );
                }
            )
            ->with([
                'bill.student.classRoom',
                'paymentMethod',
                'latestVerification',
            ]);


        /* Search */

        if ($request->filled('search')) {

            $search = trim(
                $request
                    ->string('search')
                    ->toString()
            );


            $query->where(
                function ($query) use ($search) {

                    $query
                        ->where(
                            'payment_number',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'bill',
                            function ($billQuery) use ($search) {

                                $billQuery
                                    ->where(
                                        'name',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhereHas(
                                        'student',
                                        function ($studentQuery) use ($search) {

                                            $studentQuery
                                                ->where(
                                                    'name',
                                                    'like',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'nis',
                                                    'like',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'nisn',
                                                    'like',
                                                    '%' . $search . '%'
                                                );
                                        }
                                    );
                            }
                        );
                }
            );
        }


        /* Student */

        if ($request->filled('student')) {

            $query->whereHas(
                'bill',
                function ($billQuery) use ($request) {

                    $billQuery->where(
                        'student_id',
                        $request->student
                    );
                }
            );
        }


        /* Status */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        $payments = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();


        $students = $guardian->students()
            ->with('classRoom')
            ->get();


        $totalPayments = Payment::query()
            ->whereHas(
                'bill',
                function ($billQuery) use ($studentIds) {

                    $billQuery->whereIn(
                        'student_id',
                        $studentIds
                    );
                }
            )
            ->count();


        $paidPayments = Payment::query()
            ->whereHas(
                'bill',
                function ($billQuery) use ($studentIds) {

                    $billQuery->whereIn(
                        'student_id',
                        $studentIds
                    );
                }
            )
            ->where('status', 'paid')
            ->count();


        $pendingPayments = Payment::query()
            ->whereHas(
                'bill',
                function ($billQuery) use ($studentIds) {

                    $billQuery->whereIn(
                        'student_id',
                        $studentIds
                    );
                }
            )
            ->where('status', 'pending')
            ->count();


        $rejectedPayments = Payment::query()
            ->whereHas(
                'bill',
                function ($billQuery) use ($studentIds) {

                    $billQuery->whereIn(
                        'student_id',
                        $studentIds
                    );
                }
            )
            ->where('status', 'rejected')
            ->count();


        return view(
            'guardian.payment-history',
            [
                'students' => $students,
                'payments' => $payments,

                'totalPayments' => $totalPayments,
                'paidPayments' => $paidPayments,
                'pendingPayments' => $pendingPayments,
                'rejectedPayments' => $rejectedPayments,
            ]
        );
    }
}