<?php

use App\Actions\Reports\BuildClassPaymentRecapAction;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

function createPaymentReportBill(array $attributes): Bill
{
    $dueDate = $attributes['due_date'] ?? null;

    if (! array_key_exists('billing_period', $attributes) && is_string($dueDate)) {
        $attributes['billing_period'] = Carbon::parse($dueDate)->startOfMonth()->toDateString();
    }

    return Bill::create($attributes);
}

// 1. Admin dapat membuka halaman laporan
test('admin can access report page', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertViewIs('admin.payment-report');
    $response->assertSee('Laporan Pembayaran');
    $response->assertSee('Rincian Transaksi');
    $response->assertSee('Rekap per Kelas');
});

// 2. Student tidak dapat mengakses laporan Admin (403)
test('student cannot access admin report', function () {
    $response = $this->actingAs($this->studentUser)->get(route('admin.reports.index'));

    $response->assertForbidden();
});

// 3. Guardian tidak dapat mengakses laporan Admin (403)
test('guardian cannot access admin report', function () {
    $response = $this->actingAs($this->guardianUser)->get(route('admin.reports.index'));

    $response->assertForbidden();
});

// 4. Guest diarahkan ke login
test('guest is redirected to login when accessing admin report', function () {
    $response = $this->get(route('admin.reports.index'));

    $response->assertRedirect(route('login'));
});

// 5. Fitur Rincian Transaksi existing tetap dapat digunakan
test('existing transaction details report works properly', function () {
    $classRoom = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X RPL 1',
        'grade' => 'X',
    ]);

    $student = Student::create([
        'user_id' => $this->studentUser->id,
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-001',
        'name' => 'Budi Santoso',
        'gender' => 'L',
    ]);

    $bill = createPaymentReportBill([
        'student_id' => $student->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    Payment::create([
        'bill_id' => $bill->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-001',
        'amount' => 500000,
        'status' => 'paid',
        'paid_at' => '2026-09-05',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', ['tab' => 'detail']));

    $response->assertOk();
    $response->assertSee('Budi Santoso');
    $response->assertSee('X RPL 1');
    $response->assertSee('SPP Bulanan');
    $response->assertSee('Rp 500.000');
});

test('transaction details and payment summary use bill billing period', function () {
    $classRoom = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X RPL 1',
        'grade' => 'X',
    ]);

    $createPaidBill = function (string $studentName, string $billingPeriod, string $paidAt, int $amount) use ($classRoom): Bill {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $user->id,
            'guardian_id' => $this->guardian->id,
            'class_room_id' => $classRoom->id,
            'entry_year' => 2026,
            'status' => 'active',
            'nis' => 'NIS-DETAIL-'.$amount,
            'name' => $studentName,
            'gender' => 'L',
        ]);
        $bill = createPaymentReportBill([
            'student_id' => $student->id,
            'name' => 'SPP Bulanan',
            'amount' => $amount,
            'billing_period' => $billingPeriod,
            'due_date' => '2026-10-05',
            'status' => 'paid',
        ]);

        Payment::create([
            'bill_id' => $bill->id,
            'payer_id' => $user->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_number' => 'PAY-DETAIL-'.$amount,
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => $paidAt,
        ]);

        return $bill;
    };

    $septemberBill = $createPaidBill('Siswa Periode September', '2026-09-01', '2026-10-03', 700000);
    $octoberBill = $createPaidBill('Siswa Periode Oktober', '2026-10-01', '2026-09-29', 900000);

    DB::table((new Bill)->getTable())->where('id', $septemberBill->id)->update(['created_at' => '2026-08-20 08:00:00']);
    DB::table((new Bill)->getTable())->where('id', $octoberBill->id)->update(['created_at' => '2026-09-20 08:00:00']);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'detail',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]));

    $response->assertOk()
        ->assertSee('Periode Tagihan Awal')
        ->assertSee('Siswa Periode September')
        ->assertDontSee('Siswa Periode Oktober')
        ->assertViewHas('totalIncome', 700000)
        ->assertViewHas('totalSuccessfulTransactions', 1);
});

