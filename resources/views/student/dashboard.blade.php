<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Siswa - SikolaPay</title>

    <link rel="stylesheet" href="{{ asset('css/globals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleguide.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

<div class="dashboard-layout">

    {{--===============================  SIDEBAR  ===============================--}}
    <aside class="sidebar">

        {{-- LOGO --}}
        <div class="logo-section">

            <img
                src="{{ asset('resource/img/logo-sikolapay.svg') }}"
                alt="Logo SikolaPay"
            >

            <div>

                <h2 class="logo-title">
                    <span class="logo-red">Si</span><span class="logo-orange">kola</span><span class="logo-yellow">Pay</span>
                </h2>

                <p class="logo-subtitle">
                    Siswa / Orang Tua
                </p>

            </div>

        </div>


        {{-- MENU --}}
        <nav class="sidebar-menu">

            <a
                href="{{ route('student.dashboard') }}"
                class="menu-item active"
            >
                <img
                    src="{{ asset('resource/img/Home.svg') }}"
                    alt=""
                >

                <span>
                    Dashboard
                </span>
            </a>


            {{-- TAGIHAN --}}
            <a
                href="#"
                class="menu-item"
            >
                <img
                    src="{{ asset('resource/img/Date_range_light.svg') }}"
                    alt=""
                >

                <span>
                    Tagihan Saya
                </span>
            </a>


            {{-- RIWAYAT --}}
            <a
                href="#"
                class="menu-item"
            >
                <img
                    src="{{ asset('resource/img/Time_light.svg') }}"
                    alt=""
                >

                <span>
                    Riwayat Pembayaran
                </span>
            </a>


            {{-- PROFIL --}}
            <a
                href="#"
                class="menu-item"
            >
                <img
                    src="{{ asset('resource/img/User_alt_light.svg') }}"
                    alt=""
                >

                <span>
                    Profil
                </span>
            </a>

        </nav>


        {{-- PROFILE USER --}}
        <div class="sidebar-profile">

            <div class="profile-info">

                <div class="avatar">

                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}

                </div>


                <div>

                    <div class="profile-name">
                        {{ auth()->user()->name ?? '-' }}
                    </div>

                    <div class="profile-id">
                        {{ auth()->user()->id ?? '-' }}
                    </div>

                </div>

            </div>


            {{-- LOGOUT --}}
            <form
                method="POST"
                action="{{ route('logout') }}"
            >

                @csrf

                <button
                    type="submit"
                    class="logout-icon-button"
                    title="Logout"
                >

                    <img
                        src="{{ asset('resource/img/Sign_out_squre.svg') }}"
                        alt="Logout"
                    >

                </button>

            </form>

        </div>

    </aside>


    {{-- MOBILE OVERLAY --}}
    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    {{-- =========================================================
         MAIN CONTENT
         ========================================================= --}}
    <main class="main-content">


        {{-- =====================================================
             TOPBAR
             ===================================================== --}}
        <header class="topbar">

            <div class="topbar-left">

                <button
                    type="button"
                    class="menu-toggle"
                    id="menuToggle"
                    aria-label="Buka menu"
                >
                    ☰
                </button>


                <h1 class="page-title">
                    Dashboard
                </h1>

            </div>


            <div class="topbar-actions">

                {{-- NOTIFICATION --}}
                <img
                    src="{{ asset('resource/img/Bell_pin.svg') }}"
                    alt="Notifikasi"
                >


                {{-- AVATAR --}}
                <div class="avatar small">

                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}

                </div>

            </div>

        </header>


        {{-- =====================================================
             WELCOME
             ===================================================== --}}
        <section class="welcome-section">

            <h2>
                Selamat Datang,
                {{ auth()->user()->name ?? '-' }}
            </h2>


            <p>
                {{ $student->classroom->name ?? '-' }}
                ·
                NISN :
                {{ $student->nisn ?? '-' }}
                ·
                TA
                {{ $student->academicYear->name ?? '-' }}
            </p>

        </section>


        {{-- =====================================================
             STATISTICS
             ===================================================== --}}
        <section class="stats-section">


            {{-- TOTAL TAGIHAN --}}
            <div class="stat-card">

                <img
                    src="{{ asset('resource/img/Date_range_light2.svg') }}"
                    alt=""
                >

                <div>

                    <span>
                        Total Tagihan
                    </span>

                    <h3>
                        {{ $totalBills ?? 0 }} Tagihan
                    </h3>

                    <small>
                        {{ $semesterName ?? 'Semester Aktif' }}
                    </small>

                </div>

            </div>


            {{-- BELUM BAYAR --}}
            <div class="stat-card warning">

                <img
                    src="{{ asset('resource/img/Hhourglass_move_light.svg') }}"
                    alt=""
                >

                <div>

                    <span>
                        Belum Bayar
                    </span>

                    <h3>
                        {{ $unpaidBills ?? 0 }} Tagihan
                    </h3>

                    <small>
                        Segera Lunasi
                    </small>

                </div>

            </div>


            {{-- SUDAH LUNAS --}}
            <div class="stat-card success">

                <img
                    src="{{ asset('resource/img/Check_fill.svg') }}"
                    alt=""
                >

                <div>

                    <span>
                        Sudah Lunas
                    </span>

                    <h3>
                        {{ $paidBills ?? 0 }} Tagihan
                    </h3>

                    <small>
                        Semester Ini
                    </small>

                </div>

            </div>


            {{-- TOTAL NOMINAL --}}
            <div class="stat-card">

                <img
                    src="{{ asset('resource/img/Money.svg') }}"
                    alt=""
                >

                <div>

                    <span>
                        Total Nominal
                    </span>

                    <h3>
                        Rp {{ number_format($totalAmount ?? 0, 0, ',', '.') }}
                    </h3>

                    <small>
                        {{ $semesterName ?? 'Semester Aktif' }}
                    </small>

                </div>

            </div>

        </section>


        {{-- =====================================================
             ALERT TAGIHAN
             ===================================================== --}}
        @if(isset($nearestBill) && $nearestBill)

            <section class="alert-card">

                <img
                    src="{{ asset('resource/img/Hhourglass_move_light1.svg') }}"
                    alt=""
                >


                <div class="alert-content">

                    <div>

                        <h3>
                            Tagihan Akan Segera Jatuh Tempo
                        </h3>


                        <p>

                            {{ $nearestBill->name ?? 'Tagihan' }}

                            akan jatuh tempo pada

                            {{ isset($nearestBill->due_date)
                                ? \Carbon\Carbon::parse($nearestBill->due_date)->translatedFormat('d F Y')
                                : '-'
                            }}.

                            Segera lakukan pembayaran.

                        </p>

                    </div>


                    <a
                        href="#"
                        class="alert-button"
                    >
                        Bayar Sekarang
                    </a>

                </div>

            </section>

        @endif


        {{-- =====================================================
             DASHBOARD GRID
             ===================================================== --}}
        <div class="dashboard-grid">


            {{-- =================================================
                 TAGIHAN AKTIF
                 ================================================= --}}
            <section class="invoice-section">

                <div class="section-header">

                    <h3>
                        Tagihan Aktif
                    </h3>


                    <a href="#">
                        Lihat Semua
                    </a>

                </div>


                <div class="invoice-list">

                    @forelse($activeBills ?? [] as $bill)

                        <div class="invoice-item">


                            {{-- INFORMASI --}}
                            <div class="invoice-info">

                                <div class="invoice-title">
                                    {{ $bill->name ?? '-' }}
                                </div>


                                <div class="invoice-date">

                                    Jatuh tempo:

                                    {{ isset($bill->due_date)
                                        ? \Carbon\Carbon::parse($bill->due_date)->translatedFormat('d M Y')
                                        : '-'
                                    }}

                                </div>

                            </div>


                            {{-- HARGA --}}
                            <div class="invoice-price">

                                Rp
                                {{ number_format($bill->amount ?? 0, 0, ',', '.') }}

                            </div>


                            {{-- STATUS --}}
                            <div class="invoice-action">


                                @if(($bill->status ?? '') === 'paid')

                                    <span class="badge success">
                                        Lunas
                                    </span>

                                @else

                                    <span class="badge warning">
                                        Belum Bayar
                                    </span>


                                    <a
                                        href="#"
                                        class="invoice-pay-button"
                                    >
                                        Bayar
                                    </a>

                                @endif

                            </div>

                        </div>

                    @empty

                        <div class="invoice-empty">

                            <p>
                                Tidak ada tagihan aktif.
                            </p>

                        </div>

                    @endforelse

                </div>

            </section>


            {{-- =================================================
                 RIGHT SIDEBAR
                 ================================================= --}}
            <aside class="right-sidebar">


                {{-- =============================================
                     INFORMASI SISWA
                     ============================================= --}}
                <section class="student-info">

                    <h3>
                        Informasi Siswa
                    </h3>


                    <div class="student-profile">

                        <div class="avatar">

                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}

                        </div>


                        <div>

                            <h4>
                                {{ auth()->user()->name ?? '-' }}
                            </h4>


                            <p>
                                {{ $student->classroom->name ?? '-' }}
                            </p>

                        </div>

                    </div>


                    <div class="student-detail">


                        {{-- NISN --}}
                        <div class="detail-item">

                            <span>
                                NISN
                            </span>

                            <strong>
                                {{ $student->nisn ?? '-' }}
                            </strong>

                        </div>


                        {{-- NIS --}}
                        <div class="detail-item">

                            <span>
                                NIS
                            </span>

                            <strong>
                                {{ $student->nis ?? '-' }}
                            </strong>

                        </div>


                        {{-- WALI --}}
                        <div class="detail-item">

                            <span>
                                Wali
                            </span>

                            <strong>
                                {{ $student->guardian->name ?? '-' }}
                            </strong>

                        </div>


                        {{-- TAHUN AJARAN --}}
                        <div class="detail-item">

                            <span>
                                Tahun Ajaran
                            </span>

                            <strong>
                                {{ $student->academicYear->name ?? '-' }}
                            </strong>

                        </div>

                    </div>

                </section>


                {{-- =============================================
                     QUICK ACTION
                     ============================================= --}}
                <section class="quick-action">

                    <h3>
                        Aksi Cepat
                    </h3>


                    {{-- TAGIHAN --}}
                    <a href="#">

                        <img
                            src="{{ asset('resource/img/Icon-set-pr.svg') }}"
                            alt=""
                        >

                        <span>
                            Lihat Semua Tagihan
                        </span>

                    </a>


                    {{-- RIWAYAT --}}
                    <a href="#">

                        <img
                            src="{{ asset('resource/img/File_dock.svg') }}"
                            alt=""
                        >

                        <span>
                            Riwayat Pembayaran
                        </span>

                    </a>


                    {{-- NOTA --}}
                    <a href="#">

                        <img
                            src="{{ asset('resource/img/Icon-set.svg') }}"
                            alt=""
                        >

                        <span>
                            Cek Nota
                        </span>

                    </a>

                </section>

            </aside>

        </div>

    </main>

</div>


{{-- =============================================================
     JAVASCRIPT
     ============================================================= --}}
<script src="{{ asset('js/script.js') }}"></script>

</body>
</html>