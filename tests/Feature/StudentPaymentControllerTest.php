<?php

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->guardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $guardian = Guardian::create([
        'user_id' => $this->guardianUser->id,
        'name' => 'Wali Murid Test',
        'phone' => '081234567890',
    ]);

    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $school = School::create([
        'name' => 'SMK Test',
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
        'name' => 'X RPL 1',
        'grade' => '10',
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'guardian_id' => $guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-S-001',
        'name' => 'Siswa Test',
        'gender' => 'L',
    ]);

    $this->bill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Juli 2026',
        'amount' => 150000,
        'status' => 'unpaid',
    ]);

    $this->paymentMethod = PaymentMethod::create([
        'name' => 'Transfer BCA',
        'type' => 'bank_transfer',
        'code' => 'BCA-STUDENT',
        'provider' => 'BCA',
        'is_active' => true,
    ]);
});

test('student can view create payment page for their own bill', function () {
    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment', $this->bill->id));

    $response->assertOk()
        ->assertViewIs('student.payment')
        ->assertViewHas(['student', 'bill', 'paymentMethods']);
});

test('student can confirm single payment and receives json response', function () {
    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->actingAs($this->studentUser)
        ->postJson(route('student.payment.confirm', $this->bill->id), [
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Pembayaran berhasil dikonfirmasi.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'payment' => [
                'id',
                'payment_number',
                'date',
                'proof',
            ],
        ]);

    $payment = Payment::where('bill_id', $this->bill->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->payer_id)->toBe($this->studentUser->id)
        ->and($payment->status)->toBe('pending');

    Storage::disk('public')->assertExists($payment->proof_of_payment);
});

test('student can view payment all page when unpaid bills exist', function () {
    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment.all'));

    $response->assertOk()
        ->assertViewIs('student.payment-all')
        ->assertViewHas(['student', 'unpaidBills', 'total']);
});

test('student can view payment all confirm page when unpaid bills exist', function () {
    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment.all.confirm'));

    $response->assertOk()
        ->assertViewIs('student.payment-all-confirm')
        ->assertViewHas(['student', 'unpaidBills', 'total', 'paymentMethods']);
});

test('student can confirm batch payment and receives json response', function () {
    $file = UploadedFile::fake()->image('batch_proof.jpg');

    $response = $this->actingAs($this->studentUser)
        ->postJson(route('student.payment.all.confirm.store'), [
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Pembayaran semua tagihan berhasil dikonfirmasi.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'payment' => [
                'payment_number',
                'count',
                'total',
                'date',
            ],
        ]);

    $payment = Payment::where('bill_id', $this->bill->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->payer_id)->toBe($this->studentUser->id)
        ->and($payment->status)->toBe('pending');
});