test('class recap period depends only on billing period and current bill status', function () {
    $classRoom = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X RPL 1',
        'grade' => 'X',
    ]);

    $createBill = function (
        string $nis,
        string $name,
        int $amount,
        string $billingPeriod,
        string $dueDate,
        string $status = 'unpaid',
    ) use ($classRoom): Bill {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $user->id,
            'guardian_id' => $this->guardian->id,
            'class_room_id' => $classRoom->id,
            'entry_year' => 2026,
            'status' => 'active',
            'nis' => $nis,
            'name' => $nis,
            'gender' => 'L',
        ]);

        return createPaymentReportBill([
            'student_id' => $student->id,
            'name' => $name,
            'amount' => $amount,
            'billing_period' => $billingPeriod,
            'due_date' => $dueDate,
            'status' => $status,
        ]);
    };

    $septemberDueSeptember = $createBill('NIS-A', 'SPP Bulanan', 100000, '2026-09-01', '2026-09-10');
    $septemberDueOctober = $createBill('NIS-B', 'SPP Bulanan', 200000, '2026-09-01', '2026-10-05');
    $septemberPaidOctober = $createBill('NIS-C', 'SPP Bulanan', 300000, '2026-09-01', '2026-10-05', 'paid');
    $octoberBill = $createBill('NIS-D', 'SPP Bulanan', 400000, '2026-10-01', '2026-10-02', 'paid');
    $octoberCreatedSeptember = $createBill('NIS-E', 'SPP Bulanan', 500000, '2026-10-01', '2026-10-02', 'paid');
    $septemberCreatedAugust = $createBill('NIS-F', 'SPP Bulanan', 600000, '2026-09-01', '2026-10-05');
    $createBill('NIS-G', 'Uang Ujian', 700000, '2026-09-01', '2026-09-15', 'paid');

    Payment::create([
        'bill_id' => $septemberPaidOctober->id,
        'payer_id' => $septemberPaidOctober->student->user_id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-OCTOBER-FOR-SEPTEMBER',
        'amount' => 300000,
        'status' => 'paid',
        'paid_at' => '2026-10-03',
    ]);

    DB::table((new Bill)->getTable())->where('id', $octoberCreatedSeptember->id)->update(['created_at' => '2026-09-29 08:00:00']);
    DB::table((new Bill)->getTable())->where('id', $septemberCreatedAugust->id)->update(['created_at' => '2026-08-20 08:00:00']);

    $result = app(BuildClassPaymentRecapAction::class)->execute(9, 2026, 'SPP Bulanan');

    expect($result['total_bills_count'])->toBe(4)
        ->and($result['grand_totals']['paid_count'])->toBe(1)
        ->and($result['grand_totals']['paid_total'])->toBe(300000.0)
        ->and($result['grand_totals']['unpaid_count'])->toBe(3)
        ->and($result['grand_totals']['unpaid_total'])->toBe(900000.0)
        ->and($septemberDueSeptember->billing_period->toDateString())->toBe('2026-09-01')
        ->and($septemberDueOctober->billing_period->toDateString())->toBe('2026-09-01')
        ->and($octoberBill->billing_period->toDateString())->toBe('2026-10-01');
});

// 6 & 11. Rekap Kelas X seluruhnya lunas dan Total Kelas X benar
test('recap grade X with all paid bills', function () {
    $classX = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X RPL 1',
        'grade' => 'X',
    ]);

    for ($i = 1; $i <= 3; $i++) {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $user->id,
            'guardian_id' => $this->guardian->id,
            'class_room_id' => $classX->id,
            'entry_year' => 2026,
            'status' => 'active',
            'nis' => "NIS-X-{$i}",
            'name' => "Siswa X {$i}",
            'gender' => 'L',
        ]);

        createPaymentReportBill([
            'student_id' => $student->id,
            'name' => 'SPP Bulanan',
            'amount' => 1000000,
            'due_date' => '2026-09-10',
            'status' => 'paid',
        ]);
    }

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    expect($result['has_data'])->toBeTrue()
        ->and($result['grades'])->toHaveCount(1)
        ->and($result['grades'][0]['grade_title'])->toBe('KELAS X')
        ->and($result['grades'][0]['rows'][0]['paid_count'])->toBe(3)
        ->and($result['grades'][0]['rows'][0]['paid_total'])->toBe(3000000.0)
        ->and($result['grades'][0]['rows'][0]['unpaid_count'])->toBe(0)
        ->and($result['grades'][0]['rows'][0]['unpaid_total'])->toBe(0.0)
        ->and($result['grades'][0]['totals']['paid_count'])->toBe(3)
        ->and($result['grades'][0]['totals']['paid_total'])->toBe(3000000.0)
        ->and($result['grand_totals']['paid_count'])->toBe(3)
        ->and($result['grand_totals']['paid_total'])->toBe(3000000.0);
});

