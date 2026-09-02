<?php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Guardian\BillController;
use App\Http\Controllers\Guardian\PaymentController;
use App\Http\Controllers\Guardian\PaymentHistoryController as GuardianPaymentHistoryController;
use App\Http\Controllers\Guardian\ProfileController as GuardianProfileController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\BillController as AdminBillController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Student\BillController as StudentBillController;
use App\Http\Controllers\Student\PaymentHistoryController;
use App\Http\Controllers\Student\PaymentController as StudentPaymentController;
use App\Http\Controllers\Student\PaymentReceiptController as StudentPaymentReceiptController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\AdminController as SuperAdminAdminController;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {

        /** @var User $user */
        $user = Auth::user();

        return match ($user->role) {

            'admin' => redirect()->route('admin.dashboard'),

            'guardian' => redirect()->route('guardian.dashboard'),

            'student' => redirect()->route('student.dashboard'),

            'super_admin' => redirect()->route('superadmin.dashboard'),

            default => abort(403),

        };

    })->name('dashboard');

});

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/admin/payments', [AdminPaymentController::class, 'index'])
        ->name('admin.payments.index');

    Route::get('/admin/payments/{id}', [AdminPaymentController::class, 'show'])
        ->name('admin.payments.show');

    Route::post('/admin/payments/{id}/verify', [AdminPaymentController::class, 'verify'])
        ->name('admin.payments.verify');

    Route::post('/admin/payments/{id}/reject', [AdminPaymentController::class, 'reject'])
        ->name('admin.payments.reject');

    Route::get('/admin/students',[AdminStudentController::class, 'index'])
        ->name('admin.students.index');

    Route::post('/admin/students', [AdminStudentController::class, 'store'])
    ->name('admin.students.store');

    Route::put('/admin/students/{student}', [AdminStudentController::class, 'update'])
        ->name('admin.students.update');

    Route::delete('/admin/students/{student}', [AdminStudentController::class, 'destroy'])
        ->name('admin.students.destroy');

    Route::get('/admin/bills', [AdminBillController::class, 'index'])
    ->name('admin.bills.index');

    Route::post('/admin/bills', [AdminBillController::class, 'store'])
        ->name('admin.bills.store');

    Route::put('/admin/bills/{bill}', [AdminBillController::class, 'update'])
        ->name('admin.bills.update');

    Route::delete('/admin/bills/{bill}', [AdminBillController::class, 'destroy'])
        ->name('admin.bills.destroy');

    Route::get('/admin/reports', [ReportController::class, 'index'])
        ->name('admin.reports.index');

});


Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {

    Route::get('/superadmin/dashboard', [SuperAdminDashboardController::class, 'index'])
        ->name('superadmin.dashboard');

    Route::get('/superadmin/admins', [SuperAdminAdminController::class, 'index'])
        ->name('superadmin.admins.index');

    Route::post('/superadmin/admins', [SuperAdminAdminController::class, 'store'])
        ->name('superadmin.admins.store');

    Route::put('/superadmin/admins/{admin}', [SuperAdminAdminController::class, 'update'])
        ->name('superadmin.admins.update');

    Route::delete('/superadmin/admins/{admin}', [SuperAdminAdminController::class, 'destroy'])
        ->name('superadmin.admins.destroy');

});


Route::middleware(['auth', 'role:guardian'])->group(function () {

    Route::get('/guardian/dashboard', [GuardianDashboardController::class, 'index'])
        ->name('guardian.dashboard');

    Route::get('/guardian/bills', [BillController::class, 'index'])
        ->name('guardian.bills.index');

    Route::get('/guardian/bills/{id}', [BillController::class, 'show'])
        ->name('guardian.bills.show');

    Route::get('/guardian/bills/{id}/pay', [PaymentController::class, 'create'])
        ->name('guardian.payments.create');

    Route::post('/guardian/payments', [PaymentController::class, 'store'])
        ->name('guardian.payments.store');

    Route::get('/guardian/payments/{id}', [PaymentController::class, 'show'])
        ->name('guardian.payments.show');

    Route::post('/guardian/payments/{id}/proof', [PaymentController::class, 'uploadProof'])
        ->name('guardian.payments.proof');

    Route::get('/guardian/payment-history', [GuardianPaymentHistoryController::class, 'index'])
        ->name('guardian.payment-history');

    Route::get('/guardian/payments/{id}/receipt', [PaymentController::class, 'receipt'])
        ->name('guardian.payments.receipt');

    Route::get('/guardian/profile', [GuardianProfileController::class, 'show'])
        ->name('guardian.profile');

    Route::get('/guardian/profile/edit', [GuardianProfileController::class, 'edit'])
        ->name('guardian.profile.edit');

    Route::put('/guardian/profile', [GuardianProfileController::class, 'update'])
        ->name('guardian.profile.update');

    Route::get('/guardian/profile/password', [GuardianProfileController::class, 'editPassword'])
        ->name('guardian.profile.password.edit');

    Route::put('/guardian/profile/password', [GuardianProfileController::class, 'updatePassword'])
        ->name('guardian.profile.password.update');

});

Route::middleware(['auth', 'role:student'])->group(function () {

    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
        ->name('student.dashboard');

    Route::get('/student/profile', [StudentProfileController::class, 'index'])
        ->name('student.profile');

    Route::get('/student/profile/edit', [StudentProfileController::class, 'edit'])
        ->name('student.profile.edit');
    
    Route::put('/student/profile', [StudentProfileController::class, 'update'])
        ->name('student.profile.update');

    Route::get('/student/bills', [StudentBillController::class, 'index'])
        ->name('student.bills.index');

    Route::get('/student/payment/all', [StudentPaymentController::class, 'all'])
        ->name('student.payment.all');
    
    Route::get('/student/payment/all/confirm', [StudentPaymentController::class, 'allConfirm'])
        ->name('student.payment.all.confirm');

    Route::post('/student/payment/all/confirm', [StudentPaymentController::class, 'confirmAll'])
        ->name('student.payment.all.confirm.store');

    Route::get('/student/payment-history', [PaymentHistoryController::class, 'index'])
        ->name('student.payment-history');

    Route::get('/student/payment/{id}', [StudentPaymentController::class, 'create'])
        ->name('student.payment');
    
    Route::post('/student/payment/{id}/confirm', [StudentPaymentController::class, 'confirm'])
        ->name('student.payment.confirm');

    Route::get('/student/payment/{id}/receipt', [StudentPaymentReceiptController::class, 'show'])
        ->name('student.payment.receipt');

    Route::get('/student/bills/{id}', [StudentBillController::class, 'show'])
        ->name('student.bills.show');

});


require __DIR__.'/settings.php';