<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $bill = Bill::first();

        $payer = User::where('role', 'guardian')->first();

        $paymentMethod = PaymentMethod::where('code', 'bca_va')->first();

        Payment::create([
            'bill_id' => $bill->id,
            'payer_id' => $payer->id,
            'payment_method_id' => $paymentMethod->id,

            'payment_number' => 'PAY-2026-0001',

            'amount' => $bill->amount,

            'gateway_transaction_id' => 'MID-TRX-2026-0001',
            'gateway_reference' => 'VA-2026-0001',
            'gateway_status' => 'pending',

            'payment_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/example',

            'proof_of_payment' => null,

            'status' => 'pending',
            'paid_at' => null,
        ]);
    }
}