<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SikolaPay - Verifikasi Email</title>

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
                    Verifikasi Email
                </h2>

                <p>
                    Kami telah mengirimkan link verifikasi ke email Anda.
                    Silakan buka email tersebut untuk memverifikasi akun.
                </p>

            </div>


            {{-- Status --}}
            @if(session('status') === 'verification-link-sent')

                <div class="login-status">
                    Link verifikasi baru telah dikirim ke email Anda.
                </div>

            @endif


            {{-- Resend --}}
            <form
                method="POST"
                action="{{ route('verification.send') }}"
                class="login-form"
            >

                @csrf

                <button
                    type="submit"
                    class="btn-login"
                >
                    Kirim Ulang Email Verifikasi
                </button>

            </form>


            {{-- Logout --}}
            <form
                method="POST"
                action="{{ route('logout') }}"
                class="login-form"
            >

                @csrf

                <button
                    type="submit"
                    class="forgot-password-link forgot-password-back"
                    style="background: transparent; border: 0; cursor: pointer;"
                >
                    Logout
                </button>

            </form>

        </section>

    </main>

</body>

</html>