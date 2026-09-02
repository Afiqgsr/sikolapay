<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\ClassRoom;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'class_room_id' => [
                'nullable',
                'integer',
                'exists:class_rooms,id',
            ],

            'status' => [
                'nullable',
                'in:paid,pending,unpaid',
            ],
        ]);


        $query = Bill::query()
            ->with([
                'student.classRoom',
                'latestPayment.latestVerification',
            ]);


        // Filter kelas
        if ($request->filled('class_room_id')) {

            $query->whereHas(
                'student',
                function (Builder $studentQuery) use ($request) {

                    $studentQuery->where(
                        'class_room_id',
                        $request->class_room_id
                    );
                }
            );
        }


        // Filter tanggal awal
        if ($request->filled('start_date')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->start_date
            );
        }


        // Filter tanggal akhir
        if ($request->filled('end_date')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->end_date
            );
        }


        // Filter status
        if ($request->filled('status')) {

            if ($request->status === 'paid') {

                $query->where(function (Builder $statusQuery) {

                    $statusQuery
                        ->where('status', 'paid')
                        ->orWhereHas(
                            'latestPayment',
                            function (Builder $paymentQuery) {

                                $paymentQuery->where(
                                    'status',
                                    'paid'
                                );
                            }
                        );
                });

            }


            if ($request->status === 'pending') {

                $query->whereHas(
                    'latestPayment',
                    function (Builder $paymentQuery) {

                        $paymentQuery
                            ->where('status', 'pending')
                            ->whereNotNull(
                                'proof_of_payment'
                            );
                    }
                );

            }


            if ($request->status === 'unpaid') {

                $query
                    ->where('status', 'unpaid')
                    ->whereDoesntHave(
                        'latestPayment',
                        function (Builder $paymentQuery) {

                            $paymentQuery->whereIn(
                                'status',
                                [
                                    'paid',
                                    'pending',
                                ]
                            );
                        }
                    );
            }
        }


        $reports = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();


        // Summary pembayaran berhasil
        $paymentSummaryQuery = Payment::query()
            ->where('status', 'paid');


        if ($request->filled('start_date')) {

            $paymentSummaryQuery->whereDate(
                'paid_at',
                '>=',
                $request->start_date
            );
        }


        if ($request->filled('end_date')) {

            $paymentSummaryQuery->whereDate(
                'paid_at',
                '<=',
                $request->end_date
            );
        }


        if ($request->filled('class_room_id')) {

            $paymentSummaryQuery->whereHas(
                'bill.student',
                function (Builder $studentQuery) use ($request) {

                    $studentQuery->where(
                        'class_room_id',
                        $request->class_room_id
                    );
                }
            );
        }


        $totalIncome =
            (clone $paymentSummaryQuery)
                ->sum('amount');


        $totalSuccessfulTransactions =
            (clone $paymentSummaryQuery)
                ->count();


        $classRooms = ClassRoom::query()
            ->orderBy('grade')
            ->orderBy('name')
            ->get();


        return view(
            'admin.payment-report',
            [
                'reports' =>
                    $reports,

                'classRooms' =>
                    $classRooms,

                'totalIncome' =>
                    $totalIncome,

                'totalSuccessfulTransactions' =>
                    $totalSuccessfulTransactions,
            ]
        );
    }
}