// 7. Rekap Kelas X campuran lunas dan belum lunas
test('recap grade X with mixed paid and unpaid bills', function () {
    $classX = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X TKJ 1',
        'grade' => 'X',
    ]);

    // 2 Siswa Lunas
    for ($i = 1; $i <= 2; $i++) {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $user->id,
            'guardian_id' => $this->guardian->id,
            'class_room_id' => $classX->id,
            'entry_year' => 2026,
            'status' => 'active',
            'nis' => "NIS-P-{$i}",
            'name' => "Siswa Paid {$i}",
            'gender' => 'L',
        ]);

        createPaymentReportBill([
            'student_id' => $student->id,
            'name' => 'SPP Bulanan',
            'amount' => 1000000,
            'due_date' => '2026-09-10',
            'status' => 'paid',
        ]);
    }

    // 1 Siswa Belum Lunas
    $userUnpaid = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $studentUnpaid = Student::create([
        'user_id' => $userUnpaid->id,
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $classX->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-U-1',
        'name' => 'Siswa Unpaid 1',
        'gender' => 'L',
    ]);

    createPaymentReportBill([
        'student_id' => $studentUnpaid->id,
        'name' => 'SPP Bulanan',
        'amount' => 1000000,
        'due_date' => '2026-09-10',
        'status' => 'unpaid',
    ]);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    expect($result['grades'][0]['rows'][0]['paid_count'])->toBe(2)
        ->and($result['grades'][0]['rows'][0]['paid_total'])->toBe(2000000.0)
        ->and($result['grades'][0]['rows'][0]['unpaid_count'])->toBe(1)
        ->and($result['grades'][0]['rows'][0]['unpaid_total'])->toBe(1000000.0);
});

