<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with([
            'bill.student',
            'payer',
            'paymentMethod',
            'verifications.admin',
        ])->get();

        return view('payments.index', compact('payments'));
    }
}