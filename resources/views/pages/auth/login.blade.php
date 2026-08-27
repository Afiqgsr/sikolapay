<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SikolaPay - Login</title>

    @vite([
        'resources/css/styleguide.css',
        'resources/css/globals.css',
        'resources/css/pages/login.css',
    ])
</head>

<body>

    <main class="login-page">

        <section class="login-card">

            {{-- BRAND --}}
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


            {{-- HEADER --}}
            <div class="login-header">

                <h2>
                    Hai, Selamat Datang!
                </h2>

                <p>
                    Masukkan Email / NIS untuk melanjutkan
                </p>

            </div>


            {{-- ERROR / SESSION STATUS --}}
            @if (session('status'))
                <div class="login-status">
                    {{ session('status') }}
                </div>
            @endif


            {{-- LOGIN FORM --}}
            <form
                method="POST"
                action="{{ route('login.store') }}"
                class="login-form"
            >

                @csrf


                {{-- EMAIL / NIS --}}
                <div class="form-group">

                    <label for="email">
                        Email / NIS
                    </label>

                    <input
                        type="text"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Masukkan Email / NIS"
                        autocomplete="username"
                        required
                        autofocus
                    >

                    @error('email')
                        <span class="form-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- PASSWORD --}}
                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan Password"
                        autocomplete="current-password"
                        required
                    >

                    @error('password')
                        <span class="form-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

                {{-- LOGIN BUTTON --}}
                <button
                    type="submit"
                    class="btn-login"
                >
                    Login
                </button>

            </form>

        </section>

    </main>

</body>

</html>