// 8, 9, 10, 12, 13, 14. Multi-Tingkat (X, XI, XII), Rombel Terpisah, Total Per Tingkat, Grand Total
test('multi-grade recap groups correctly and calculates grade totals and grand totals', function () {
    $classX1 = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $classX2 = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 2', 'grade' => 'X']);
    $classXI1 = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);
    $classXII1 = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XII RPL 1', 'grade' => 'XII']);

    // Helper membuat siswa & bill
    $createStudentBill = function ($classRoom, $name, $status, $amount) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create([
            'user_id' => $u->id,
            'guardian_id' => $this->guardian->id,
            'class_room_id' => $classRoom?->id,
            'entry_year' => 2026,
            'status' => 'active',
            'nis' => 'NIS-'.uniqid(),
            'name' => $name,
            'gender' => 'L',
        ]);
        createPaymentReportBill([
            'student_id' => $s->id,
            'name' => 'SPP Bulanan',
            'amount' => $amount,
            'due_date' => '2026-09-10',
            'status' => $status,
        ]);
    };

    // X RPL 1: 1 Paid (1.000.000), 1 Unpaid (1.000.000)
    $createStudentBill($classX1, 'Siswa X1 Paid', 'paid', 1000000);
    $createStudentBill($classX1, 'Siswa X1 Unpaid', 'unpaid', 1000000);

    // X RPL 2: 2 Paid (2.000.000)
    $createStudentBill($classX2, 'Siswa X2 Paid A', 'paid', 1000000);
    $createStudentBill($classX2, 'Siswa X2 Paid B', 'paid', 1000000);

    // XI RPL 1: 1 Paid (1.500.000), 2 Unpaid (3.000.000)
    $createStudentBill($classXI1, 'Siswa XI Paid', 'paid', 1500000);
    $createStudentBill($classXI1, 'Siswa XI Unpaid A', 'unpaid', 1500000);
    $createStudentBill($classXI1, 'Siswa XI Unpaid B', 'unpaid', 1500000);

    // XII RPL 1: 1 Paid (2.000.000)
    $createStudentBill($classXII1, 'Siswa XII Paid', 'paid', 2000000);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    // 10. X RPL 1 dan X RPL 2 tetap menjadi 2 baris terpisah di Grade X
    $gradeX = collect($result['grades'])->firstWhere('grade_key', 'X');
    expect($gradeX['rows'])->toHaveCount(2)
        ->and($gradeX['rows'][0]['class_name'])->toBe('X RPL 1')
        ->and($gradeX['rows'][1]['class_name'])->toBe('X RPL 2')
        // 11. Total Kelas X: 3 Paid (3.000.000), 1 Unpaid (1.000.000)
        ->and($gradeX['totals']['paid_count'])->toBe(3)
        ->and($gradeX['totals']['paid_total'])->toBe(3000000.0)
        ->and($gradeX['totals']['unpaid_count'])->toBe(1)
        ->and($gradeX['totals']['unpaid_total'])->toBe(1000000.0);

    // 12. Total Kelas XI: 1 Paid (1.500.000), 2 Unpaid (3.000.000)
    $gradeXI = collect($result['grades'])->firstWhere('grade_key', 'XI');
    expect($gradeXI['totals']['paid_count'])->toBe(1)
        ->and($gradeXI['totals']['paid_total'])->toBe(1500000.0)
        ->and($gradeXI['totals']['unpaid_count'])->toBe(2)
        ->and($gradeXI['totals']['unpaid_total'])->toBe(3000000.0);

    // 13. Total Kelas XII: 1 Paid (2.000.000), 0 Unpaid (0)
    $gradeXII = collect($result['grades'])->firstWhere('grade_key', 'XII');
    expect($gradeXII['totals']['paid_count'])->toBe(1)
        ->and($gradeXII['totals']['paid_total'])->toBe(2000000.0)
        ->and($gradeXII['totals']['unpaid_count'])->toBe(0)
        ->and($gradeXII['totals']['unpaid_total'])->toBe(0.0);

    // 14. Grand Total merupakan jumlah seluruh tingkat
    // Total Paid: 3 + 1 + 1 = 5 siswa, 3jt + 1.5jt + 2jt = 6.5jt
    // Total Unpaid: 1 + 2 + 0 = 3 siswa, 1jt + 3jt + 0 = 4jt
    expect($result['grand_totals']['paid_count'])->toBe(5)
        ->and($result['grand_totals']['paid_total'])->toBe(6500000.0)
        ->and($result['grand_totals']['unpaid_count'])->toBe(3)
        ->and($result['grand_totals']['unpaid_total'])->toBe(4000000.0);
});

// 15, 16, 17, 28, 29. Filter Month, Year, Bill Name bekerja dan tidak saling mencampur data
test('filter month, year, and bill_name isolates correct bills', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X TKJ 1', 'grade' => 'X']);

    $createBill = function ($name, $dueDate, $amount) use ($classX) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create([
            'user_id' => $u->id,
            'guardian_id' => $this->guardian->id,
            'class_room_id' => $classX->id,
            'entry_year' => 2026,
            'status' => 'active',
            'nis' => 'NIS-'.uniqid(),
            'name' => 'Siswa Test',
            'gender' => 'L',
        ]);
        createPaymentReportBill([
            'student_id' => $s->id,
            'name' => $name,
            'amount' => $amount,
            'due_date' => $dueDate,
            'status' => 'paid',
        ]);
    };

    // Target: September 2026 - SPP Bulanan
    $createBill('SPP Bulanan', '2026-09-10', 500000);

    // Lain bulan: Oktober 2026 - SPP Bulanan
    $createBill('SPP Bulanan', '2026-10-10', 500000);

    // Lain tahun: September 2025 - SPP Bulanan
    $createBill('SPP Bulanan', '2025-09-10', 500000);

    // Lain jenis tagihan: September 2026 - Uang Ujian
    $createBill('Uang Ujian', '2026-09-10', 300000);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    expect($result['total_bills_count'])->toBe(1)
        ->and($result['grand_totals']['paid_count'])->toBe(1)
        ->and($result['grand_totals']['paid_total'])->toBe(500000.0);
});

