<?php

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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->school = School::create([
        'name' => 'SMK SikolaPay Test',
    ]);

    $this->academicYear = AcademicYear::create([
        'school_id' => $this->school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);

    $this->classRoom = ClassRoom::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => 'X RPL 1',
        'grade' => '10',
    ]);

    $this->guardianUser = User::factory()->create([
        'role' => 'guardian',
        'status' => 'active',
    ]);

    $this->guardian = Guardian::create([
        'user_id' => $this->guardianUser->id,
        'name' => 'Wali Test',
        'phone' => '081234567890',
    ]);

    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'guardian_id' => $this->guardian->id,
        'class_room_id' => $this->classRoom->id,
        'entry_year' => 2026,
        'status' => 'active',
        'nis' => 'NIS-2026-01',
        'name' => 'Siswa Test',
        'gender' => 'L',
    ]);

    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->paymentMethod = PaymentMethod::create([
        'name' => 'Transfer BCA',
        'type' => 'bank_transfer',
        'code' => 'BCA',
        'provider' => 'BCA',
        'is_active' => true,
    ]);
});

test('boundary test: on 30 Sep 2026, Sep bill is payable and Oct bill shows Belum dapat dibayar with date notice', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 10, 0, 0, (string) config('app.timezone')));

    $septemberBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP September',
        'amount' => 200000,
        'billing_period' => '2026-09-01',
        'due_date' => '2026-10-05',
        'status' => 'unpaid',
    ]);

    $octoberBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Oktober',
        'amount' => 250000,
        'billing_period' => '2026-10-01',
        'due_date' => '2026-10-20',
        'status' => 'unpaid',
    ]);

    // 1. Student bills index
    $response = $this->actingAs($this->studentUser)->get(route('student.bills.index'));
    $response->assertOk();

    // Pastikan kedua bill tetap terlihat di daftar
    $response->assertSee('SPP September');
    $response->assertSee('SPP Oktober');

    // Pastikan label Periode Tagihan dan Jatuh Tempo ada
    $response->assertSee('Periode Tagihan');
    $response->assertSee('Jatuh Tempo');

    // Pastikan October Bill menampilkan status "Belum dapat dibayar" dan info ketersediaan
    $response->assertSee('Belum dapat dibayar');
    $response->assertSee('Tersedia mulai 1 Oktober 2026');

    // Aksi bayar tidak boleh tersedia untuk October Bill
    $studentPaymentUrlOct = route('student.payment', $octoberBill->id);
    $response->assertDontSee('href="'.$studentPaymentUrlOct.'"', false);

    // September bill tetap payable
    $studentPaymentUrlSep = route('student.payment', $septemberBill->id);
    $response->assertSee('href="'.$studentPaymentUrlSep.'"', false);

    // 2. Student bill detail for October Bill
    $detailResponse = $this->actingAs($this->studentUser)->get(route('student.bills.show', $octoberBill->id));
    $detailResponse->assertOk();
    $detailResponse->assertSee('Belum dapat dibayar');
    $detailResponse->assertSee('Tersedia mulai 1 Oktober 2026');
    $detailResponse->assertDontSee('href="'.$studentPaymentUrlOct.'"', false);
});

test('boundary test: on 1 Oct 2026, Oct bill becomes payable with action available', function () {
    $this->travelTo(Carbon::create(2026, 10, 1, 0, 0, 0, (string) config('app.timezone')));

    $octoberBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Oktober',
        'amount' => 250000,
        'billing_period' => '2026-10-01',
        'due_date' => '2026-10-20',
        'status' => 'unpaid',
    ]);

    $response = $this->actingAs($this->studentUser)->get(route('student.bills.index'));
    $response->assertOk();
    $response->assertSee('SPP Oktober');
    $response->assertSee('Belum Bayar');
    $response->assertDontSee('Belum dapat dibayar');

    $studentPaymentUrlOct = route('student.payment', $octoberBill->id);
    $response->assertSee('href="'.$studentPaymentUrlOct.'"', false);
});

test('student batch payment summary strictly excludes future billing period bills', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 10, 0, 0, (string) config('app.timezone')));

    $currentBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP September',
        'amount' => 200000,
        'billing_period' => '2026-09-01',
        'due_date' => '2026-10-05',
        'status' => 'unpaid',
    ]);

    $futureBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Oktober',
        'amount' => 300000,
        'billing_period' => '2026-10-01',
        'due_date' => '2026-10-20',
        'status' => 'unpaid',
    ]);

    // 1. student bills index summary card
    $indexResponse = $this->actingAs($this->studentUser)->get(route('student.bills.index'));
    $indexResponse->assertOk();
    // Hanya currentBill yang masuk ke total tagihan belum dibayar (Rp 200.000, bukan Rp 500.000)
    $indexResponse->assertSee('Rp 200.000');
    $indexResponse->assertDontSee('Rp 500.000');

    // 2. student.payment.all
    $allResponse = $this->actingAs($this->studentUser)->get(route('student.payment.all'));
    $allResponse->assertOk();
    $allResponse->assertViewHas('unpaidBills', function ($bills) use ($currentBill, $futureBill) {
        return $bills->contains('id', $currentBill->id) && ! $bills->contains('id', $futureBill->id);
    });
    $allResponse->assertViewHas('total', 200000);

    // 3. student.payment.all.confirm
    $confirmResponse = $this->actingAs($this->studentUser)->get(route('student.payment.all.confirm'));
    $confirmResponse->assertOk();
    $confirmResponse->assertViewHas('unpaidBills', function ($bills) use ($currentBill, $futureBill) {
        return $bills->contains('id', $currentBill->id) && ! $bills->contains('id', $futureBill->id);
    });
    $confirmResponse->assertViewHas('total', 200000);
});

