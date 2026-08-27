<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        //
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));

        Fortify::verifyEmailView(
            fn () => view('pages::auth.verify-email')
        );

        Fortify::resetPasswordView(
            fn () => view('pages::auth.reset-password')
        );

        Fortify::requestPasswordResetLinkView(
            fn () => view('pages::auth.forgot-password')
        );
    }

    /**
     * Configure authentication.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {

            $login = $request->input('email');
            $password = $request->input('password');

            /*
             * Login menggunakan email.
             */
            $user = User::where('email', $login)->first();

            /*
             * Jika bukan email, coba cari berdasarkan NIS student.
             */
            if (! $user) {
                $student = \App\Models\Student::where('nis', $login)
                    ->first();

                if ($student) {
                    $user = $student->user;
                }
            }

            /*
             * User tidak ditemukan atau password salah.
             */
            if (! $user || ! Hash::check($password, $user->password)) {
                return null;
            }

            return $user;
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {

            $throttleKey = Str::transliterate(
                Str::lower(
                    $request->input(Fortify::username())
                ) . '|' . $request->ip()
            );

            return Limit::perMinute(5)
                ->by($throttleKey);
        });
    }
}