<?php

use App\Actions\Admin\Reports\BuildStudentBillRecapAction;
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

beforeEach(function () {
    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Admin Keuangan',
    ]);

    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->guardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $this->guardian = Guardian::create([
        'user_id' => $this->guardianUser->id,
        'name' => 'Wali Murid Test',
        'phone' => '081234567890',
    ]);

    $this->school = School::create([
        'name' => 'SMK SikolaPay',
        'npsn' => '12345678',
        'address' => 'Jl. Pendidikan No. 1',
        'phone' => '081234567890',
        'email' => 'sekolah@sikolapay.test',
    ]);

    $this->academicYear = AcademicYear::create([
        'school_id' => $this->school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);

    $this->paymentMethod = PaymentMethod::create([
        'name' => 'Transfer Bank BNI',
        'code' => 'bni_va',
        'type' => 'bank_transfer',
        'provider' => 'bni',
        'is_active' => true,
    ]);
});

function createStudentReportBill(array $attributes): Bill
{
    $dueDate = $attributes['due_date'] ?? null;

    if (! array_key_exists('billing_period', $attributes) && is_string($dueDate)) {
        $attributes['billing_period'] = Carbon::parse($dueDate)->startOfMonth()->toDateString();
    }

    return Bill::create($attributes);
}

// 1. Admin dapat membuka Tab Rekap per Siswa
test('admin can access student recap tab', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', ['tab' => 'student_recap']));

    $response->assertOk();
    $response->assertViewIs('admin.payment-report');
    $response->assertSee('Rekap per Siswa');
    $response->assertSee('Cari Siswa / NIS');
});

// 2. Student & Guardian tidak dapat mengakses Tab Rekap Siswa (403)
test('non admin roles cannot access student recap', function () {
    $this->actingAs($this->studentUser)->get(route('admin.reports.index', ['tab' => 'student_recap']))->assertForbidden();
    $this->actingAs($this->guardianUser)->get(route('admin.reports.index', ['tab' => 'student_recap']))->assertForbidden();
});

// 3. Guest diarahkan ke login
test('guest is redirected when accessing student recap', function () {
    $this->get(route('admin.reports.index', ['tab' => 'student_recap']))->assertRedirect(route('login'));
});

// 4. Tab Rekap Siswa menampilkan siswa unik meskipun memiliki multiple bills
test('student appears only once in recap student list even with multiple bills', function () {
    $classRoom = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X RPL 1',
        'grade' => 'X',
    ]);

    $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create([
        'user_id' => $user->id,
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-MULTI-01',
        'name' => 'Ahmad Fajar',
        'gender' => 'L',
    ]);

    // Buat 3 tagihan berbeda di bulan September 2026 untuk siswa yang sama
    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'Kegiatan',
        'amount' => 150000,
        'due_date' => '2026-09-15',
        'status' => 'unpaid',
    ]);

    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'Seragam',
        'amount' => 300000,
        'due_date' => '2026-09-20',
        'status' => 'unpaid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'student_recap',
        'month' => 9,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertSee('Ahmad Fajar');
    $response->assertSee('NIS-MULTI-01');
    $response->assertSee('X RPL 1');
    $response->assertSee('1 siswa ditemukan');
});