test('guardian view: status precedence ensures paid is Lunas, pending is Menunggu, rejected preserves resubmission, and future unpaid is Belum dapat dibayar', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 10, 0, 0, (string) config('app.timezone')));

    // 1. Paid bill
    $paidBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Juli 2026',
        'amount' => 150000,
        'billing_period' => '2026-07-01',
        'status' => 'paid',
    ]);
    Payment::create([
        'bill_id' => $paidBill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-PAID-01',
        'amount' => 150000,
        'status' => 'paid',
    ]);

    // 2. Pending bill
    $pendingBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Agustus 2026',
        'amount' => 150000,
        'billing_period' => '2026-08-01',
        'status' => 'unpaid',
    ]);
    Payment::create([
        'bill_id' => $pendingBill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-PEND-01',
        'amount' => 150000,
        'status' => 'pending',
    ]);

    // 3. Rejected payment on future bill: realistic state (Payment.status = pending, latestVerification.status = rejected)
    // Resubmission must NOT be blocked, and UI must treat it as rejected, NOT pending!
    $rejectedBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Oktober 2026 Ditolak',
        'amount' => 200000,
        'billing_period' => '2026-10-01',
        'due_date' => '2026-10-20',
        'status' => 'unpaid',
    ]);
    $rejectedPayment = Payment::create([
        'bill_id' => $rejectedBill->id,
        'payer_id' => $this->guardianUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-REJ-01',
        'amount' => 200000,
        'status' => 'pending', // Realistic SikolaPay state
        'proof_of_payment' => 'payments/proofs/sample.jpg',
        'proof_uploaded_at' => now()->subDay(),
    ]);
    PaymentVerification::create([
        'payment_id' => $rejectedPayment->id,
        'admin_id' => $this->adminUser->id,
        'status' => 'rejected',
        'note' => 'Bukti transfer tidak terbaca',
        'processed_at' => now(),
    ]);

    // 4. Pure future unpaid bill
    $futureBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP November 2026',
        'amount' => 200000,
        'billing_period' => '2026-11-01',
        'due_date' => '2026-11-20',
        'status' => 'unpaid',
    ]);

    // Test Guardian Bills Index
    $response = $this->actingAs($this->guardianUser)->get(route('guardian.bills.index'));
    $response->assertOk();

    // Semua bill harus tetap terlihat
    $response->assertSee('SPP Juli 2026');
    $response->assertSee('SPP Agustus 2026');
    $response->assertSee('SPP Oktober 2026 Ditolak');
    $response->assertSee('SPP November 2026');

    // Status precedence assertions
    $response->assertSee('Lunas');
    $response->assertSee('Menunggu Verifikasi');
    $response->assertSee('Ditolak');
    $response->assertSee('Bayar Lagi'); // Resubmission link preserved!
    $response->assertSee('Belum dapat dibayar');
    $response->assertSee('Tersedia mulai 1 November 2026');

    // Future bill does NOT offer active payment link
    $guardianPaymentCreateUrl = route('guardian.payments.create', $futureBill->id);
    $response->assertDontSee('href="'.$guardianPaymentCreateUrl.'"', false);

    // Test Guardian Dashboard: precedence must show Ditolak and Bayar Lagi
    $dashboardResponse = $this->actingAs($this->guardianUser)->get(route('guardian.dashboard'));
    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee('SPP Oktober 2026 Ditolak');
    $dashboardResponse->assertSee('Ditolak');
    $dashboardResponse->assertSee('Bayar Lagi');

    // Test Guardian Bills Show for future unpaid
    $showResponse = $this->actingAs($this->guardianUser)->get(route('guardian.bills.show', $futureBill->id));
    $showResponse->assertOk();
    $showResponse->assertSee('Belum dapat dibayar');
    $showResponse->assertSee('Tersedia mulai 1 November 2026');
    $showResponse->assertSee('Periode Tagihan');
    $showResponse->assertSee('Jatuh Tempo');
    $showResponse->assertDontSee('href="'.$guardianPaymentCreateUrl.'"', false);

    // Test Guardian Bills Show for rejected payment bill: resubmission is available
    $rejectedShowResponse = $this->actingAs($this->guardianUser)->get(route('guardian.bills.show', $rejectedBill->id));
    $rejectedShowResponse->assertOk();
    $rejectedShowResponse->assertSee('Ditolak');
    $rejectedShowResponse->assertSee('Bayar Lagi');
    $rejectedPaymentUrl = route('guardian.payments.create', $rejectedBill->id);
    $rejectedShowResponse->assertSee('href="'.$rejectedPaymentUrl.'"', false);

    // Test Guardian Payment Create (Form): resubmission on rejected future bill is NOT disabled
    $guardianFormResponse = $this->actingAs($this->guardianUser)->get(route('guardian.payments.create', $rejectedBill->id));
    $guardianFormResponse->assertOk();
    $guardianFormResponse->assertDontSee('Tagihan ini adalah tagihan periode mendatang dan belum dapat dibayar.');
    $guardianFormResponse->assertSee('Konfirmasi Pembayaran');

    // Compare with pure future unpaid bill in Guardian Payment Create: IT IS disabled
    $futureFormResponse = $this->actingAs($this->guardianUser)->get(route('guardian.payments.create', $futureBill->id));
    $futureFormResponse->assertOk();
    $futureFormResponse->assertSee('Tagihan ini adalah tagihan periode mendatang dan belum dapat dibayar.');
    $futureFormResponse->assertSee('Belum Dapat Dibayar');
});

