<?php

use App\Actions\Payments\RecordManualPaymentAction;
use App\Actions\Payments\RejectPaymentAction;
use App\Actions\Payments\VerifyPaymentAction;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->studentUser = User::factory()->create([
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

    $guardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $guardian = Guardian::create([
        'user_id' => $guardianUser->id,
        'name' => 'Test Guardian',
        'phone' => '08123456789',
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'guardian_id' => $guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-001',
        'name' => 'Test Student',
        'gender' => 'L',
    ]);

    $this->bill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Tuition',
        'amount' => 100000,
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

test('verify payment locks the payment state and records the admin audit', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-VERIFY-001',
        'amount' => $this->bill->amount,
        'proof_of_payment' => 'payments/proof.jpg',
        'proof_uploaded_at' => now(),
        'status' => 'pending',
    ]);

    $verifiedPayment = app(VerifyPaymentAction::class)->execute(
        $payment->id,
        $this->admin->id,
    );

    expect($verifiedPayment->fresh()->status)->toBe('paid')
        ->and($this->bill->fresh()->status)->toBe('paid')
        ->and(PaymentVerification::query()->first()->admin_id)
        ->toBe($this->admin->id);

    expect(fn () => app(VerifyPaymentAction::class)->execute(
        $payment->id,
        User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ])->id,
    ))->toThrow(ModelNotFoundException::class);

    expect(PaymentVerification::query()->count())->toBe(1);
});

test('reject payment preserves pending status and records the rejection audit', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-REJECT-001',
        'amount' => $this->bill->amount,
        'proof_of_payment' => 'payments/proof.jpg',
        'proof_uploaded_at' => now(),
        'status' => 'pending',
    ]);

    app(RejectPaymentAction::class)->execute(
        $payment->id,
        $this->admin->id,
        'Bukti tidak jelas.',
    );

    $verification = PaymentVerification::query()->first();

    expect($payment->fresh()->status)->toBe('pending')
        ->and($verification->status)->toBe('rejected')
        ->and($verification->note)->toBe('Bukti tidak jelas.')
        ->and($verification->admin_id)->toBe($this->admin->id);
});

test('manual payment marks the bill paid and records the admin', function () {
    $payment = app(RecordManualPaymentAction::class)->execute([
        'student_id' => $this->student->id,
        'bill_id' => $this->bill->id,
        'payment_method_id' => $this->paymentMethod->id,
        'paid_at' => now()->toDateTimeString(),
    ], $this->admin->id);

    $verification = PaymentVerification::query()->first();

    expect($payment->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->payer_id)->toBe($this->studentUser->id)
        ->and($payment->fresh()->recorded_by)->toBe($this->admin->id)
        ->and($this->bill->fresh()->status)->toBe('paid')
        ->and($verification->status)->toBe('verified')
        ->and($verification->admin_id)->toBe($this->admin->id);
});