// 18, 19, 20. Payment pending & rejected dihitung Belum Lunas, Bill paid dihitung Lunas
test('payment pending and rejected are counted as unpaid while bill paid is paid', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);

    // Siswa 1: Bill unpaid + Payment pending (belum diverifikasi) -> Belum Lunas
    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s1 = Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-PND', 'name' => 'Siswa Pending', 'gender' => 'L']);
    $b1 = createPaymentReportBill(['student_id' => $s1->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'unpaid']);
    Payment::create(['bill_id' => $b1->id, 'payer_id' => $u1->id, 'payment_method_id' => $this->paymentMethod->id, 'payment_number' => 'PAY-PND', 'amount' => 500000, 'status' => 'pending', 'proof_of_payment' => 'proof.jpg']);

    // Siswa 2: Bill unpaid + Payment rejected -> Belum Lunas
    $u2 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s2 = Student::create(['user_id' => $u2->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-REJ', 'name' => 'Siswa Rejected', 'gender' => 'L']);
    $b2 = createPaymentReportBill(['student_id' => $s2->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'unpaid']);
    $p2 = Payment::create(['bill_id' => $b2->id, 'payer_id' => $u2->id, 'payment_method_id' => $this->paymentMethod->id, 'payment_number' => 'PAY-REJ', 'amount' => 500000, 'status' => 'pending', 'proof_of_payment' => 'proof.jpg']);
    PaymentVerification::create(['payment_id' => $p2->id, 'admin_id' => $this->adminUser->id, 'status' => 'rejected', 'note' => 'Buram', 'processed_at' => now()]);

    // Siswa 3: Bill paid -> Lunas
    $u3 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s3 = Student::create(['user_id' => $u3->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-LNS', 'name' => 'Siswa Lunas', 'gender' => 'L']);
    createPaymentReportBill(['student_id' => $s3->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    expect($result['grand_totals']['paid_count'])->toBe(1)
        ->and($result['grand_totals']['paid_total'])->toBe(500000.0)
        ->and($result['grand_totals']['unpaid_count'])->toBe(2)
        ->and($result['grand_totals']['unpaid_total'])->toBe(1000000.0);
});

// 21. Student tanpa ClassRoom masuk kelompok Tanpa Kelas
test('student without class room is grouped under Tanpa Kelas', function () {
    $classNoClass = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'Tanpa Kelas',
        'grade' => 'TANPA_KELAS',
    ]);

    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create([
        'user_id' => $u->id,
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $classNoClass->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-NO-CLASS',
        'name' => 'Siswa Tanpa Kelas',
        'gender' => 'L',
    ]);

    createPaymentReportBill([
        'student_id' => $s->id,
        'name' => 'SPP Bulanan',
        'amount' => 750000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    $noClassSection = collect($result['grades'])->firstWhere('grade_key', 'TANPA_KELAS');

    expect($noClassSection)->not->toBeNull()
        ->and($noClassSection['grade_title'])->toBe('TANPA KELAS')
        ->and($noClassSection['rows'][0]['class_name'])->toBe('Tanpa Kelas')
        ->and($noClassSection['totals']['paid_count'])->toBe(1)
        ->and($noClassSection['totals']['paid_total'])->toBe(750000.0)
        ->and($result['grand_totals']['paid_total'])->toBe(750000.0);
});

// 22 & 23. Periode hanya mengikuti bills.billing_period, tanpa fallback batch due_date
test('billing period does not fall back to bill batch due date', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s1 = Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-D1', 'name' => 'Siswa Direct', 'gender' => 'L']);

    $batch = BillBatch::create([
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
        'billing_period' => '2026-09-01',
        'due_date' => '2026-09-15',
        'target_type' => 'school',
    ]);

    $bill = createPaymentReportBill([
        'bill_batch_id' => $batch->id,
        'student_id' => $s1->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'billing_period' => '2026-10-01',
        'due_date' => null,
        'status' => 'paid',
    ]);

    $action = app(BuildClassPaymentRecapAction::class);
    $septemberResult = $action->execute(9, 2026, 'SPP Bulanan');
    $octoberResult = $action->execute(10, 2026, 'SPP Bulanan');

    expect($septemberResult['total_bills_count'])->toBe(0)
        ->and($octoberResult['total_bills_count'])->toBe(1)
        ->and($bill->fresh()->due_date)->toBeNull();
});

// 24. due_date tidak diperlukan untuk menentukan periode laporan
test('bill without due date is included by its billing period', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-NODUE', 'name' => 'Siswa No Due', 'gender' => 'L']);

    createPaymentReportBill([
        'student_id' => $s->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'billing_period' => '2026-09-01',
        'due_date' => null,
        'status' => 'unpaid',
    ]);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    expect($result['has_data'])->toBeTrue()
        ->and($result['total_bills_count'])->toBe(1)
        ->and($result['grand_totals']['unpaid_count'])->toBe(1);
});

// 25. Preview menghasilkan angka yang sama dengan halaman Rekap
test('preview page returns identical data to recap action', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-PRV', 'name' => 'Siswa Preview', 'gender' => 'L']);

    createPaymentReportBill([
        'student_id' => $s->id,
        'name' => 'SPP Bulanan',
        'amount' => 1250000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.preview', [
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
    ]));

    $response->assertOk();
    $response->assertViewIs('admin.reports.preview');
    $response->assertSee('SMK SikolaPay');
    $response->assertSee('Laporan Rekapitulasi Pembayaran Siswa');
    $response->assertSee('September 2026');
    $response->assertSee('X RPL 1');
    $response->assertSee('Rp 1.250.000');
    $response->assertSee('Admin Keuangan');
    $response->assertSee('Semua Tingkat');
});

