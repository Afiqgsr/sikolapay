<?php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Guardian\BillController;
use App\Http\Controllers\Guardian\PaymentController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {

        /** @var User $user */
        $user = Auth::user();

        return match ($user->role) {

            'admin' => redirect()->route('admin.dashboard'),

            'guardian' => redirect()->route('guardian.dashboard'),

            'student' => redirect()->route('student.dashboard'),

            'super_admin' => redirect()->route('super_admin.dashboard'),

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
});

Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {

    Route::get('/super-admin/dashboard', function () {
        return view('super_admin.dashboard');
    })->name('super_admin.dashboard');

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
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
        ->name('student.dashboard');
});

require __DIR__.'/settings.php';