test('admin manual payment selector excludes future billing period bills', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 10, 0, 0, (string) config('app.timezone')));

    $currentBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP September 2026',
        'amount' => 200000,
        'billing_period' => '2026-09-01',
        'status' => 'unpaid',
    ]);

    $futureBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Oktober 2026 Future',
        'amount' => 250000,
        'billing_period' => '2026-10-01',
        'status' => 'unpaid',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.payments.create'));
    $response->assertOk();
    $response->assertSee('Hanya tagihan periode berjalan/lampau yang belum lunas dan tidak sedang menunggu verifikasi yang ditampilkan.');
    $response->assertViewHas('bills', function ($bills) use ($currentBill, $futureBill) {
        return $bills->contains('id', $currentBill->id) && ! $bills->contains('id', $futureBill->id);
    });
});

test('student view: rejected payment on future bill allows resubmission and does not disable form', function () {
    $this->travelTo(Carbon::create(2026, 9, 30, 10, 0, 0, (string) config('app.timezone')));

    $rejectedBill = Bill::create([
        'student_id' => $this->student->id,
        'name' => 'SPP Oktober 2026 Student Ditolak',
        'amount' => 200000,
        'billing_period' => '2026-10-01',
        'due_date' => '2026-10-20',
        'status' => 'unpaid',
    ]);

    $payment = Payment::create([
        'bill_id' => $rejectedBill->id,
        'payer_id' => $this->studentUser->id,
        'payment_method_id' => $this->paymentMethod->id,
        'payment_number' => 'PAY-STU-REJ-01',
        'amount' => 200000,
        'status' => 'pending',
        'proof_of_payment' => 'payments/proofs/student-sample.jpg',
        'proof_uploaded_at' => now()->subDay(),
    ]);

    PaymentVerification::create([
        'payment_id' => $payment->id,
        'admin_id' => $this->adminUser->id,
        'status' => 'rejected',
        'note' => 'Bukti buram, mohon upload ulang',
        'processed_at' => now(),
    ]);

    // 1. Student bills index: shows Ditolak and Upload Ulang Bukti
    $indexResponse = $this->actingAs($this->studentUser)->get(route('student.bills.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('SPP Oktober 2026 Student Ditolak');
    $indexResponse->assertSee('Ditolak');
    $indexResponse->assertSee('Upload Ulang Bukti');

    // 2. Student bill detail: shows Bukti Pembayaran Ditolak and Upload Ulang Bukti
    $detailResponse = $this->actingAs($this->studentUser)->get(route('student.bills.show', $rejectedBill->id));
    $detailResponse->assertOk();
    $detailResponse->assertSee('Bukti Pembayaran Ditolak');
    $detailResponse->assertSee('Upload Ulang Bukti');
    $detailResponse->assertDontSee('Belum dapat dibayar');

    // 3. Student payment form: form is NOT disabled with "Belum Dapat Dibayar"
    $paymentResponse = $this->actingAs($this->studentUser)->get(route('student.payment', $rejectedBill->id));
    $paymentResponse->assertOk();
    $paymentResponse->assertDontSee('Tagihan ini adalah tagihan periode mendatang dan belum dapat dibayar.');
    $paymentResponse->assertSee('Konfirmasi Pembayaran');

    // 4. Student confirm resubmission: backend succeeds (200 OK)
    $confirmResponse = $this->actingAs($this->studentUser)->postJson(route('student.payment.confirm', $rejectedBill->id), [
        'payment_method_id' => $this->paymentMethod->id,
        'proof_of_payment' => UploadedFile::fake()->image('resubmit-proof.jpg'),
    ]);
    $confirmResponse->assertOk();
    $confirmResponse->assertJson([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil dikirim ulang.',
    ]);
});
