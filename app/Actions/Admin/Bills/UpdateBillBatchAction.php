<?php

namespace App\Actions\Admin\Bills;

use App\Models\Bill;
use App\Models\BillBatch;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateBillBatchAction
{
    /**
     * Memperbarui data BillBatch, menghapus Bill lama, dan membuat ulang Bill baru dalam transaksi database.
     *
     * @param  array<string, mixed>  $validated
     * @param  Collection<int, Student>  $students
     */
    public function execute(
        BillBatch $bill,
        array $validated,
        Collection $students
    ): BillBatch {
        $billingPeriod = $validated['billing_period'].'-01';

        return DB::transaction(function () use (
            $bill,
            $billingPeriod,
            $validated,
            $students
        ): BillBatch {
            $bill->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'semester' => $validated['semester'],
                'amount' => $validated['amount'],
                'billing_period' => $billingPeriod,
                'due_date' => $validated['due_date'] ?? null,
                'target_type' => $validated['target_type'],
                'target_value' => $validated['target_type'] === 'school'
                    ? null
                    : $validated['target_value'],
            ]);

            $bill->bills()->delete();

            foreach ($students as $student) {
                Bill::create([
                    'bill_batch_id' => $bill->id,
                    'student_id' => $student->id,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'semester' => $validated['semester'],
                    'amount' => $validated['amount'],
                    'billing_period' => $billingPeriod,
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => 'unpaid',
                ]);
            }

            return $bill;
        });
    }
}
