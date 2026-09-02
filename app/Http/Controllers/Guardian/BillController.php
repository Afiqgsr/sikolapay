<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $guardian = Auth::user()?->guardian;

        $students = collect();
        $bills = collect();

        if ($guardian) {

            $students = $guardian->students()
                ->with('classRoom')
                ->get();

            $studentIds = $students->pluck('id');

            if ($studentIds->isNotEmpty()) {

                $query = Bill::query()
                    ->whereIn('student_id', $studentIds)
                    ->with([
                        'student.classRoom',
                        'latestPayment.latestVerification',
                    ]);


                if ($request->filled('student')) {

                    $query->where(
                        'student_id',
                        $request->student
                    );
                }


                if ($request->filled('status')) {

                    $status = $request->status;

                    if ($status === 'paid') {

                        $query->where('status', 'paid');

                    } elseif ($status === 'pending') {

                        $query->whereHas(
                            'latestPayment',
                            function ($paymentQuery) {

                                $paymentQuery->where(
                                    'status',
                                    'pending'
                                );
                            }
                        );

                    } elseif ($status === 'rejected') {

                        $query->whereHas(
                            'latestPayment',
                            function ($paymentQuery) {

                                $paymentQuery->where(
                                    'status',
                                    'rejected'
                                );
                            }
                        );

                    } elseif ($status === 'unpaid') {

                        $query
                            ->where('status', 'unpaid')
                            ->whereDoesntHave(
                                'latestPayment',
                                function ($paymentQuery) {

                                    $paymentQuery->whereIn(
                                        'status',
                                        [
                                            'pending',
                                            'paid',
                                        ]
                                    );
                                }
                            );
                    }
                }


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


                $bills = $query
                    ->latest()
                    ->paginate(10)
                    ->withQueryString();
            }
        }


        return view(
            'guardian.bills.index',
            [
                'guardian' => $guardian,
                'students' => $students,
                'bills' => $bills,
            ]
        );
    }

    public function show($id)
    {
        $guardian = Auth::user()?->guardian;

        abort_unless($guardian, 404);

        $studentIds = $guardian->students()
            ->pluck('id');

        $bill = Bill::query()
            ->whereIn('student_id', $studentIds)
            ->with([
                'student.classRoom',
            ])
            ->findOrFail($id);

        return view('guardian.bills.show', [
            'bill' => $bill,
        ]);
    }
}