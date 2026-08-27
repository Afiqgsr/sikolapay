<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@sikolapay.test',
            'password' => 'password',
            'role' => 'guardian',
        ]);

        User::create([
            'name' => 'Andi Santoso',
            'email' => 'andi@sikolapay.test',
            'password' => 'password',
            'role' => 'student',
        ]);

        User::create([
            'name' => 'Admin SikolaPay',
            'email' => 'admin@sikolapay.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}