<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SikolaPay - Lupa Password</title>

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
                    Lupa Password?
                </h2>

                <p>
                    Masukkan email Anda untuk menerima link reset password.
                </p>

            </div>


            {{-- Session status --}}
            @if(session('status'))

                <div class="login-status">
                    Link reset password telah dikirim ke email Anda.
                </div>

            @endif


            {{-- Form --}}
            <form
                method="POST"
                action="{{ route('password.email') }}"
                class="login-form"
            >

                @csrf


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Masukkan Email"
                        autocomplete="email"
                        required
                        autofocus
                    >

                    @error('email')

                        <span class="form-error">
                            {{ $message }}
                        </span>

                    @enderror

                </div>


                <button
                    type="submit"
                    class="btn-login"
                >
                    Kirim Link Reset Password
                </button>


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