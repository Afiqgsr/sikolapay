<?php

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillBatch;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentVerification;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
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
        'nis' => 'NIS-NORMALIZE',
        'name' => 'Siswa Normalize',
        'gender' => 'L',
    ]);
});

function createLegacyNameBatch(
    Student $student,
    string $name = 'SPP September 2026',
    array $batchOverrides = [],
    array $billOverrides = [],
    int $billCount = 1,
): array {
    $batch = BillBatch::create(array_merge([
        'name' => $name,
        'description' => 'Tagihan legacy',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 350000,
        'billing_period' => '2026-09-01',
        'due_date' => '2026-10-05',
        'target_type' => 'student',
        'target_value' => (string) $student->id,
    ], $batchOverrides));

    $bills = collect();

    if ($billCount > 0) {
        foreach (range(1, $billCount) as $number) {
            $bills->push(Bill::create(array_merge([
                'bill_batch_id' => $batch->id,
                'student_id' => $student->id,
                'name' => $name,
                'description' => "Bill {$number}",
                'semester' => 'Ganjil 2026/2027',
                'amount' => 350000,
                'billing_period' => '2026-09-01',
                'due_date' => '2026-10-05',
                'status' => 'unpaid',
            ], $billOverrides)));
        }
    }

    return [$batch, $bills];
}

test('default command previews legacy names without changing data', function () {
    [$batch, $bills] = createLegacyNameBatch($this->student, billCount: 2);

    $this->artisan('bills:normalize-legacy-names')
        ->expectsTable([
            'Batch ID',
            'Bill IDs',
            'Legacy Name',
            'New Name',
            'Billing Period',
            'Status',
        ], [[
            $batch->id,
            $bills->pluck('id')->implode(', '),
            'SPP September 2026',
            'SPP Bulanan',
            '2026-09-01',
            'VALID',
        ]])
        ->expectsOutputToContain('DRY-RUN')
        ->assertSuccessful();

    expect($batch->fresh()->name)->toBe('SPP September 2026')
        ->and($bills->every(fn (Bill $bill): bool => $bill->fresh()->name === 'SPP September 2026'))->toBeTrue();
});

test('apply normalizes batch and every child while preserving protected data', function () {
    [$batch, $bills] = createLegacyNameBatch(
        $this->student,
        'SPP Juli 2026',
        batchOverrides: ['billing_period' => '2026-07-01'],
        billOverrides: ['billing_period' => '2026-07-01'],
        billCount: 2,
    );
    $bill = $bills->first();
    $paymentMethod = PaymentMethod::create([
        'name' => 'Bank Transfer',
        'type' => 'bank_transfer',
        'code' => 'NORMALIZE-LEGACY',
        'provider' => 'Test Bank',
        'is_active' => true,
    ]);
    $payment = Payment::create([
        'bill_id' => $bill->id,
        'payer_id' => $this->student->user_id,
        'payment_method_id' => $paymentMethod->id,
        'payment_number' => 'PAY-NORMALIZE-LEGACY',
        'amount' => $bill->amount,
        'status' => 'paid',
        'paid_at' => '2026-07-03 08:00:00',
    ]);
    $admin = User::factory()->create(['role' => 'admin']);
    $verification = PaymentVerification::create([
        'payment_id' => $payment->id,
        'admin_id' => $admin->id,
        'status' => 'verified',
        'note' => 'Valid',
        'verified_at' => '2026-07-03 09:00:00',
        'processed_at' => '2026-07-03 09:00:00',
    ]);
    $batchSnapshot = [
        'updated_at' => $batch->updated_at->toDateTimeString(),
        'semester' => $batch->semester,
        'amount' => $batch->amount,
        'target_type' => $batch->target_type,
        'target_value' => $batch->target_value,
    ];
    $billSnapshots = $bills->mapWithKeys(
        fn (Bill $child): array => [$child->id => [
            'updated_at' => $child->updated_at->toDateTimeString(),
            'semester' => $child->semester,
            'amount' => $child->amount,
            'status' => $child->status,
        ]]
    );
    $paymentSnapshot = (array) DB::table((new Payment)->getTable())
        ->where('id', $payment->id)
        ->first();
    $verificationSnapshot = (array) DB::table((new PaymentVerification)->getTable())
        ->where('id', $verification->id)
        ->first();

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])
        ->expectsOutputToContain('APPLIED')
        ->assertSuccessful();

    $freshBatch = $batch->fresh();

    expect($freshBatch->name)->toBe('SPP Bulanan')
        ->and($freshBatch->updated_at->toDateTimeString())->toBe($batchSnapshot['updated_at'])
        ->and($freshBatch->billing_period->toDateString())->toBe('2026-07-01')
        ->and($freshBatch->due_date->toDateString())->toBe('2026-10-05')
        ->and($freshBatch->semester)->toBe($batchSnapshot['semester'])
        ->and($freshBatch->amount)->toBe($batchSnapshot['amount'])
        ->and($freshBatch->target_type)->toBe($batchSnapshot['target_type'])
        ->and($freshBatch->target_value)->toBe($batchSnapshot['target_value']);

    foreach ($bills as $child) {
        $freshChild = $child->fresh();
        $snapshot = $billSnapshots[$child->id];

        expect($freshChild->name)->toBe('SPP Bulanan')
            ->and($freshChild->updated_at->toDateTimeString())->toBe($snapshot['updated_at'])
            ->and($freshChild->billing_period->toDateString())->toBe('2026-07-01')
            ->and($freshChild->due_date->toDateString())->toBe('2026-10-05')
            ->and($freshChild->semester)->toBe($snapshot['semester'])
            ->and($freshChild->amount)->toBe($snapshot['amount'])
            ->and($freshChild->status)->toBe($snapshot['status']);
    }

    $freshPayment = (array) DB::table((new Payment)->getTable())
        ->where('id', $payment->id)
        ->first();
    $freshVerification = (array) DB::table((new PaymentVerification)->getTable())
        ->where('id', $verification->id)
        ->first();

    expect($freshPayment)->toBe($paymentSnapshot)
        ->and($freshVerification)->toBe($verificationSnapshot);
});