// 5. Filter "Semua Jenis Tagihan" menyertakan seluruh jenis tagihan pada periode tersebut
test('all bill types option includes any bill type in period', function () {
    $classRoom = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);

    // Siswa A punya SPP Bulanan
    $uA = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $sA = Student::create(['user_id' => $uA->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-A', 'name' => 'Siswa SPP', 'gender' => 'L']);
    createStudentReportBill(['student_id' => $sA->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    // Siswa B punya Uang Gedung
    $uB = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $sB = Student::create(['user_id' => $uB->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-B', 'name' => 'Siswa Gedung', 'gender' => 'L']);
    createStudentReportBill(['student_id' => $sB->id, 'name' => 'Uang Gedung', 'amount' => 2000000, 'due_date' => '2026-09-10', 'status' => 'unpaid']);

    // Siswa C punya SPP tapi di bulan Oktober (tidak boleh masuk)
    $uC = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $sC = Student::create(['user_id' => $uC->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-C', 'name' => 'Siswa Oktober', 'gender' => 'L']);
    createStudentReportBill(['student_id' => $sC->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-10-10', 'status' => 'paid']);

    // Request dengan bill_name = all
    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'student_recap',
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'all',
    ]));

    $response->assertOk();
    $response->assertSee('Siswa SPP');
    $response->assertSee('Siswa Gedung');
    $response->assertDontSee('Siswa Oktober');
    $response->assertSee('2 siswa ditemukan');
});

// 6. Filter jenis tagihan spesifik hanya menyertakan siswa yang memiliki jenis tagihan tersebut
test('specific bill type filter filters correctly', function () {
    $classRoom = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);

    // Siswa A punya SPP Bulanan
    $uA = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $sA = Student::create(['user_id' => $uA->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-A', 'name' => 'Siswa SPP', 'gender' => 'L']);
    createStudentReportBill(['student_id' => $sA->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    // Siswa B punya Uang Gedung
    $uB = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $sB = Student::create(['user_id' => $uB->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-B', 'name' => 'Siswa Gedung', 'gender' => 'L']);
    createStudentReportBill(['student_id' => $sB->id, 'name' => 'Uang Gedung', 'amount' => 2000000, 'due_date' => '2026-09-10', 'status' => 'unpaid']);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'student_recap',
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
    ]));

    $response->assertOk();
    $response->assertSee('Siswa SPP');
    $response->assertDontSee('Siswa Gedung');
    $response->assertSee('1 siswa ditemukan');
});

// 7. Search siswa berdasarkan nama atau NIS bekerja
test('search filters students by name or nis', function () {
    $classRoom = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);

    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s1 = Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => '1001', 'name' => 'Budi Santoso', 'gender' => 'L']);
    createStudentReportBill(['student_id' => $s1->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    $u2 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s2 = Student::create(['user_id' => $u2->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => '1002', 'name' => 'Citra Lestari', 'gender' => 'P']);
    createStudentReportBill(['student_id' => $s2->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    // Cari by nama 'Citra'
    $responseName = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'student_recap',
        'month' => 9,
        'year' => 2026,
        'search' => 'Citra',
    ]));
    $responseName->assertOk()->assertSee('Citra Lestari')->assertDontSee('Budi Santoso');

    // Cari by NIS '1001'
    $responseNis = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'student_recap',
        'month' => 9,
        'year' => 2026,
        'search' => '1001',
    ]));
    $responseNis->assertOk()->assertSee('Budi Santoso')->assertDontSee('Citra Lestari');
});

// 8. BuildStudentBillRecapAction menghitung Total Tagihan, Total Lunas, Sisa Belum Lunas dengan benar
test('build student bill recap action computes amounts and statuses correctly', function () {
    $classRoom = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create(['user_id' => $user->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-CALC', 'name' => 'Siswa Kalkulasi', 'gender' => 'L']);

    // 1. Bill Lunas: 500.000
    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    // 2. Bill Pending (sudah upload bukti): 300.000 -> status pending, dihitung belum lunas
    $pendingBill = createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'Kegiatan',
        'amount' => 300000,
        'due_date' => '2026-09-15',
        'status' => 'unpaid',
    ]);
    Payment::create([
        'bill_id' => $pendingBill->id,
        'payer_id' => $user->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-PND-01',
        'amount' => 300000,
        'status' => 'pending',
        'proof_of_payment' => 'proof.jpg',
        'proof_uploaded_at' => now(),
    ]);

    // 3. Bill Belum Lunas (unpaid murni): 200.000
    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'Seragam',
        'amount' => 200000,
        'due_date' => '2026-09-20',
        'status' => 'unpaid',
    ]);

    $action = app(BuildStudentBillRecapAction::class);
    $result = $action->execute($student, 9, 2026, 'all');

    expect($result['has_data'])->toBeTrue()
        ->and($result['summary']['total_amount'])->toBe(1000000.0)
        ->and($result['summary']['total_paid'])->toBe(500000.0)
        ->and($result['summary']['total_unpaid'])->toBe(500000.0)
        ->and($result['summary']['bill_count'])->toBe(3)
        ->and($result['summary']['paid_count'])->toBe(1)
        ->and($result['summary']['unpaid_count'])->toBe(2)
        ->and($result['bills'][0]['status'])->toBe('paid')
        ->and($result['bills'][1]['status'])->toBe('pending')
        ->and($result['bills'][2]['status'])->toBe('unpaid');
});

// 9. Preview Laporan Siswa menampilkan data lengkap
test('student preview page returns ok with complete details', function () {
    $classRoom = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create(['user_id' => $user->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-PRV-01', 'name' => 'Dewi Anggraini', 'gender' => 'P']);

    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'SPP Bulanan',
        'amount' => 750000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.student.preview', [
        'student' => $student->id,
        'month' => 9,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertViewIs('admin.reports.student-preview');
    $response->assertSee('Dewi Anggraini');
    $response->assertSee('NIS-PRV-01');
    $response->assertSee('X RPL 1');
    $response->assertSee('September 2026');
    $response->assertSee('SPP Bulanan');
    $response->assertSee('Rp 750.000');
    $response->assertSee('Admin Keuangan');
});

// 10. Export CSV Siswa menghasilkan struktur CSV valid
test('student export excel csv streams valid format and amounts', function () {
    $classRoom = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create(['user_id' => $user->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classRoom->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-EXCEL', 'name' => 'Rian Pratama', 'gender' => 'L']);

    createStudentReportBill([
        'student_id' => $student->id,
        'name' => 'SPP Bulanan',
        'amount' => 600000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.student.export-excel', [
        'student' => $student->id,
        'month' => 9,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('LAPORAN REKAPITULASI TAGIHAN SISWA')
        ->and($content)->toContain('Rian Pratama')
        ->and($content)->toContain('NIS-EXCEL')
        ->and($content)->toContain('September 2026')
        ->and($content)->toContain('SPP Bulanan')
        ->and($content)->toContain('600000')
        ->and($content)->toContain('RINGKASAN REKAPITULASI');
});
