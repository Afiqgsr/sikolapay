<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\User;
use Illuminate\Database\Seeder;

class GuardianSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'budi@sikolapay.test')->firstOrFail();

        Guardian::create([
            'user_id' => $user->id,
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Contoh No. 10',
        ]);
    }
}