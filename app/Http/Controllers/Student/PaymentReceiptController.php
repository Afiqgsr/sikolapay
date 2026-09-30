<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentReceiptController extends Controller
{
    public function show(int|string $id): View
    {
        $student = Student::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $payment = Payment::query()
            ->whereKey($id)
            ->where('status', 'paid')
            ->whereHas('bill', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->with([
                'bill',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();

        return view('student.payment-receipt', [
            'student' => $student,
            'payment' => $payment,
        ]);
    }
}