// 26. CSV Export menghasilkan angka yang sama dan format bersih
test('csv export streams correct recap structure', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-CSV', 'name' => 'Siswa CSV', 'gender' => 'L']);

    createPaymentReportBill([
        'student_id' => $s->id,
        'name' => 'SPP Bulanan',
        'amount' => 800000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.export-excel', [
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('LAPORAN REKAPITULASI PEMBAYARAN SISWA')
        ->and($content)->toContain('September 2026')
        ->and($content)->toContain('SPP Bulanan')
        ->and($content)->toContain('Semua Tingkat')
        ->and($content)->toContain('X RPL 1')
        ->and($content)->toContain('800000')
        ->and($content)->toContain('GRAND TOTAL KESELURUHAN');
});

// 27. Satu Student dengan riwayat multi-payment tidak dihitung double
test('one student with multiple payment attempts is not double counted in student count', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-DBL', 'name' => 'Siswa Multi Pay', 'gender' => 'L']);

    $bill = createPaymentReportBill([
        'student_id' => $s->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    // Payment 1: rejected
    Payment::create(['bill_id' => $bill->id, 'payer_id' => $u->id, 'payment_method_id' => $this->paymentMethod->id, 'payment_number' => 'PAY-1', 'amount' => 500000, 'status' => 'failed']);
    // Payment 2: paid
    Payment::create(['bill_id' => $bill->id, 'payer_id' => $u->id, 'payment_method_id' => $this->paymentMethod->id, 'payment_number' => 'PAY-2', 'amount' => 500000, 'status' => 'paid', 'paid_at' => now()]);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan');

    expect($result['grand_totals']['paid_count'])->toBe(1)
        ->and($result['grand_totals']['paid_total'])->toBe(500000.0);
});

// 30. Tab Rekap tetap aktif setelah filter disubmit
test('recap tab remains active when filter query is submitted', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-TAB', 'name' => 'Siswa Tab', 'gender' => 'L']);

    createPaymentReportBill([
        'student_id' => $s->id,
        'name' => 'SPP Bulanan',
        'amount' => 500000,
        'due_date' => '2026-09-10',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'recap',
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
    ]));

    $response->assertOk();
    $response->assertSee('Rekapitulasi SPP Bulanan');
    $response->assertSee('value="recap"', false);
});

// =========================================================================
// REVISI TEST: FILTER TINGKAT KELAS (GRADE X, XI, XII & SEMUA TINGKAT)
// =========================================================================

