<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Student;
use Illuminate\Database\Seeder;

class BillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = Student::where('nis', '20260001')->firstOrFail();

        Bill::create([
            'student_id' => $student->id,
            'name' => 'SPP Bulanan',
            'description' => 'Tagihan SPP bulan Juli tahun ajaran 2026/2027',
            'amount' => 500000,
            'due_date' => '2026-07-10',
            'status' => 'unpaid',
        ]);
    }
}
