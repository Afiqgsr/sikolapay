<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Bill;

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
            'name' => 'SPP Juli 2026',
            'description' => 'Tagihan SPP bulan Juli tahun ajaran 2026/2027',
            'amount' => 500000,
            'due_date' => '2026-07-10',
            'status' => 'unpaid',
        ]);
    }
}