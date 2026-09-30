<?php

/**
 * @property Student $student
 * @property Student $otherStudent
 * @property Bill $bill
 * @property Bill $otherBill
 * @property PaymentMethod $paymentMethod
 */

use App\Actions\Payments\CreateStudentBatchPaymentsAction;
use App\Actions\Payments\CreateStudentPaymentAction;
use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentVerification;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $guardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $guardian = Guardian::create([
        'user_id' => $guardianUser->id,
        'name' => 'Test Guardian',
        'phone' => '08123456789',
    ]);

    $studentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $school = School::create([
        'name' => 'Test School',
    ]);

    $academicYear = AcademicYear::create([
        'school_id' => $school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);

    $classRoom = ClassRoom::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'X-A',
        'grade' => '10',
    ]);

    $this->student = Student::create([
        'user_id' => $studentUser->id,
        'guardian_id' => $guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-001',
        'name' => 'Test Student',
        'gender' => 'L',
    ]);

    $otherStudentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'guardian_id' => $guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-002',
        'name' => 'Other Student',
        'gender' => 'P',
    ]);

    $this->bill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Tuition',
        'amount' => 150000,
        'billing_period' => now()->startOfMonth()->toDateString(),
        'status' => 'unpaid',
    ]);

    $this->secondBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Uniform',
        'amount' => 100000,
        'billing_period' => now()->startOfMonth()->toDateString(),
        'status' => 'unpaid',
    ]);

    $this->otherBill = Bill::create([
        'student_id' => $this->otherStudent->id,
        'name' => 'Other Tuition',
        'amount' => 200000,
        'billing_period' => now()->startOfMonth()->toDateString(),
        'status' => 'unpaid',
    ]);

    $this->paymentMethod = PaymentMethod::create([
        'name' => 'Bank Transfer',
        'type' => 'bank_transfer',
        'code' => 'TEST-BANK',
        'provider' => 'Test Bank',
        'is_active' => true,
    ]);
});

test('student can create a pending payment for their own bill', function () {
    $result = app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
        $this->student->user_id
    );

    $payment = $result['payment'];

    expect($payment->bill_id)->toBe($this->bill->id)
        ->and($payment->payer_id)->toBe($this->student->user_id)
        ->and($payment->payment_method_id)->toBe($this->paymentMethod->id)
        ->and($payment->status)->toBe('pending')
        ->and((int) $payment->amount)->toBe((int) $this->bill->amount);
});

test('student cannot create a pending payment for another student bill', function () {
    expect(fn () => app(CreateStudentPaymentAction::class)->execute(
        $this->otherBill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
        $this->student->user_id
    ))->toThrow(ModelNotFoundException::class);
});

test('student cannot create duplicate pending payment for the same bill', function () {
    Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->student->user_id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-DUPLICATE-001',
        'amount' => $this->bill->amount,
        'proof_of_payment' => 'payments/proof.jpg',
        'proof_uploaded_at' => now(),
        'status' => 'pending',
    ]);

    expect(fn () => app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
        $this->student->user_id
    ))->toThrow(HttpException::class, 'Pembayaran masih menunggu verifikasi.');
});

test('student can create pending payments for all unpaid eligible bills', function () {
    $result = app(CreateStudentBatchPaymentsAction::class)->execute(
        $this->student->id,
        $this->paymentMethod->id,
        'payments/batch-proof.jpg',
        $this->student->user_id
    );

    $payments = $result['payments'];

    expect($payments)->toHaveCount(2)
        ->and($payments[0]->bill_id)->toBe($this->bill->id)
        ->and($payments[1]->bill_id)->toBe($this->secondBill->id);
});

test('student single payment follows billing period instead of due date or created date', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 12, 0, 0, (string) config('app.timezone')));

    $this->bill->update([
        'billing_period' => '2026-09-01',
        'due_date' => '2026-10-05',
    ]);
    $this->bill->forceFill(['created_at' => '2026-08-15 08:00:00'])->saveQuietly();

    $result = app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
        $this->student->user_id
    );

    expect($result['payment'])->toBeInstanceOf(Payment::class)
        ->and($result['is_resubmission'])->toBeFalse();
});

test('student single payment rejects a future or missing billing period', function (?string $billingPeriod) {
    $this->travelTo(Carbon::create(2026, 9, 30, 12, 0, 0, (string) config('app.timezone')));

    $this->bill->update([
        'billing_period' => $billingPeriod,
        'due_date' => '2026-09-20',
    ]);
    $this->bill->forceFill(['created_at' => '2026-08-15 08:00:00'])->saveQuietly();
    Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->student->user_id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-FAILED-FUTURE',
        'amount' => $this->bill->amount,
        'status' => 'failed',
        'paid_at' => '2026-08-20 08:00:00',
    ]);

    expect(fn () => app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
        $this->student->user_id
    ))->toThrow(HttpException::class, 'Tagihan periode ini belum dapat dibayar.')
        ->and(Payment::where('bill_id', $this->bill->id)->count())->toBe(1);
})->with([
    'future period' => '2026-10-01',
    'missing period' => null,
]);

test('student can pay October bill when October starts', function () {
    $this->travelTo(Carbon::create(2026, 10, 1, 0, 0, 0, (string) config('app.timezone')));
    $this->bill->update(['billing_period' => '2026-10-01']);

    $result = app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
        $this->student->user_id
    );

    expect($result['payment'])->toBeInstanceOf(Payment::class);
});

test('student batch excludes future bills and creates no partial payment when none are eligible', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 12, 0, 0, (string) config('app.timezone')));
    $this->bill->update(['billing_period' => '2026-09-01']);
    $this->secondBill->update(['billing_period' => '2026-10-01']);

    $result = app(CreateStudentBatchPaymentsAction::class)->execute(
        $this->student->id,
        $this->paymentMethod->id,
        'payments/batch-proof.jpg',
        $this->student->user_id
    );

    expect($result['payments'])->toHaveCount(1)
        ->and($result['payments']->first()->bill_id)->toBe($this->bill->id)
        ->and(Payment::where('bill_id', $this->secondBill->id)->doesntExist())->toBeTrue();

    expect(fn () => app(CreateStudentBatchPaymentsAction::class)->execute(
        $this->student->id,
        $this->paymentMethod->id,
        'payments/batch-proof-2.jpg',
        $this->student->user_id
    ))->toThrow(HttpException::class, 'Tidak ada tagihan yang dapat dibayar.')
        ->and(Payment::query()->count())->toBe(1);
});

test('student rejected payment resubmission remains available for an existing payment', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 12, 0, 0, (string) config('app.timezone')));
    $this->bill->update(['billing_period' => '2026-10-01']);
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->student->user_id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-REJECTED-RESUBMIT',
        'amount' => $this->bill->amount,
        'proof_of_payment' => 'payments/old-proof.jpg',
        'proof_uploaded_at' => now()->subDay(),
        'status' => 'pending',
    ]);
    PaymentVerification::create([
        'payment_id' => $payment->id,
        'admin_id' => User::factory()->create(['role' => 'admin'])->id,
        'status' => 'rejected',
        'note' => 'Bukti tidak jelas.',
        'processed_at' => now(),
    ]);

    $result = app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->id,
        $this->paymentMethod->id,
        'payments/new-proof.jpg',
        $this->student->user_id
    );

    expect($result['is_resubmission'])->toBeTrue()
        ->and($result['payment']->id)->toBe($payment->id)
        ->and($result['payment']->proof_of_payment)->toBe('payments/new-proof.jpg')
        ->and(Payment::where('bill_id', $this->bill->id)->count())->toBe(1);
});
