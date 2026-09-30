<?php

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillBatch;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $school = School::create(['name' => 'SMK SikolaPay']);
    $academicYear = AcademicYear::create([
        'school_id' => $school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);
    $classRoom = ClassRoom::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'X RPL 1',
        'grade' => 'X',
    ]);
    $guardianUser = User::factory()->create(['role' => 'guardian']);
    $guardian = Guardian::create([
        'user_id' => $guardianUser->id,
        'name' => 'Wali Murid',
        'phone' => '081234567890',
    ]);
    $studentUser = User::factory()->create(['role' => 'student']);

    $this->student = Student::create([
        'user_id' => $studentUser->id,
        'guardian_id' => $guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-BACKFILL',
        'name' => 'Siswa Backfill',
        'gender' => 'L',
    ]);
});

function createLegacyBillingPeriodBatch(
    Student $student,
    string $name,
    ?string $dueDate = '2026-10-02',
    array $batchOverrides = [],
    array $billOverrides = [],
    int $billCount = 1,
): array {
    $batch = BillBatch::create(array_merge([
        'name' => $name,
        'semester' => 'Ganjil 2026/2027',
        'amount' => 350000,
        'due_date' => $dueDate,
        'target_type' => 'student',
        'target_value' => (string) $student->id,
    ], $batchOverrides));

    $bills = collect();

    foreach (range(1, $billCount) as $number) {
        $bills->push(Bill::create(array_merge([
            'bill_batch_id' => $batch->id,
            'student_id' => $student->id,
            'name' => $name,
            'semester' => 'Ganjil 2026/2027',
            'amount' => 350000,
            'due_date' => $dueDate,
            'status' => 'unpaid',
        ], $billOverrides, [
            'description' => "Bill {$number}",
        ])));
    }

    return [$batch, $bills];
}

test('dry run identifies explicit month and year without changing data', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Juli 2026',
        '2026-07-10',
        billCount: 2,
    );

    $this->artisan('bills:backfill-billing-period')
        ->expectsOutputToContain('2026-07-01')
        ->expectsOutputToContain('SAFE')
        ->assertSuccessful();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->every(fn (Bill $bill): bool => $bill->fresh()->billing_period === null))->toBeTrue();
});

test('generic bill name is not automatically inferred from due date', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch($this->student, 'SPP Bulanan');

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])
        ->expectsOutputToContain('AMBIGUOUS')
        ->assertSuccessful();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->first()->fresh()->billing_period)->toBeNull();
});

test('manual mapping normalizes and updates batch with all child bills', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Bulanan',
        billCount: 2,
    );

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
        '--apply' => true,
    ])->assertSuccessful();

    expect($batch->fresh()->billing_period->toDateString())->toBe('2026-09-01')
        ->and($bills->every(
            fn (Bill $bill): bool => $bill->fresh()->billing_period->toDateString() === '2026-09-01'
        ))->toBeTrue()
        ->and($batch->fresh()->due_date->toDateString())->toBe('2026-10-02');
});

test('manual mapping is also a dry run without apply', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch($this->student, 'SPP Bulanan');

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
    ])->expectsOutputToContain('DRY-RUN')->assertSuccessful();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->first()->fresh()->billing_period)->toBeNull();
});

test('invalid manual period is rejected', function (string $period) {
    [$batch] = createLegacyBillingPeriodBatch($this->student, 'SPP Bulanan');

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => $period,
        '--apply' => true,
    ])->expectsOutputToContain('format YYYY-MM')->assertExitCode(Command::INVALID);

    expect($batch->fresh()->billing_period)->toBeNull();
})->with([
    'month name' => 'September 2026',
    'invalid month' => '2026-13',
    'full date' => '2026-09-01',
]);

test('existing billing period is not overwritten by a different manual mapping', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Bulanan',
        batchOverrides: ['billing_period' => '2026-09-01'],
        billOverrides: ['billing_period' => '2026-09-01'],
    );

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-10',
        '--apply' => true,
    ])->expectsOutputToContain('sudah memiliki billing period yang berbeda')->assertFailed();

    expect($batch->fresh()->billing_period->toDateString())->toBe('2026-09-01')
        ->and($bills->first()->fresh()->billing_period->toDateString())->toBe('2026-09-01');
});

test('transaction rolls back batch update when a child update fails', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Bulanan',
        billCount: 2,
    );

    Bill::updating(function (): void {
        throw new RuntimeException('Simulasi kegagalan child Bill.');
    });

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
        '--apply' => true,
    ])->expectsOutputToContain('Simulasi kegagalan child Bill')->assertFailed();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->every(fn (Bill $bill): bool => $bill->fresh()->billing_period === null))->toBeTrue();
});

