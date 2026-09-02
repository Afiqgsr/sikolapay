<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBillBatches extends Command
{
    protected $signature = 'bills:backfill-batches';

    protected $description = 'Membuat bill batch dari tagihan lama yang belum memiliki batch';

    public function handle(): int
    {
        $legacyBills = Bill::query()
            ->with([
                'student.classRoom',
            ])
            ->whereNull('bill_batch_id')
            ->get();

        if ($legacyBills->isEmpty()) {
            $this->info('Tidak ada tagihan lama yang perlu diproses.');

            return self::SUCCESS;
        }

        $groups = $legacyBills->groupBy(function ($bill) {
            return implode('|', [
                $bill->name ?? '',
                $bill->semester ?? '',
                $bill->amount ?? '',
                $bill->due_date?->format('Y-m-d') ?? '',
                $bill->description ?? '',
            ]);
        });

        DB::transaction(function () use ($groups) {

            foreach ($groups as $bills) {

                $firstBill = $bills->first();

                $classRoomIds = $bills
                    ->pluck('student.class_room_id')
                    ->filter()
                    ->unique()
                    ->values();

                $entryYears = $bills
                    ->pluck('student.entry_year')
                    ->filter()
                    ->unique()
                    ->values();

                if (
                    $classRoomIds->count() === 1
                    && $bills->count() > 1
                ) {

                    $targetType = 'class';
                    $targetValue = $classRoomIds->first();

                } elseif (
                    $entryYears->count() === 1
                    && $bills->count() > 1
                ) {

                    $targetType = 'cohort';
                    $targetValue = $entryYears->first();

                } elseif ($bills->count() === 1) {

                    $targetType = 'student';
                    $targetValue = $firstBill->student_id;

                } else {

                    $targetType = 'school';
                    $targetValue = null;

                }

                $batch = BillBatch::create([
                    'name' => $firstBill->name,
                    'description' => $firstBill->description,
                    'semester' => $firstBill->semester,
                    'amount' => $firstBill->amount,
                    'due_date' => $firstBill->due_date,
                    'target_type' => $targetType,
                    'target_value' => $targetValue,
                ]);

                Bill::query()
                    ->whereIn(
                        'id',
                        $bills->pluck('id')
                    )
                    ->update([
                        'bill_batch_id' => $batch->id,
                    ]);
            }

        });

        $this->info(
            'Berhasil membuat '
            . $groups->count()
            . ' batch dari '
            . $legacyBills->count()
            . ' tagihan lama.'
        );

        return self::SUCCESS;
    }
}