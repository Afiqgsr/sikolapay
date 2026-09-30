<?php

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillBatch;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Admin Keuangan',
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

    $guardianUser = User::factory()->create(['role' => 'guardian', 'status' => 'active']);
    $this->guardian = Guardian::create([
        'user_id' => $guardianUser->id,
        'name' => 'Wali Murid Test',
        'phone' => '081234567890',
    ]);
});

// 1. Form Tambah Tagihan menampilkan target resmi: Per Siswa, Per Kelas, Per Tingkat, Seluruh Sekolah dan TIDAK menampilkan Per Angkatan
test('billing data page shows official target options and excludes per angkatan from add form', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.bills.index'));

    $response->assertOk();
    $response->assertSee('Per Siswa');
    $response->assertSee('Per Kelas');
    $response->assertSee('Per Tingkat');
    $response->assertSee('Seluruh Sekolah');
    $response->assertDontSee('<option value="cohort">', false);
});

// 2. Semester dropdown dibentuk dari AcademicYear aktif
test('semester dropdown is populated from active academic year', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.bills.index'));

    $response->assertOk();
    $response->assertSee('Ganjil 2026/2027');
    $response->assertSee('Genap 2026/2027');
});

// 3. Create Per Siswa berhasil
test('can create bill batch per siswa', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create(['user_id' => $u->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-01', 'name' => 'Budi Santoso', 'gender' => 'L']);

    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'student',
        'target_value' => $student->id,
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 350000,
        'due_date' => '2026-09-10',
    ]);

    $response->assertRedirect(route('admin.bills.index'));
    $this->assertDatabaseHas('bill_batches', [
        'target_type' => 'student',
        'target_value' => (string) $student->id,
        'name' => 'SPP Bulanan',
    ]);
    $this->assertDatabaseHas('bills', [
        'student_id' => $student->id,
        'name' => 'SPP Bulanan',
        'amount' => 350000,
    ]);
});

// 4. Create Per Kelas berhasil
test('can create bill batch per kelas', function () {
    $classX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $u2 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $s1 = Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-K1', 'name' => 'Siswa K1', 'gender' => 'L']);
    $s2 = Student::create(['user_id' => $u2->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $classX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-K2', 'name' => 'Siswa K2', 'gender' => 'L']);

    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'class',
        'target_value' => $classX->id,
        'name' => 'Uang Ujian',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 200000,
        'due_date' => '2026-09-10',
    ]);

    $response->assertRedirect(route('admin.bills.index'));
    $this->assertDatabaseHas('bill_batches', [
        'target_type' => 'class',
        'target_value' => (string) $classX->id,
        'name' => 'Uang Ujian',
    ]);
    expect(Bill::where('name', 'Uang Ujian')->count())->toBe(2);
});

// 5. Create Per Tingkat X membuat tagihan hanya untuk siswa tingkat X, bukan XI/XII
test('per tingkat X only generates bills for students in grade X, excluding XI and XII', function () {
    $cX1 = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cX2 = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X TKJ 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);
    $cXII = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XII RPL 1', 'grade' => 'XII']);

    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $u2 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $u3 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $u4 = User::factory()->create(['role' => 'student', 'status' => 'active']);

    $s1 = Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cX1->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-X1', 'name' => 'Siswa X1', 'gender' => 'L']);
    $s2 = Student::create(['user_id' => $u2->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cX2->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-X2', 'name' => 'Siswa X2', 'gender' => 'L']);
    $s3 = Student::create(['user_id' => $u3->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cXI->id, 'entry_year' => 2025, 'status' => 'active', 'nis' => 'NIS-XI', 'name' => 'Siswa XI', 'gender' => 'L']);
    $s4 = Student::create(['user_id' => $u4->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cXII->id, 'entry_year' => 2024, 'status' => 'active', 'nis' => 'NIS-XII', 'name' => 'Siswa XII', 'gender' => 'L']);

    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'grade',
        'target_value' => 'X',
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
        'due_date' => '2026-09-10',
    ]);

    $response->assertRedirect(route('admin.bills.index'));

    $batch = BillBatch::where('target_type', 'grade')->where('target_value', 'X')->first();
    expect($batch)->not->toBeNull();

    $bills = Bill::where('bill_batch_id', $batch->id)->get();
    expect($bills)->toHaveCount(2);

    $studentIds = $bills->pluck('student_id')->all();
    expect($studentIds)->toContain($s1->id)
        ->and($studentIds)->toContain($s2->id)
        ->and($studentIds)->not->toContain($s3->id)
        ->and($studentIds)->not->toContain($s4->id);
});

