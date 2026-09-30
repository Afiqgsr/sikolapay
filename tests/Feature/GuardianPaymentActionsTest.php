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
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
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

    $this->otherGuardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $this->otherGuardian = Guardian::create([
        'user_id' => $this->otherGuardianUser->id,
        'name' => 'Wali Murid Lain',
        'phone' => '081234567891',
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

    $studentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->student = Student::create([
        'user_id' => $studentUser->id,
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-G-001',
        'name' => 'Anak Test',
        'gender' => 'L',
    ]);

    $otherStudentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'guardian_id' => $this->otherGuardian->id,
        'class_room_id' => $classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-G-002',
        'name' => 'Anak Lain',
        'gender' => 'P',
    ]);

    $this->bill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Juli 2026',
        'amount' => 250000,
        'status' => 'unpaid',
    ]);

    $this->otherBill = Bill::create([
        'student_id' => $this->otherStudent->id,
        'name' => 'SPP Juli 2026 Lain',
        'amount' => 250000,
        'status' => 'unpaid',
    ]);

    $this->paymentMethod = PaymentMethod::create([
        'name' => 'Transfer BCA',
        'type' => 'bank_transfer',
        'code' => 'BCA-TEST',
        'provider' => 'BCA',
        'is_active' => true,
    ]);
});

test('guardian can view create payment page for their student bill', function () {
    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.create', $this->bill->id));

    $response->assertOk()
        ->assertViewIs('guardian.payments.create')
        ->assertViewHas(['bill', 'paymentMethods']);
});

test('guardian cannot view create payment page for another student bill', function () {
    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.create', $this->otherBill->id));

    $response->assertNotFound();
});

test('create payment redirects to show if bill is already paid', function () {
    $paidPayment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-PAID-01',
        'amount' => $this->bill->amount,
        'status' => 'paid',
    ]);

    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.create', $this->bill->id));

    $response->assertRedirect(route('guardian.payments.show', $paidPayment->id));
});

test('create payment redirects to show if bill has pending payment', function () {
    $pendingPayment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-PEND-01',
        'amount' => $this->bill->amount,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.create', $this->bill->id));

    $response->assertRedirect(route('guardian.payments.show', $pendingPayment->id));
});