test('metadata mismatch and orphan bills are reported without modification', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP September 2026',
        billOverrides: ['name' => 'Nama Tidak Sama'],
    );
    $orphan = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Agustus 2026',
        'amount' => 350000,
        'due_date' => '2026-08-10',
        'status' => 'unpaid',
    ]);

    $this->artisan('bills:backfill-billing-period')
        ->expectsOutputToContain('Metadata Bill tidak konsisten')
        ->expectsOutputToContain('Bill orphan tanpa BillBatch')
        ->assertSuccessful();

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
        '--apply' => true,
    ])->expectsOutputToContain('Metadata Bill tidak konsisten')->assertFailed();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->first()->fresh()->billing_period)->toBeNull()
        ->and($orphan->fresh()->billing_period)->toBeNull();
});

test('auto apply updates a safe batch and all child bills', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Juli 2026',
        '2026-08-05',
        billCount: 2,
    );

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])
        ->expectsOutputToContain("BillBatch berhasil diterapkan: {$batch->id}")
        ->assertSuccessful();

    expect($batch->fresh()->billing_period->toDateString())->toBe('2026-07-01')
        ->and($bills->every(
            fn (Bill $bill): bool => $bill->fresh()->billing_period->toDateString() === '2026-07-01'
        ))->toBeTrue();
});

test('auto apply changes only safe records in a mixed dataset', function () {
    [$safeBatch, $safeBills] = createLegacyBillingPeriodBatch($this->student, 'SPP Agustus 2026');
    [$ambiguousBatch, $ambiguousBills] = createLegacyBillingPeriodBatch($this->student, 'SPP Bulanan');
    [$manualBatch, $manualBills] = createLegacyBillingPeriodBatch($this->student, 'SPP Bulan September');

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])
        ->expectsOutputToContain('AMBIGUOUS')
        ->expectsOutputToContain('MANUAL')
        ->assertSuccessful();

    expect($safeBatch->fresh()->billing_period->toDateString())->toBe('2026-08-01')
        ->and($safeBills->first()->fresh()->billing_period->toDateString())->toBe('2026-08-01')
        ->and($ambiguousBatch->fresh()->billing_period)->toBeNull()
        ->and($ambiguousBills->first()->fresh()->billing_period)->toBeNull()
        ->and($manualBatch->fresh()->billing_period)->toBeNull()
        ->and($manualBills->first()->fresh()->billing_period)->toBeNull();
});

test('manual mapping requires both batch and period options', function (array $options, string $message) {
    $this->artisan('bills:backfill-billing-period', $options)
        ->expectsOutputToContain($message)
        ->assertExitCode(Command::INVALID);
})->with([
    'batch without period' => [
        ['--batch' => 1],
        'Option --period wajib',
    ],
    'period without batch' => [
        ['--period' => '2026-09'],
        'Option --batch wajib',
    ],
    'nonnumeric batch' => [
        ['--batch' => 'abc', '--period' => '2026-09'],
        'Option --batch wajib',
    ],
]);

test('manual mapping handles a missing batch safely', function () {
    $this->artisan('bills:backfill-billing-period', [
        '--batch' => 999999,
        '--period' => '2026-09',
        '--apply' => true,
    ])->expectsOutputToContain('BillBatch tidak ditemukan')->assertFailed();
});

test('auto apply skips a consistently populated billing period', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP September 2026',
        batchOverrides: ['billing_period' => '2026-09-01'],
        billOverrides: ['billing_period' => '2026-09-01'],
    );

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])
        ->expectsOutputToContain('SKIP')
        ->expectsOutputToContain('BillBatch berhasil diterapkan: -')
        ->assertSuccessful();

    expect($batch->fresh()->billing_period->toDateString())->toBe('2026-09-01')
        ->and($bills->first()->fresh()->billing_period->toDateString())->toBe('2026-09-01');
});

test('manual preview rejects inconsistent existing child periods', function (?string $childPeriod) {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Bulanan',
        batchOverrides: ['billing_period' => '2026-09-01'],
        billOverrides: ['billing_period' => $childPeriod],
    );

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
    ])->expectsOutputToContain('tidak konsisten')->assertFailed();

    expect($batch->fresh()->billing_period->toDateString())->toBe('2026-09-01')
        ->and($bills->first()->fresh()->billing_period?->toDateString())->toBe($childPeriod);
})->with([
    'child is null' => null,
    'child has another period' => '2026-10-01',
]);

test('manual preview rejects an empty bill batch', function () {
    $batch = BillBatch::create([
        'name' => 'SPP Bulanan',
        'amount' => 350000,
        'due_date' => '2026-09-30',
        'target_type' => 'student',
        'target_value' => (string) $this->student->id,
    ]);

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
    ])->expectsOutputToContain('tidak memiliki Bill turunan')->assertFailed();

    expect($batch->fresh()->billing_period)->toBeNull();
});

