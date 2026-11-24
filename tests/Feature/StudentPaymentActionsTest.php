<?php

/**
 * @property \App\Models\Student $student
 * @property \App\Models\Student $otherStudent
 * @property \App\Models\Bill $bill
 * @property \App\Models\Bill $otherBill
 * @property \App\Models\PaymentMethod $paymentMethod
 */

use App\Actions\Payments\CreateStudentBatchPaymentsAction;
use App\Actions\Payments\CreateStudentPaymentAction;
use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
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

    $this->otherStudent = Student::create([
        'user_id' => User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ])->id,
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
        'status' => 'unpaid',
    ]);

    $this->otherBill = Bill::create([
        'student_id' => $this->otherStudent->id,
        'name' => 'Other Tuition',
        'amount' => 200000,
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
    $payment = app(CreateStudentPaymentAction::class)->execute(
        $this->bill,
        $this->student->user_id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
    );

    expect($payment->bill_id)->toBe($this->bill->id)
        ->and($payment->payer_id)->toBe($this->student->user_id)
        ->and($payment->payment_method_id)->toBe($this->paymentMethod->id)
        ->and($payment->status)->toBe('pending')
        ->and($payment->amount)->toBe((string) $this->bill->amount);
});

test('student cannot create a pending payment for another student bill', function () {
    expect(fn () => app(CreateStudentPaymentAction::class)->execute(
        $this->otherBill,
        $this->student->user_id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
    ))->toThrow(HttpException::class, 'Tagihan tidak ditemukan.');
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
        $this->student->user_id,
        $this->paymentMethod->id,
        'payments/proof.jpg',
    ))->toThrow(HttpException::class, 'Pembayaran masih menunggu verifikasi.');
});

test('student can create pending payments for all unpaid eligible bills', function () {
    $payments = app(CreateStudentBatchPaymentsAction::class)->execute(
        $this->student->id,
        $this->paymentMethod->id,
        'payments/batch-proof.jpg',
    );

    expect($payments)->toHaveCount(2)
        ->and($payments[0]->bill_id)->toBe($this->bill->id)
        ->and($payments[1]->bill_id)->toBe($this->otherBill->id);
});