test('guardian can store payment successfully with proof upload', function () {
    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->post(route('guardian.payments.store'), [
            'bill_id' => $this->bill->id,
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $payment = Payment::where('bill_id', $this->bill->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payer_id)->toBe($this->guardianUser->id)
        ->and($payment->status)->toBe('pending')
        ->and((int) $payment->amount)->toBe(250000);

    Storage::disk('public')->assertExists($payment->proof_of_payment);

    $response->assertRedirect(route('guardian.payments.show', $payment->id))
        ->assertSessionHas('success', 'Pembayaran berhasil dikirim dan menunggu verifikasi admin.');
});

test('guardian store redirects with error and does not create payment when proof upload fails', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);

    $filesystem = Mockery::mock(FilesystemFactory::class);
    $filesystem->shouldReceive('disk')->once()->with('public')->andReturn($disk);

    $this->app->instance(FilesystemFactory::class, $filesystem);

    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->from(route('guardian.payments.create', $this->bill->id))
        ->post(route('guardian.payments.store'), [
            'bill_id' => $this->bill->id,
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertRedirect(route('guardian.payments.create', $this->bill->id))
        ->assertSessionHas('error', 'Bukti pembayaran gagal diunggah. Silakan coba lagi.');

    expect(Payment::where('bill_id', $this->bill->id)->count())->toBe(0);
});

test('guardian cannot store payment for another student bill', function () {
    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->post(route('guardian.payments.store'), [
            'bill_id' => $this->otherBill->id,
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertNotFound();
    expect(Payment::where('bill_id', $this->otherBill->id)->count())->toBe(0);
});

test('guardian cannot store payment if bill is already paid', function () {
    $paidPayment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-PAID-02',
        'amount' => $this->bill->amount,
        'status' => 'paid',
    ]);

    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->post(route('guardian.payments.store'), [
            'bill_id' => $this->bill->id,
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertRedirect(route('guardian.payments.show', $paidPayment->id))
        ->assertSessionHas('error', 'Tagihan ini sudah dibayar.');
});

test('guardian cannot store payment if bill already has pending payment', function () {
    $pendingPayment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-PEND-02',
        'amount' => $this->bill->amount,
        'status' => 'pending',
    ]);

    $file = UploadedFile::fake()->image('proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->post(route('guardian.payments.store'), [
            'bill_id' => $this->bill->id,
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertRedirect(route('guardian.payments.show', $pendingPayment->id))
        ->assertSessionHas('error', 'Pembayaran tagihan ini sedang menunggu verifikasi.');
});

test('validation fails if proof of payment is not an image or invalid', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($this->guardianUser)
        ->post(route('guardian.payments.store'), [
            'bill_id' => $this->bill->id,
            'payment_method_id' => $this->paymentMethod->id,
            'proof_of_payment' => $file,
        ]);

    $response->assertSessionHasErrors(['proof_of_payment']);
});

test('guardian can view payment show page for their student payment', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-SHOW-01',
        'amount' => $this->bill->amount,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.show', $payment->id));

    $response->assertOk()
        ->assertViewIs('guardian.payments.show')
        ->assertViewHas('payment');
});

test('guardian cannot view show page for another student payment', function () {
    $otherPayment = Payment::create([
        'bill_id' => $this->otherBill->id,
        'payer_id' => $this->otherGuardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-OTHER-01',
        'amount' => $this->otherBill->amount,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.show', $otherPayment->id));

    $response->assertNotFound();
});

test('guardian can upload new proof for pending payment and cleans old proof', function () {
    $oldFile = UploadedFile::fake()->image('old_proof.jpg');
    $oldPath = $oldFile->store('payments/proofs', 'public');

    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-PROOF-01',
        'amount' => $this->bill->amount,
        'proof_of_payment' => $oldPath,
        'proof_uploaded_at' => now(),
        'status' => 'pending',
    ]);

    Storage::disk('public')->assertExists($oldPath);

    $newFile = UploadedFile::fake()->image('new_proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->post(route('guardian.payments.proof', $payment->id), [
            'proof_of_payment' => $newFile,
        ]);

    $payment->refresh();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($payment->proof_of_payment);

    $response->assertRedirect(route('guardian.payments.show', $payment->id))
        ->assertSessionHas('success', 'Bukti pembayaran berhasil diperbarui dan menunggu verifikasi admin.');
});

test('guardian upload proof redirects with error and keeps old proof when new proof upload fails', function () {
    $oldFile = UploadedFile::fake()->image('old_proof.jpg');
    $oldPath = $oldFile->store('payments/proofs', 'public');

    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-PROOF-FAIL-01',
        'amount' => $this->bill->amount,
        'proof_of_payment' => $oldPath,
        'proof_uploaded_at' => now()->subDay(),
        'status' => 'pending',
    ]);

    $originalProofUploadedAt = $payment->proof_uploaded_at;

    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);

    $filesystem = Mockery::mock(FilesystemFactory::class);
    $filesystem->shouldReceive('disk')->once()->with('public')->andReturn($disk);

    $this->app->instance(FilesystemFactory::class, $filesystem);

    $newFile = UploadedFile::fake()->image('new_proof.jpg');

    $response = $this->actingAs($this->guardianUser)
        ->from(route('guardian.payments.show', $payment->id))
        ->post(route('guardian.payments.proof', $payment->id), [
            'proof_of_payment' => $newFile,
        ]);

    $payment->refresh();

    $response->assertRedirect(route('guardian.payments.show', $payment->id))
        ->assertSessionHas('error', 'Bukti pembayaran gagal diunggah. Silakan coba lagi.');

    expect($payment->proof_of_payment)->toBe($oldPath)
        ->and($payment->proof_uploaded_at?->equalTo($originalProofUploadedAt))->toBeTrue();
});

test('guardian can view receipt for paid payment', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-RCPT-01',
        'amount' => $this->bill->amount,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.receipt', $payment->id));

    $response->assertOk()
        ->assertViewIs('guardian.payments.receipt')
        ->assertViewHas('payment');
});

test('guardian cannot view receipt for non-paid payment', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-TEST-NONPAID-01',
        'amount' => $this->bill->amount,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->guardianUser)
        ->get(route('guardian.payments.receipt', $payment->id));

    $response->assertNotFound();
});
