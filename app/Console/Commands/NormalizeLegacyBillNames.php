<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillBatch;
use DateTimeInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

#[Signature('bills:normalize-legacy-names
    {--apply : Terapkan normalisasi; setiap batch memakai transaksi terpisah}')]
#[Description('Normalisasi nama legacy SPP menjadi SPP Bulanan')]
class NormalizeLegacyBillNames extends Command
{
    /**
     * @var array<string, string>
     */
    private const array NAME_MAPPINGS = [
        'SPP Juli 2026' => 'SPP Bulanan',
        'SPP Agustus 2026' => 'SPP Bulanan',
        'SPP September 2026' => 'SPP Bulanan',
        'SPP Oktober 2026' => 'SPP Bulanan',
        'SPP November 2026' => 'SPP Bulanan',
        'SPP Bulan September' => 'SPP Bulanan',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $batchIds = BillBatch::query()
            ->whereIn('name', array_keys(self::NAME_MAPPINGS))
            ->orderBy('id')
            ->pluck('id');

        if ($batchIds->isEmpty()) {
            $this->info('Tidak ada nama legacy SPP yang perlu dinormalisasi.');

            return self::SUCCESS;
        }

        $rows = [];
        $failedBatchIds = [];

        foreach ($batchIds as $batchId) {
            $batchId = (int) $batchId;

            if (! $apply) {
                $batch = BillBatch::query()->findOrFail($batchId);
                $bills = $this->billsForBatch($batch);
                $validation = $this->validateBatch($batch, $bills);
                $rows[] = $this->rowForBatch(
                    $batch,
                    $bills,
                    $validation['valid'] ? 'VALID' : 'INVALID: '.$validation['message'],
                );

                if (! $validation['valid']) {
                    $failedBatchIds[] = $batchId;
                }

                continue;
            }

            try {
                $rows[] = $this->applyBatch($batchId);
            } catch (Throwable $exception) {
                $failedBatchIds[] = $batchId;
                $rows[] = [$batchId, '-', '-', '-', '-', 'FAILED: '.$exception->getMessage()];
            }
        }

        $this->table([
            'Batch ID',
            'Bill IDs',
            'Legacy Name',
            'New Name',
            'Billing Period',
            'Status',
        ], $rows);

        if (! $apply) {
            $this->line('<comment>DRY-RUN: tidak ada data yang diubah. Gunakan --apply untuk menerapkan normalisasi.</comment>');
        } else {
            $this->warn('Setiap BillBatch diproses dalam transaksi terpisah.');
        }

        if ($failedBatchIds !== []) {
            $this->error('BillBatch yang tidak diproses: '.implode(', ', $failedBatchIds));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array{int, string, string, string, string, string}
     */
    private function applyBatch(int $batchId): array
    {
        return DB::transaction(function () use ($batchId): array {
            $batch = BillBatch::query()
                ->lockForUpdate()
                ->findOrFail($batchId);
            $bills = $this->lockedBillsForBatch($batch);
            $validation = $this->validateBatch($batch, $bills);

            if (! $validation['valid']) {
                throw new RuntimeException($validation['message']);
            }

            $legacyName = $this->batchName($batch);
            $newName = self::NAME_MAPPINGS[$legacyName];

            $batch->newQuery()
                ->whereKey($batch->getKey())
                ->toBase()
                ->update(['name' => $newName]);

            Bill::query()
                ->whereKey($bills->modelKeys())
                ->toBase()
                ->update(['name' => $newName]);

            return $this->rowForBatch($batch, $bills, 'APPLIED');
        });
    }

    /**
     * @param  EloquentCollection<int, Bill>  $bills
     * @return array{valid: bool, message: string}
     */
    private function validateBatch(BillBatch $batch, EloquentCollection $bills): array
    {
        $batchName = $this->batchName($batch);

        if (! array_key_exists($batchName, self::NAME_MAPPINGS)) {
            return ['valid' => false, 'message' => 'Nama batch tidak termasuk mapping eksplisit.'];
        }

        $batchPeriod = $this->dateString($batch, 'billing_period');

        if ($batchPeriod === null) {
            return ['valid' => false, 'message' => 'Billing period batch belum terisi.'];
        }

        if ($bills->isEmpty()) {
            return ['valid' => false, 'message' => 'BillBatch tidak memiliki Bill turunan.'];
        }

        foreach ($bills as $bill) {
            if ($bill->getAttribute('name') !== $batchName) {
                return ['valid' => false, 'message' => "Nama Bill ID {$bill->getKey()} tidak sama dengan batch."];
            }

            if ($this->dateString($bill, 'billing_period') !== $batchPeriod) {
                return ['valid' => false, 'message' => "Billing period Bill ID {$bill->getKey()} tidak sama dengan batch."];
            }
        }

        return ['valid' => true, 'message' => 'Lolos seluruh validasi.'];
    }

    /**
     * @return EloquentCollection<int, Bill>
     */
    private function billsForBatch(BillBatch $batch): EloquentCollection
    {
        return Bill::query()
            ->where('bill_batch_id', $batch->getKey())
            ->orderBy('id')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Bill>
     */
    private function lockedBillsForBatch(BillBatch $batch): EloquentCollection
    {
        return Bill::query()
            ->where('bill_batch_id', $batch->getKey())
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
    }

    private function batchName(BillBatch $batch): string
    {
        $name = $batch->getAttribute('name');

        if (! is_string($name)) {
            throw new RuntimeException('Nama BillBatch tidak valid.');
        }

        return $name;
    }

    private function dateString(Bill|BillBatch $model, string $attribute): ?string
    {
        $value = $model->getAttribute($attribute);

        if ($value === null) {
            return null;
        }

        if (! $value instanceof DateTimeInterface) {
            throw new RuntimeException("Attribute {$attribute} bukan nilai tanggal yang valid.");
        }

        return $value->format('Y-m-d');
    }

    /**
     * @param  EloquentCollection<int, Bill>  $bills
     * @return array{int, string, string, string, string, string}
     */
    private function rowForBatch(BillBatch $batch, EloquentCollection $bills, string $status): array
    {
        $legacyName = $this->batchName($batch);

        return [
            (int) $batch->getKey(),
            $bills->pluck('id')->implode(', ') ?: '-',
            $legacyName,
            self::NAME_MAPPINGS[$legacyName],
            $this->dateString($batch, 'billing_period') ?? '-',
            $status,
        ];
    }
}
