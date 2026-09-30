<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        School::create([
            'name' => 'SMK SikolaPay',
            'npsn' => '12345678',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '081234567890',
            'email' => 'sekolah@sikolapay.test',
        ]);
    }
}