// 6. Seluruh Sekolah tidak membutuhkan target_value
test('seluruh sekolah creates bill for all active students without target_value', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);

    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $u2 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-ALL1', 'name' => 'Siswa All 1', 'gender' => 'L']);
    Student::create(['user_id' => $u2->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cXI->id, 'entry_year' => 2025, 'status' => 'active', 'nis' => 'NIS-ALL2', 'name' => 'Siswa All 2', 'gender' => 'L']);

    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'school',
        'target_value' => null,
        'name' => 'Kegiatan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 100000,
        'due_date' => '2026-09-10',
    ]);

    $response->assertRedirect(route('admin.bills.index'));
    expect(Bill::where('name', 'Kegiatan')->count())->toBe(2);
});

// 7. Cohort tidak dapat dibuat dari submission baru
test('cohort target type is rejected for new submissions', function () {
    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'cohort',
        'target_value' => 2026,
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
    ]);

    $response->assertSessionHasErrors('target_type');
});

// 8. Jenis Tagihan invalid ditolak
test('invalid bill name is rejected by validation', function () {
    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'school',
        'name' => 'SPP September 2026', // Invalid, harus kategori resmi
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
    ]);

    $response->assertSessionHasErrors('name');
});

// 9. Semester invalid / bebas ditolak
test('invalid or manual semester input is rejected by validation', function () {
    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'school',
        'name' => 'SPP Bulanan',
        'semester' => 'Semester Ganjil 2026', // Tidak sesuai opsi AcademicYear
        'amount' => 500000,
    ]);

    $response->assertSessionHasErrors('semester');
});

// 10. Target Grade invalid ditolak
test('invalid grade target is rejected by validation', function () {
    $response = $this->actingAs($this->adminUser)->post(route('admin.bills.store'), [
        'target_type' => 'grade',
        'target_value' => 'XIII', // Invalid
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
    ]);

    $response->assertSessionHasErrors('target_value');
});

// 11. Update mendukung Per Tingkat
test('update supports per tingkat target', function () {
    $cX = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'X RPL 1', 'grade' => 'X']);
    $cXI = ClassRoom::create(['academic_year_id' => $this->academicYear->id, 'name' => 'XI RPL 1', 'grade' => 'XI']);

    $u1 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $u2 = User::factory()->create(['role' => 'student', 'status' => 'active']);
    Student::create(['user_id' => $u1->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cX->id, 'entry_year' => 2026, 'status' => 'active', 'nis' => 'NIS-U1', 'name' => 'Siswa X', 'gender' => 'L']);
    Student::create(['user_id' => $u2->id, 'guardian_id' => $this->guardian->id, 'class_room_id' => $cXI->id, 'entry_year' => 2025, 'status' => 'active', 'nis' => 'NIS-U2', 'name' => 'Siswa XI', 'gender' => 'L']);

    $batch = BillBatch::create([
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
        'target_type' => 'grade',
        'target_value' => 'X',
    ]);

    $response = $this->actingAs($this->adminUser)->put(route('admin.bills.update', $batch), [
        'target_type' => 'grade',
        'target_value' => 'XI',
        'name' => 'SPP Bulanan',
        'semester' => 'Genap 2026/2027',
        'amount' => 600000,
    ]);

    $response->assertRedirect(route('admin.bills.index'));
    $this->assertDatabaseHas('bill_batches', [
        'id' => $batch->id,
        'target_type' => 'grade',
        'target_value' => 'XI',
        'semester' => 'Genap 2026/2027',
        'amount' => 600000,
    ]);
});

// 12. Legacy Cohort tetap dapat dibaca pada index list
test('legacy cohort bill batch can still be displayed on index page', function () {
    $batch = BillBatch::create([
        'name' => 'SPP Bulanan',
        'semester' => 'Ganjil 2026/2027',
        'amount' => 500000,
        'target_type' => 'cohort',
        'target_value' => 2026,
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.bills.index'));

    $response->assertOk();
    $response->assertSee('Angkatan 2026');
});
