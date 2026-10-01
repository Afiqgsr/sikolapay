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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->guardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $this->guardian = Guardian::create([
        'user_id' => $this->guardianUser->id,
        'name' => 'Wali Murid Test',
        'phone' => '081234567890',
    ]);

    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $school = School::create([
        'name' => 'SMK SikolaPay',
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
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-STU-001',
        'name' => 'Siswa Test',
        'gender' => 'L',
    ]);

    $this->paymentMethod = PaymentMethod::create([
        'name' => 'Transfer Bank BNI',
        'code' => 'bni_va',
        'type' => 'bank_transfer',
        'provider' => 'BNI',
        'account_number' => '1234567890',
        'account_name' => 'SMK SikolaPay',
        'is_active' => true,
    ]);
});

test('student payment history loads successfully without filters', function () {
    $billSpp = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Bulanan',
        'amount' => 250000,
        'billing_period' => '2026-07-01',
        'status' => 'paid',
    ]);

    $paymentSpp = Payment::create([
        'bill_id' => $billSpp->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-SPP-001',
        'amount' => 250000,
        'status' => 'paid',
        'paid_at' => Carbon::create(2026, 7, 10, 10, 0, 0),
        'created_at' => Carbon::create(2026, 7, 10, 9, 0, 0),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment-history'));

    $response->assertOk()
        ->assertViewIs('student.payment-history')
        ->assertSee('PAY-SPP-001')
        ->assertSee('SPP Bulanan')
        ->assertSee('Semua Jenis');

    foreach (Bill::TYPES as $type) {
        $response->assertSee($type);
    }
});

test('student payment history filters correctly by bill type using Bill::TYPES without sql error', function () {
    $billUjian = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Uang Ujian',
        'amount' => 150000,
        'billing_period' => '2026-08-01',
        'status' => 'paid',
    ]);

    $paymentUjian = Payment::create([
        'bill_id' => $billUjian->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-UJIAN-001',
        'amount' => 150000,
        'status' => 'paid',
        'paid_at' => Carbon::create(2026, 8, 5, 10, 0, 0),
        'created_at' => Carbon::create(2026, 8, 5, 9, 0, 0),
    ]);

    $billGedung = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Uang Gedung',
        'amount' => 500000,
        'billing_period' => '2026-08-01',
        'status' => 'paid',
    ]);

    $paymentGedung = Payment::create([
        'bill_id' => $billGedung->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-GEDUNG-001',
        'amount' => 500000,
        'status' => 'paid',
        'paid_at' => Carbon::create(2026, 8, 6, 10, 0, 0),
        'created_at' => Carbon::create(2026, 8, 6, 9, 0, 0),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment-history', ['type' => 'Uang Ujian']));

    $response->assertOk()
        ->assertSee('PAY-UJIAN-001')
        ->assertSee('Uang Ujian')
        ->assertDontSee('PAY-GEDUNG-001');
});

test('student payment history filters correctly with type, status, and year combined', function () {
    $billUjian2026 = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Uang Ujian',
        'amount' => 150000,
        'billing_period' => '2026-09-01',
        'status' => 'unpaid',
    ]);

    $paymentPending2026 = Payment::create([
        'bill_id' => $billUjian2026->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-COMBINED-PEND-2026',
        'amount' => 150000,
        'status' => 'pending',
        'created_at' => Carbon::create(2026, 9, 10, 8, 0, 0),
    ]);

    $billUjian2025 = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'Uang Ujian',
        'amount' => 150000,
        'billing_period' => '2025-09-01',
        'status' => 'unpaid',
    ]);

    $paymentPending2025 = Payment::create([
        'bill_id' => $billUjian2025->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-COMBINED-PEND-2025',
        'amount' => 150000,
        'status' => 'pending',
    ]);
    DB::table('payments')
        ->where('id', $paymentPending2025->id)
        ->update(['created_at' => '2025-09-10 08:00:00']);

    $billSpp2026 = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Bulanan',
        'amount' => 250000,
        'billing_period' => '2026-09-01',
        'status' => 'unpaid',
    ]);

    $paymentSppPending2026 = Payment::create([
        'bill_id' => $billSpp2026->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-SPP-PEND-2026',
        'amount' => 250000,
        'status' => 'pending',
        'created_at' => Carbon::create(2026, 9, 10, 8, 0, 0),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment-history', [
            'type' => 'Uang Ujian',
            'status' => 'pending',
            'year' => '2026',
        ]));

    $response->assertOk()
        ->assertSee('PAY-COMBINED-PEND-2026')
        ->assertDontSee('PAY-COMBINED-PEND-2025')
        ->assertDontSee('PAY-SPP-PEND-2026');
});

test('student payment history handles invalid type safely without sql error', function () {
    $bill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Bulanan',
        'amount' => 250000,
        'billing_period' => '2026-07-01',
        'status' => 'paid',
    ]);

    Payment::create([
        'bill_id' => $bill->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-VALID-001',
        'amount' => 250000,
        'status' => 'paid',
        'paid_at' => Carbon::create(2026, 7, 10, 10, 0, 0),
        'created_at' => Carbon::create(2026, 7, 10, 9, 0, 0),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->get(route('student.payment-history', ['type' => 'invalid_type_xyz']));

    $response->assertOk()
        ->assertDontSee('PAY-VALID-001');
});