// Test R1: Semua Tingkat menampilkan seluruh grade X, XI, XII
test('grade filter null displays all grades X, XI, XII', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);
    $cXII = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XII RPL 1', 'grade' => 'XII']);

    $createBillForClass = function ($class, $name, $amt) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $class->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-'.uniqid(), 'name' => 'Siswa '.$name, 'gender' => 'L']);
        createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => $amt, 'due_date' => '2026-09-10', 'status' => 'paid']);
    };

    $createBillForClass($cX, 'X', 1000000);
    $createBillForClass($cXI, 'XI', 1200000);
    $createBillForClass($cXII, 'XII', 1500000);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan', null);

    expect($result['grades'])->toHaveCount(3)
        ->and($result['period']['grade_label'])->toBe('Semua Tingkat')
        ->and($result['grand_totals']['paid_count'])->toBe(3)
        ->and($result['grand_totals']['paid_total'])->toBe(3700000.0);
});

// Test R2, R5, R6, R7: grade=X hanya menghasilkan Kelas X, tidak memasukkan XI dan XII
test('grade=X only includes grade X and isolates grand total', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);
    $cXII = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XII RPL 1', 'grade' => 'XII']);

    $createBillForClass = function ($class, $name, $amt) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $class->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-'.uniqid(), 'name' => 'Siswa '.$name, 'gender' => 'L']);
        createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => $amt, 'due_date' => '2026-09-10', 'status' => 'paid']);
    };

    $createBillForClass($cX, 'X', 1000000);
    $createBillForClass($cXI, 'XI', 1200000);
    $createBillForClass($cXII, 'XII', 1500000);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan', 'X');

    expect($result['grades'])->toHaveCount(1)
        ->and($result['grades'][0]['grade_title'])->toBe('KELAS X')
        ->and($result['grades'][0]['rows'][0]['class_name'])->toBe('X RPL 1')
        ->and($result['period']['grade'])->toBe('X')
        ->and($result['period']['grade_label'])->toBe('Kelas X')
        ->and($result['grand_totals']['paid_count'])->toBe(1)
        ->and($result['grand_totals']['paid_total'])->toBe(1000000.0);
});

// Test R3: grade=XI hanya menghasilkan Kelas XI
test('grade=XI only includes grade XI', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);

    $createBillForClass = function ($class, $name, $amt) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $class->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-'.uniqid(), 'name' => 'Siswa '.$name, 'gender' => 'L']);
        createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => $amt, 'due_date' => '2026-09-10', 'status' => 'paid']);
    };

    $createBillForClass($cX, 'X', 1000000);
    $createBillForClass($cXI, 'XI', 1200000);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan', 'XI');

    expect($result['grades'])->toHaveCount(1)
        ->and($result['grades'][0]['grade_title'])->toBe('KELAS XI')
        ->and($result['grades'][0]['rows'][0]['class_name'])->toBe('XI RPL 1')
        ->and($result['grand_totals']['paid_total'])->toBe(1200000.0);
});

// Test R4: grade=XII hanya menghasilkan Kelas XII
test('grade=XII only includes grade XII', function () {
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);
    $cXII = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XII RPL 1', 'grade' => 'XII']);

    $createBillForClass = function ($class, $name, $amt) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $class->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-'.uniqid(), 'name' => 'Siswa '.$name, 'gender' => 'L']);
        createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => $amt, 'due_date' => '2026-09-10', 'status' => 'paid']);
    };

    $createBillForClass($cXI, 'XI', 1200000);
    $createBillForClass($cXII, 'XII', 1500000);

    $action = app(BuildClassPaymentRecapAction::class);
    $result = $action->execute(9, 2026, 'SPP Bulanan', 'XII');

    expect($result['grades'])->toHaveCount(1)
        ->and($result['grades'][0]['grade_title'])->toBe('KELAS XII')
        ->and($result['grades'][0]['rows'][0]['class_name'])->toBe('XII RPL 1')
        ->and($result['grand_totals']['paid_total'])->toBe(1500000.0);
});

