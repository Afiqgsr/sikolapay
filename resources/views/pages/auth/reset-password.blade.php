<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SikolaPay - Reset Password</title>

    @vite([
        'resources/css/styleguide.css',
        'resources/css/globals.css',
        'resources/css/pages/login.css',
    ])
</head>

<body>

    <main class="login-page">

        <section class="login-card">

            {{-- Brand --}}
            <div class="login-brand">

                <img
                    src="{{ asset('assets/img/logo-sikolapay.svg') }}"
                    alt="Logo SikolaPay"
                    class="login-brand__logo"
                >

                <h1 class="login-brand__title">
                    <span class="text-secondary">Si</span><span class="text-tertiary">kola</span><span class="text-optional">Pay</span>
                </h1>

                <p class="login-brand__subtitle">
                    Sistem Pembayaran Sekolah
                </p>

            </div>


            {{-- Header --}}
            <div class="login-header">

                <h2>
                    Reset Password
                </h2>

                <p>
                    Masukkan password baru untuk akun Anda.
                </p>

            </div>


            {{-- Reset form --}}
            <form
                method="POST"
                action="{{ route('password.update') }}"
                class="login-form"
            >

                @csrf


                {{-- Token --}}
                <input
                    type="hidden"
                    name="token"
                    value="{{ request()->route('token') }}"
                >


                {{-- Email --}}
                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', request('email')) }}"
                        placeholder="Masukkan Email"
                        autocomplete="email"
                        required
                        readonly
                    >

                    @error('email')

                        <span class="form-error">
                            {{ $message }}
                        </span>

                    @enderror

                </div>


                {{-- Password --}}
                <div class="form-group">

                    <label for="password">
                        Password Baru
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan Password Baru"
                        autocomplete="new-password"
                        required
                    >

                    @error('password')

                        <span class="form-error">
                            {{ $message }}
                        </span>

                    @enderror

                </div>


                {{-- Confirm password --}}
                <div class="form-group">

                    <label for="password_confirmation">
                        Konfirmasi Password
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Konfirmasi Password Baru"
                        autocomplete="new-password"
                        required
                    >

                </div>


                {{-- Submit --}}
                <button
                    type="submit"
                    class="btn-login"
                >
                    Reset Password
                </button>


                {{-- Back --}}
                <a
                    href="{{ route('login') }}"
                    class="forgot-password-link forgot-password-back"
                >
                    Kembali ke Login
                </a>

            </form>

        </section>

    </main>

</body>

</html>