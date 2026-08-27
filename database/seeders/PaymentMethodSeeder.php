<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Virtual Account BCA',
                'type' => 'virtual_account',
                'code' => 'bca_va',
                'provider' => 'midtrans',
                'is_active' => true,
            ],
            [
                'name' => 'Virtual Account BNI',
                'type' => 'virtual_account',
                'code' => 'bni_va',
                'provider' => 'midtrans',
                'is_active' => true,
            ],
            [
                'name' => 'QRIS',
                'type' => 'qris',
                'code' => 'qris',
                'provider' => 'midtrans',
                'is_active' => true,
            ],
            [
                'name' => 'GoPay',
                'type' => 'e_wallet',
                'code' => 'gopay',
                'provider' => 'midtrans',
                'is_active' => true,
            ],
            [
                'name' => 'ShopeePay',
                'type' => 'e_wallet',
                'code' => 'shopeepay',
                'provider' => 'midtrans',
                'is_active' => true,
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::updateOrCreate(
                ['code' => $paymentMethod['code']],
                $paymentMethod
            );
        }
    }
}