// Test R8 & R9: Preview membawa filter grade dengan akurat
test('preview page handles grade=X and Semua Tingkat accurately', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);

    $createBillForClass = function ($class, $name, $amt) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $class->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-'.uniqid(), 'name' => 'Siswa '.$name, 'gender' => 'L']);
        createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => $amt, 'due_date' => '2026-09-10', 'status' => 'paid']);
    };

    $createBillForClass($cX, 'X', 1000000);
    $createBillForClass($cXI, 'XI', 1200000);

    // Preview dengan grade=X
    $responseX = $this->actingAs($this->adminUser)->get(route('admin.reports.preview', [
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
        'grade' => 'X',
    ]));

    $responseX->assertOk();
    $responseX->assertSee('Kelas X');
    $responseX->assertSee('X RPL 1');
    $responseX->assertDontSee('XI RPL 1');

    // Preview tanpa grade (Semua Tingkat)
    $responseAll = $this->actingAs($this->adminUser)->get(route('admin.reports.preview', [
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
    ]));

    $responseAll->assertOk();
    $responseAll->assertSee('Semua Tingkat');
    $responseAll->assertSee('X RPL 1');
    $responseAll->assertSee('XI RPL 1');
});

// Test R10 & R11: CSV export carries grade filter accurately
test('csv export with grade=XI only contains grade XI data and metadata', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);

    $createBillForClass = function ($class, $name, $amt) {
        $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $class->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-'.uniqid(), 'name' => 'Siswa '.$name, 'gender' => 'L']);
        createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => $amt, 'due_date' => '2026-09-10', 'status' => 'paid']);
    };

    $createBillForClass($cX, 'X', 1000000);
    $createBillForClass($cXI, 'XI', 1200000);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.export-excel', [
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
        'grade' => 'XI',
    ]));

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('Kelas XI')
        ->and($content)->toContain('XI RPL 1')
        ->and($content)->not->toContain('X RPL 1');
});

// Test R12: Invalid grade ditolak oleh validation
test('invalid grade parameter is rejected by validation', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.preview', [
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
        'grade' => 'XIII', // Invalid
    ]));

    $response->assertSessionHasErrors('grade');
});

// Test R13 & R14: Parameter grade tetap aktif dan terpilih di UI Tab Rekap
test('recap tab keeps grade parameter selected after submission', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-GRADE-X', 'name' => 'Siswa X Grade', 'gender' => 'L']);
    createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', [
        'tab' => 'recap',
        'month' => 9,
        'year' => 2026,
        'bill_name' => 'SPP Bulanan',
        'grade' => 'X',
    ]));

    $response->assertOk();
    $response->assertSee('value="X" selected', false);
    $response->assertSee('X RPL 1');
});

// Test R15: Student tanpa kelas hanya muncul saat Semua Tingkat, tidak saat grade X/XI/XII dipilih
test('student without class only appears when grade filter is null', function () {
    $cNoClass = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'Tanpa Kelas', 'grade' => 'TANPA_KELAS']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cNoClass->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-TK', 'name' => 'Siswa TK', 'gender' => 'L']);
    createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP Bulanan', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    $action = app(BuildClassPaymentRecapAction::class);

    // Semua Tingkat: Muncul
    $resultAll = $action->execute(9, 2026, 'SPP Bulanan', null);
    expect(collect($resultAll['grades'])->firstWhere('grade_key', 'TANPA_KELAS'))->not->toBeNull();

    // Grade X: Tidak muncul
    $resultX = $action->execute(9, 2026, 'SPP Bulanan', 'X');
    expect($resultX['has_data'])->toBeFalse()
        ->and(collect($resultX['grades'])->firstWhere('grade_key', 'TANPA_KELAS'))->toBeNull();
});

// Test R16: Dropdown Jenis Tagihan Rekap hanya menampilkan kategori resmi Bill::TYPES dan tidak menampilkan legacy name
test('recap bill type dropdown only contains official Bill::TYPES and excludes legacy names', function () {
    // Buat data dengan nama legacy
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-LEG', 'name' => 'Siswa Leg', 'gender' => 'L']);
    createPaymentReportBill(['student_id' => $s->id, 'name' => 'SPP September 2026', 'amount' => 500000, 'due_date' => '2026-09-10', 'status' => 'paid']);

    $response = $this->actingAs($this->adminUser)->get(route('admin.reports.index', ['tab' => 'recap']));

    $response->assertOk();
    foreach (Bill::TYPES as $type) {
        $response->assertSee('value="'.$type.'"', false);
    }

    // Pastikan nama legacy seperti SPP September 2026 tidak menjadi opsi baru di dropdown
    $response->assertDontSee('value="SPP September 2026"', false);
});
