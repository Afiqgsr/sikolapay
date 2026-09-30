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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Signature('bills:backfill-billing-period
    {--apply : Terapkan kandidat SAFE atau mapping manual; setiap batch memakai transaksi terpisah}
    {--batch= : ID BillBatch untuk mapping manual}
    {--period= : Periode manual dalam format YYYY-MM}')]
#[Description('Audit dan backfill billing period secara konservatif')]
class BackfillBillingPeriods extends Command
{
    /**
     * @var array<string, int>
     */
    private const array MONTHS = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    public function handle(): int
    {
        $batchOption = $this->option('batch');
        $periodOption = $this->option('period');

        if ($batchOption !== null || $periodOption !== null) {
            return $this->handleManualMapping($batchOption, $periodOption);
        }

        return $this->handleAudit();
    }

    private function handleAudit(): int
    {
        $apply = (bool) $this->option('apply');
        $rows = [];
        $appliedBatchIds = [];
        $failedBatchIds = [];

        $batches = BillBatch::query()
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            $bills = $this->billsForBatch($batch);

            if ($apply) {
                try {
                    $result = $this->applySafeBatch($batch->id);
                    $rows[] = $result['row'];

                    if ($result['applied']) {
                        $appliedBatchIds[] = $batch->id;
                    }
                } catch (Throwable $exception) {
                    $failedBatchIds[] = $batch->id;
                    $rows[] = $this->rowForBatch($batch, $bills, [
                        'candidate' => null,
                        'source' => 'Gagal diterapkan: '.$exception->getMessage(),
                        'status' => 'MANUAL',
                    ]);
                }

                continue;
            }

            $classification = $this->classify($batch, $bills);
            $rows[] = $this->rowForBatch($batch, $bills, $classification);
        }

        $orphanBills = Bill::query()
            ->whereNull('bill_batch_id')
            ->orderBy('id')
            ->get();

        foreach ($orphanBills as $orphanBill) {
            $rows[] = [
                '-',
                (string) $orphanBill->id,
                $orphanBill->name,
                $this->dateString($orphanBill, 'due_date') ?? '-',
                '-',
                'Bill orphan tanpa BillBatch',
                'MANUAL',
            ];
        }

        $this->table([
            'BillBatch ID',
            'Bill IDs',
            'Nama Tagihan',
            'Due Date',
            'Kandidat Periode',
            'Sumber Inferensi',
            'Status',
        ], $rows);

        $safeCount = 0;
        $ambiguousCount = 0;
        $manualCount = 0;
        $skipCount = 0;

        foreach ($rows as $row) {
            match ($row[6]) {
                'SAFE' => $safeCount++,
                'AMBIGUOUS' => $ambiguousCount++,
                'MANUAL' => $manualCount++,
                'SKIP' => $skipCount++,
                default => null,
            };
        }

        $this->newLine();
        $this->line('SAFE: '.$safeCount);
        $this->line('AMBIGUOUS: '.$ambiguousCount);
        $this->line('MANUAL: '.$manualCount);
        $this->line('SKIP: '.$skipCount);

        if (! $apply) {
            $this->line('<comment>DRY-RUN: tidak ada data yang diubah. Gunakan --apply untuk menerapkan kandidat SAFE.</comment>');
        } else {
            $this->newLine();
            $this->info('BillBatch berhasil diterapkan: '.($appliedBatchIds === [] ? '-' : implode(', ', $appliedBatchIds)));
            $this->line('BillBatch gagal diterapkan: '.($failedBatchIds === [] ? '-' : implode(', ', $failedBatchIds)));
            $this->warn('PARTIAL SUCCESS: setiap BillBatch diproses dalam transaksi terpisah; batch yang sudah berhasil tidak di-rollback jika batch berikutnya gagal.');
        }

        return $failedBatchIds === [] ? self::SUCCESS : self::FAILURE;
    }

    private function handleManualMapping(mixed $batchOption, mixed $periodOption): int
    {
        if (
            (! is_string($batchOption) && ! is_int($batchOption))
            || ! ctype_digit((string) $batchOption)
            || (int) $batchOption < 1
        ) {
            $this->error('Option --batch wajib berupa ID positif ketika melakukan mapping manual.');

            return self::INVALID;
        }

        if (! is_string($periodOption) || Validator::make(
            ['period' => $periodOption],
            ['period' => ['required', 'date_format:Y-m']]
        )->fails()) {
            $this->error('Option --period wajib menggunakan format YYYY-MM yang valid.');

            return self::INVALID;
        }

        $batch = BillBatch::query()->find((int) $batchOption);

        if (! $batch) {
            $this->error('BillBatch tidak ditemukan.');

            return self::FAILURE;
        }

        $period = $periodOption.'-01';
        $bills = $this->billsForBatch($batch);
        $validation = $this->validateManualMapping($batch, $bills, $period);
        $classification = [
            'candidate' => $period,
            'source' => $validation['message'],
            'status' => $validation['valid']
                ? ($validation['changed'] ? 'VALID' : 'SKIP')
                : 'INVALID',
        ];

        $this->table([
            'BillBatch ID',
            'Bill IDs',
            'Nama Tagihan',
            'Due Date',
            'Kandidat Periode',
            'Sumber Inferensi',
            'Status',
        ], [$this->rowForBatch($batch, $bills, $classification)]);

        if (! $validation['valid']) {
            $this->error('Mapping manual tidak valid: '.$validation['message']);

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->line('<comment>DRY-RUN VALID: mapping manual lolos validasi tetapi belum diterapkan. Tambahkan --apply untuk mengubah data.</comment>');

            return self::SUCCESS;
        }

        try {
            $changed = $this->applyManualPeriod($batch->id, $period);
        } catch (Throwable $exception) {
            $this->error('Mapping manual dibatalkan: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $changed) {
            $this->info('BillBatch dan seluruh Bill sudah menggunakan periode tersebut; tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        $this->info('Billing period berhasil diperbarui secara atomik.');

        return self::SUCCESS;
    }

    /**
     * @param  EloquentCollection<int, Bill>  $bills
     * @return array{candidate: ?string, source: string, status: string}
     */
    private function classify(BillBatch $batch, EloquentCollection $bills): array
    {
        if ($bills->isEmpty()) {
            return [
                'candidate' => null,
                'source' => 'BillBatch tidak memiliki Bill turunan',
                'status' => 'MANUAL',
            ];
        }

        if (! $this->hasConsistentMetadata($batch, $bills)) {
            return [
                'candidate' => null,
                'source' => 'Metadata Bill tidak konsisten dengan BillBatch',
                'status' => 'MANUAL',
            ];
        }

        $batchPeriod = $this->dateString($batch, 'billing_period');
        $childPeriods = $bills
            ->map(fn (Bill $bill): ?string => $this->dateString($bill, 'billing_period'))
            ->unique()
            ->values();

        if ($batchPeriod !== null) {
            if ($childPeriods->count() === 1 && $childPeriods->first() === $batchPeriod) {
                return [
                    'candidate' => $batchPeriod,
                    'source' => 'Billing period sudah terisi konsisten',
                    'status' => 'SKIP',
                ];
            }

            return [
                'candidate' => $batchPeriod,
                'source' => 'Billing period batch dan Bill tidak konsisten',
                'status' => 'MANUAL',
            ];
        }

        if ($childPeriods->contains(fn (?string $period): bool => $period !== null)) {
            return [
                'candidate' => null,
                'source' => 'Sebagian Bill sudah memiliki billing period',
                'status' => 'MANUAL',
            ];
        }

        $periodFromName = $this->inferPeriodFromName($batch->name);

        if ($periodFromName !== null) {
            return [
                'candidate' => $periodFromName,
                'source' => 'Nama mengandung bulan dan tahun eksplisit',
                'status' => 'SAFE',
            ];
        }

        if (in_array($batch->name, Bill::TYPES, true) && $this->dateString($batch, 'due_date') !== null) {
            return [
                'candidate' => null,
                'source' => 'Kategori generik; due_date bukan bukti periode',
                'status' => 'AMBIGUOUS',
            ];
        }

        return [
            'candidate' => null,
            'source' => 'Nama tidak memiliki bulan dan tahun eksplisit',
            'status' => 'MANUAL',
        ];
    }

    private function inferPeriodFromName(string $name): ?string
    {
        $matches = [];
        $matchCount = preg_match_all(
            '/\b('.implode('|', array_keys(self::MONTHS)).')\s+(\d{4})\b/iu',
            Str::lower($name),
            $matches,
            PREG_SET_ORDER
        );

        if ($matchCount !== 1) {
            return null;
        }

        $month = self::MONTHS[$matches[0][1]];
        $year = (int) $matches[0][2];

        if ($year < 1900 || $year > 9999) {
            return null;
        }

        return sprintf('%04d-%02d-01', $year, $month);
    }

    /**
     * @return array{row: array{int, string, string, string, string, string, string}, applied: bool}
     */
    private function applySafeBatch(int $batchId): array
    {
        return DB::transaction(function () use ($batchId): array {
            $batch = BillBatch::query()
                ->lockForUpdate()
                ->findOrFail($batchId);

            $bills = $this->lockedBillsForBatch($batch);
            $classification = $this->classify($batch, $bills);

            if (
                $classification['status'] !== 'SAFE'
                || $classification['candidate'] === null
            ) {
                return [
                    'row' => $this->rowForBatch($batch, $bills, $classification),
                    'applied' => false,
                ];
            }

            $this->updatePeriod($batch, $bills, $classification['candidate']);
            $classification['source'] .= '; diterapkan setelah validasi terkunci';

            return [
                'row' => $this->rowForBatch($batch, $bills, $classification),
                'applied' => true,
            ];
        });
    }

    private function applyManualPeriod(int $batchId, string $period): bool
    {
        return DB::transaction(function () use ($batchId, $period): bool {
            $batch = BillBatch::query()
                ->lockForUpdate()
                ->findOrFail($batchId);

            $bills = $this->lockedBillsForBatch($batch);
            $validation = $this->validateManualMapping($batch, $bills, $period);

            if (! $validation['valid']) {
                throw new RuntimeException($validation['message']);
            }

            if (! $validation['changed']) {
                return false;
            }

            $this->updatePeriod($batch, $bills, $period);

            return true;
        });
    }

    /**
     * @param  EloquentCollection<int, Bill>  $bills
     * @return array{valid: bool, changed: bool, message: string}
     */
    private function validateManualMapping(
        BillBatch $batch,
        EloquentCollection $bills,
        string $period
    ): array {
        if ($bills->isEmpty()) {
            return [
                'valid' => false,
                'changed' => false,
                'message' => 'BillBatch tidak memiliki Bill turunan.',
            ];
        }

        if (! $this->hasConsistentMetadata($batch, $bills)) {
            return [
                'valid' => false,
                'changed' => false,
                'message' => 'Metadata Bill tidak konsisten dengan BillBatch.',
            ];
        }

        $batchPeriod = $this->dateString($batch, 'billing_period');

        if ($batchPeriod !== null && $batchPeriod !== $period) {
            return [
                'valid' => false,
                'changed' => false,
                'message' => 'BillBatch sudah memiliki billing period yang berbeda.',
            ];
        }

        if (
            $batchPeriod !== null
            && $bills->contains(
                fn (Bill $bill): bool => $this->dateString($bill, 'billing_period') !== $batchPeriod
            )
        ) {
            return [
                'valid' => false,
                'changed' => false,
                'message' => 'Billing period batch dan Bill tidak konsisten.',
            ];
        }

        foreach ($bills as $bill) {
            $childPeriod = $this->dateString($bill, 'billing_period');

            if ($childPeriod !== null && $childPeriod !== $period) {
                return [
                    'valid' => false,
                    'changed' => false,
                    'message' => "Bill ID {$bill->id} sudah memiliki billing period yang berbeda.",
                ];
            }
        }

        $changed = $batchPeriod !== $period
            || $bills->contains(
                fn (Bill $bill): bool => $this->dateString($bill, 'billing_period') !== $period
            );

        return [
            'valid' => true,
            'changed' => $changed,
            'message' => $changed
                ? 'Mapping manual lolos seluruh validasi.'
                : 'BillBatch dan seluruh Bill sudah menggunakan periode tersebut.',
        ];
    }

    /**
     * @param  EloquentCollection<int, Bill>  $bills
     */
    private function updatePeriod(
        BillBatch $batch,
        EloquentCollection $bills,
        string $period
    ): void {
        $batch->update(['billing_period' => $period]);

        foreach ($bills as $bill) {
            $bill->update(['billing_period' => $period]);
        }
    }

    /**
     * @return EloquentCollection<int, Bill>
     */
    private function lockedBillsForBatch(BillBatch $batch): EloquentCollection
    {
        return Bill::query()
            ->where('bill_batch_id', $batch->id)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, Bill>  $bills
     */
    private function hasConsistentMetadata(BillBatch $batch, EloquentCollection $bills): bool
    {
        return $bills->every(function (Bill $bill) use ($batch): bool {
            return $bill->bill_batch_id === $batch->id
                && $bill->name === $batch->name
                && $bill->semester === $batch->semester
                && $bill->amount === $batch->amount
                && $this->dateString($bill, 'due_date') === $this->dateString($batch, 'due_date');
        });
    }

    /**
     * @return EloquentCollection<int, Bill>
     */
    private function billsForBatch(BillBatch $batch): EloquentCollection
    {
        return Bill::query()
            ->where('bill_batch_id', $batch->id)
            ->orderBy('id')
            ->get();
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
     * @param  array{candidate: ?string, source: string, status: string}  $classification
     * @return array{int, string, string, string, string, string, string}
     */
    private function rowForBatch(
        BillBatch $batch,
        EloquentCollection $bills,
        array $classification
    ): array {
        return [
            $batch->id,
            $bills->pluck('id')->implode(', ') ?: '-',
            $batch->name,
            $this->dateString($batch, 'due_date') ?? '-',
            $classification['candidate'] ?? '-',
            $classification['source'],
            $classification['status'],
        ];
    }
}