test('manual preview rejects each protected metadata mismatch', function (array $billOverrides) {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP Bulanan',
        billOverrides: $billOverrides,
    );

    $this->artisan('bills:backfill-billing-period', [
        '--batch' => $batch->id,
        '--period' => '2026-09',
    ])->expectsOutputToContain('Metadata Bill tidak konsisten')->assertFailed();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->first()->fresh()->billing_period)->toBeNull();
})->with([
    'name' => [['name' => 'Nama Berbeda']],
    'semester' => [['semester' => 'Genap 2026/2027']],
    'amount' => [['amount' => 999999]],
    'due date' => [['due_date' => '2026-11-01']],
]);

test('apply changes only billing period fields', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch(
        $this->student,
        'SPP September 2026',
        '2026-10-05',
        batchOverrides: ['description' => 'Tagihan September'],
        billOverrides: ['description' => 'Tagihan September'],
    );
    $bill = $bills->first();
    $paymentMethod = PaymentMethod::create([
        'name' => 'Bank Transfer',
        'type' => 'bank_transfer',
        'code' => 'BACKFILL-SAFETY',
        'provider' => 'Test Bank',
        'is_active' => true,
    ]);
    $payment = Payment::create([
        'bill_id' => $bill->id,
        'payer_id' => $this->student->user_id,
        'payment_method_id' => $paymentMethod->id,
        'payment_number' => 'PAY-BACKFILL-SAFETY',
        'amount' => $bill->amount,
        'status' => 'pending',
    ]);
    $batchSnapshot = [
        'name' => $batch->name,
        'description' => $batch->description,
        'semester' => $batch->semester,
        'due_date' => $batch->due_date->toDateString(),
    ];
    $billSnapshot = [
        'name' => $bill->name,
        'description' => $bill->description,
        'semester' => $bill->semester,
        'due_date' => $bill->due_date->toDateString(),
        'status' => $bill->status,
    ];
    $paymentSnapshot = (array) DB::table('payments')
        ->where('id', $payment->id)
        ->first();

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])->assertSuccessful();

    $freshBatch = $batch->fresh();
    $freshBill = $bill->fresh();
    $freshPayment = (array) DB::table('payments')
        ->where('id', $payment->id)
        ->first();

    expect([
        'name' => $freshBatch->name,
        'description' => $freshBatch->description,
        'semester' => $freshBatch->semester,
        'due_date' => $freshBatch->due_date->toDateString(),
    ])->toBe($batchSnapshot)
        ->and([
            'name' => $freshBill->name,
            'description' => $freshBill->description,
            'semester' => $freshBill->semester,
            'due_date' => $freshBill->due_date->toDateString(),
            'status' => $freshBill->status,
        ])->toBe($billSnapshot)
        ->and($freshPayment)->toBe($paymentSnapshot);
});

test('auto apply recalculates classification from locked records', function () {
    [$batch, $bills] = createLegacyBillingPeriodBatch($this->student, 'SPP September 2026');
    $changed = false;

    Bill::retrieved(function (Bill $retrievedBill) use (&$changed, $batch): void {
        if ($changed || $retrievedBill->bill_batch_id !== $batch->id) {
            return;
        }

        $changed = true;
        DB::table('bill_batches')->where('id', $batch->id)->update(['name' => 'SPP Bulanan']);
        DB::table('bills')->where('bill_batch_id', $batch->id)->update(['name' => 'SPP Bulanan']);
    });

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])
        ->expectsOutputToContain('AMBIGUOUS')
        ->assertSuccessful();

    expect($batch->fresh()->billing_period)->toBeNull()
        ->and($bills->first()->fresh()->billing_period)->toBeNull()
        ->and($batch->fresh()->name)->toBe('SPP Bulanan');
});

test('auto apply reports partial success with successful and failed batch ids', function () {
    [$successfulBatch, $successfulBills] = createLegacyBillingPeriodBatch($this->student, 'SPP Juli 2026');
    [$failedBatch, $failedBills] = createLegacyBillingPeriodBatch($this->student, 'SPP Agustus 2026');

    Bill::updating(function (Bill $bill) use ($failedBatch): void {
        if ($bill->bill_batch_id === $failedBatch->id) {
            throw new RuntimeException('Simulasi kegagalan batch kedua.');
        }
    });

    $this->artisan('bills:backfill-billing-period', ['--apply' => true])
        ->expectsOutputToContain("BillBatch berhasil diterapkan: {$successfulBatch->id}")
        ->expectsOutputToContain("BillBatch gagal diterapkan: {$failedBatch->id}")
        ->expectsOutputToContain('PARTIAL SUCCESS')
        ->assertFailed();

    expect($successfulBatch->fresh()->billing_period->toDateString())->toBe('2026-07-01')
        ->and($successfulBills->first()->fresh()->billing_period->toDateString())->toBe('2026-07-01')
        ->and($failedBatch->fresh()->billing_period)->toBeNull()
        ->and($failedBills->first()->fresh()->billing_period)->toBeNull();
});
