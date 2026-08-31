<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class PaymentReceiptController extends Controller
{
    public function show($id)
    {
        // Ambil data siswa yang sedang login
        $student = Student::where('user_id', Auth::id())
            ->firstOrFail();

        // Ambil pembayaran milik siswa tersebut
        $payment = Payment::where('id', $id)
            ->whereHas('bill', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->with([
                'bill',
                'paymentMethod',
                'latestVerification',
            ])
            ->firstOrFail();

        // Hanya pembayaran yang sudah lunas
        if ($payment->status !== 'paid') {
            abort(403);
        }

        return view('student.payment-receipt', [
            'student' => $student,
            'payment' => $payment,
        ]);
    }
}