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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

test('admin cannot record a future period manual payment', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 12, 0, 0, (string) config('app.timezone')));
    $this->bill->update([
        'billing_period' => '2026-10-01',
        'due_date' => '2026-09-20',
    ]);

    expect(fn () => app(RecordManualPaymentAction::class)->execute([
        'student_id' => $this->student->id,
        'bill_id' => $this->bill->id,
        'payment_method_id' => $this->paymentMethod->id,
        'paid_at' => '2026-09-30 12:00:00',
    ], $this->admin->id))->toThrow(HttpException::class, 'Tagihan periode ini belum dapat dibayar.')
        ->and(Payment::query()->count())->toBe(0)
        ->and($this->bill->fresh()->status)->toBe('unpaid');
});

test('admin can record an October manual payment when October starts', function () {
    $this->travelTo(Carbon::create(2026, 10, 1, 0, 0, 0, (string) config('app.timezone')));
    $this->bill->update(['billing_period' => '2026-10-01']);

    $payment = app(RecordManualPaymentAction::class)->execute([
        'student_id' => $this->student->id,
        'bill_id' => $this->bill->id,
        'payment_method_id' => $this->paymentMethod->id,
        'paid_at' => '2026-10-01 00:00:00',
    ], $this->admin->id);

    expect($payment->status)->toBe('paid')
        ->and($this->bill->fresh()->status)->toBe('paid');
});

test('admin cannot backdate manual payment before the billing period', function () {
    $this->travelTo(Carbon::create(2026, 10, 2, 12, 0, 0, (string) config('app.timezone')));
    $this->bill->update(['billing_period' => '2026-10-01']);

    expect(fn () => app(RecordManualPaymentAction::class)->execute([
        'student_id' => $this->student->id,
        'bill_id' => $this->bill->id,
        'payment_method_id' => $this->paymentMethod->id,
        'paid_at' => '2026-09-29 12:00:00',
    ], $this->admin->id))->toThrow(
        HttpException::class,
        'Tanggal pembayaran tidak boleh lebih awal dari periode tagihan.'
    )
        ->and(Payment::query()->count())->toBe(0)
        ->and($this->bill->fresh()->status)->toBe('unpaid');
});

test('admin manual payment form excludes future billing periods', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 12, 0, 0, (string) config('app.timezone')));
    $this->bill->update(['billing_period' => '2026-09-01']);
    $futureBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Bulanan',
        'amount' => 200000,
        'billing_period' => '2026-10-01',
        'status' => 'unpaid',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.payments.create'));

    $response->assertOk()
        ->assertViewHas('bills', function ($bills) use ($futureBill): bool {
            return $bills->contains('id', $this->bill->id)
                && ! $bills->contains('id', $futureBill->id);
        });
});
