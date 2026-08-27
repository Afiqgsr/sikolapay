<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentVerification;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $payment = Payment::first();

        $admin = User::where('role', 'admin')->first();

        PaymentVerification::create([
            'payment_id' => $payment->id,
            'admin_id' => $admin->id,
            'status' => 'verified',
            'note' => 'Pembayaran telah diverifikasi oleh admin.',
            'verified_at' => now(),
            'processed_at' => now(),
        ]);
    }
}