test('only explicitly mapped names are normalized', function () {
    [$allowedBatch, $allowedBills] = createLegacyNameBatch($this->student, 'SPP Agustus 2026');
    [$unknownBatch, $unknownBills] = createLegacyNameBatch($this->student, 'SPP Desember 2026');
    [$nonSppBatch, $nonSppBills] = createLegacyNameBatch($this->student, 'Kegiatan Desember 2026');

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])->assertSuccessful();

    expect($allowedBatch->fresh()->name)->toBe('SPP Bulanan')
        ->and($allowedBills->first()->fresh()->name)->toBe('SPP Bulanan')
        ->and($unknownBatch->fresh()->name)->toBe('SPP Desember 2026')
        ->and($unknownBills->first()->fresh()->name)->toBe('SPP Desember 2026')
        ->and($nonSppBatch->fresh()->name)->toBe('Kegiatan Desember 2026')
        ->and($nonSppBills->first()->fresh()->name)->toBe('Kegiatan Desember 2026');
});

test('child name mismatch rejects the whole batch', function () {
    [$batch, $bills] = createLegacyNameBatch(
        $this->student,
        billOverrides: ['name' => 'SPP Agustus 2026'],
        billCount: 2,
    );

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])
        ->expectsOutputToContain('tidak sama dengan batch')
        ->assertFailed();

    expect($batch->fresh()->name)->toBe('SPP September 2026')
        ->and($bills->every(fn (Bill $bill): bool => $bill->fresh()->name === 'SPP Agustus 2026'))->toBeTrue();
});

test('child billing period mismatch rejects the whole batch', function () {
    [$batch, $bills] = createLegacyNameBatch(
        $this->student,
        billOverrides: ['billing_period' => '2026-10-01'],
        billCount: 2,
    );

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])
        ->expectsOutputToContain('Billing period Bill ID')
        ->assertFailed();

    expect($batch->fresh()->name)->toBe('SPP September 2026')
        ->and($bills->every(fn (Bill $bill): bool => $bill->fresh()->name === 'SPP September 2026'))->toBeTrue();
});

test('missing batch billing period and empty batch are rejected', function (array $batchOverrides, int $billCount, string $message) {
    [$batch] = createLegacyNameBatch(
        $this->student,
        batchOverrides: $batchOverrides,
        billCount: $billCount,
    );

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect($batch->fresh()->name)->toBe('SPP September 2026');
})->with([
    'missing billing period' => [['billing_period' => null], 1, 'Billing period batch belum terisi'],
    'empty batch' => [[], 0, 'tidak memiliki Bill turunan'],
]);

test('transaction rolls back the batch name when child update fails', function () {
    [$batch, $bills] = createLegacyNameBatch($this->student, billCount: 2);

    DB::unprepared("CREATE TRIGGER fail_legacy_bill_name_update
        BEFORE UPDATE OF name ON bills
        WHEN NEW.bill_batch_id = {$batch->id}
        BEGIN
            SELECT RAISE(ABORT, 'Simulasi kegagalan child Bill');
        END");

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])
        ->expectsOutputToContain('Simulasi kegagalan child Bill')
        ->assertFailed();

    expect($batch->fresh()->name)->toBe('SPP September 2026')
        ->and($bills->every(fn (Bill $bill): bool => $bill->fresh()->name === 'SPP September 2026'))->toBeTrue();
});

test('command can be rerun safely after names are normalized', function () {
    [$batch, $bills] = createLegacyNameBatch($this->student);

    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])->assertSuccessful();
    $this->artisan('bills:normalize-legacy-names', ['--apply' => true])
        ->expectsOutputToContain('Tidak ada nama legacy SPP')
        ->assertSuccessful();

    expect($batch->fresh()->name)->toBe('SPP Bulanan')
        ->and($bills->first()->fresh()->name)->toBe('SPP Bulanan');
});
