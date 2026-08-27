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

    {{-- SIDEBAR --}}
    <aside class="sidebar">

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

                <span>Dashboard</span>
            </a>


            <a
                href="{{ route('student.bills') }}"
                class="menu-item"
            >
                <img
                    src="{{ asset('resource/img/Date_range_light.svg') }}"
                    alt=""
                >

                <span>Tagihan Saya</span>
            </a>


            <a
                href="{{ route('student.payment-history') }}"
                class="menu-item"
            >
                <img
                    src="{{ asset('resource/img/Time_light.svg') }}"
                    alt=""
                >

                <span>Riwayat Pembayaran</span>
            </a>


            <a
                href="{{ route('student.profile') }}"
                class="menu-item"
            >
                <img
                    src="{{ asset('resource/img/User_alt_light.svg') }}"
                    alt=""
                >

                <span>Profil</span>
            </a>

        </nav>


        {{-- PROFILE SIDEBAR --}}
        <div class="sidebar-profile">

            <div class="profile-info">

                <div class="avatar">
                    {{ $student->user?->initials() ?? 'S' }}
                </div>

                <div>

                    <div class="profile-name">
                        {{ $student->user?->name ?? $student->name }}
                    </div>

                    <div class="profile-id">
                        {{ $student->nisn ?? '-' }}
                    </div>

                </div>

            </div>


            <form
                action="{{ route('logout') }}"
                method="POST"
            >
                @csrf

                <button
                    type="submit"
                    style="background:none;border:none;padding:0;"
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


    {{-- MAIN CONTENT --}}
    <main class="main-content">


        {{-- TOPBAR --}}
        <header class="topbar">

            <div class="topbar-left">

                <button
                    class="menu-toggle"
                    id="menuToggle"
                    type="button"
                >
                    ☰
                </button>

                <h1 class="page-title">
                    Dashboard
                </h1>

            </div>


            <div class="topbar-actions">

                <img
                    src="{{ asset('resource/img/Bell_pin.svg') }}"
                    alt="Notifikasi"
                >

                <div class="avatar small">
                    {{ $student->user?->initials() ?? 'S' }}
                </div>

            </div>

        </header>


        {{-- CONTENT --}}

        <section class="welcome-section">

            <h2>
                Selamat Datang,
                {{ $student->user?->name ?? $student->name }}
            </h2>

            <p>
                Kelas {{ $student->classRoom?->name ?? '-' }}
                ·
                NISN : {{ $student->nisn ?? '-' }}
                ·
                TA {{ $academicYearName }}
            </p>

        </section>


        {{-- STATISTIK --}}

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
                        {{ $totalBills }} Tagihan
                    </h3>

                    <small>
                        Tahun Ajaran {{ $academicYearName }}
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
                        {{ $unpaidBills }} Tagihan
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
                        {{ $paidBills }} Tagihan
                    </h3>

                    <small>
                        Tahun Ajaran Ini
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
                        Rp {{ number_format($totalAmount, 0, ',', '.') }}
                    </h3>

                    <small>
                        Total Seluruh Tagihan
                    </small>

                </div>

            </div>

        </section>


        {{-- TAGIHAN TERDEKAT --}}

        @if($nearestBill)

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

                            @if($nearestBill->due_date)
                                akan jatuh tempo pada
                                {{ \Carbon\Carbon::parse($nearestBill->due_date)->translatedFormat('d F Y') }}.
                            @endif

                            Segera lakukan pembayaran.

                        </p>

                    </div>


                    <a
                        href="{{ route('student.bills') }}"
                        class="dashboard-alert-button"
                    >
                        Bayar Sekarang
                    </a>

                </div>

            </section>

        @endif


        {{-- GRID --}}

        <div class="dashboard-grid">


            {{-- TAGIHAN AKTIF --}}
            <section class="invoice-section">


                <div class="section-header">

                    <h3>
                        Tagihan Aktif
                    </h3>

                    <a href="{{ route('student.bills') }}">
                        Lihat Semua
                    </a>

                </div>


                <div class="invoice-list">


                    @forelse($activeBills->take(4) as $bill)

                        <div class="invoice-item">


                            <div class="invoice-info">

                                <div class="invoice-title">
                                    {{ $bill->name ?? $bill->title ?? 'Tagihan' }}
                                </div>

                                <div class="invoice-date">

                                    Jatuh tempo:

                                    @if($bill->due_date)

                                        {{ \Carbon\Carbon::parse($bill->due_date)->translatedFormat('d M Y') }}

                                    @else

                                        -

                                    @endif

                                </div>

                            </div>


                            <div class="invoice-price">

                                Rp {{ number_format($bill->amount, 0, ',', '.') }}

                            </div>


                            <div class="invoice-action">

                                <span class="badge warning">
                                    Belum Bayar
                                </span>


                                <a
                                    href="{{ route('student.payment', $bill->id) }}"
                                    class="dashboard-invoice-button"
                                >
                                    Bayar
                                </a>

                            </div>

                        </div>

                    @empty

                        <div class="dashboard-empty">

                            Tidak ada tagihan aktif.

                        </div>

                    @endforelse


                </div>

            </section>


            {{-- SIDEBAR KANAN --}}

            <aside class="right-sidebar">


                {{-- INFORMASI SISWA --}}

                <section class="student-info">

                    <h3>
                        Informasi Siswa
                    </h3>


                    <div class="student-profile">

                        <div class="avatar">

                            {{ $student->user?->initials() ?? 'S' }}

                        </div>


                        <div>

                            <h4>
                                {{ $student->user?->name ?? $student->name }}
                            </h4>

                            <p>
                                {{ $student->classRoom?->name ?? '-' }}
                            </p>

                        </div>

                    </div>


                    <div class="student-detail">


                        <div class="detail-item">

                            <span>
                                NISN
                            </span>

                            <strong>
                                {{ $student->nisn ?? '-' }}
                            </strong>

                        </div>


                        <div class="detail-item">

                            <span>
                                NIS
                            </span>

                            <strong>
                                {{ $student->nis ?? '-' }}
                            </strong>

                        </div>


                        <div class="detail-item">

                            <span>
                                Wali
                            </span>

                            <strong>
                                {{ $student->guardian?->name ?? '-' }}
                            </strong>

                        </div>


                        <div class="detail-item">

                            <span>
                                Tahun Ajaran
                            </span>

                            <strong>
                                {{ $academicYearName }}
                            </strong>

                        </div>


                    </div>

                </section>


                {{-- AKSI CEPAT --}}

                <section class="quick-action">

                    <h3>
                        Aksi Cepat
                    </h3>


                    <a href="{{ route('student.bills') }}">

                        <img
                            src="{{ asset('resource/img/Icon-set-pr.svg') }}"
                            alt=""
                        >

                        <span>
                            Lihat Semua Tagihan
                        </span>

                    </a>


                    <a href="{{ route('student.payment-history') }}">

                        <img
                            src="{{ asset('resource/img/File_dock.svg') }}"
                            alt=""
                        >

                        <span>
                            Riwayat Pembayaran
                        </span>

                    </a>


                    <a href="{{ route('student.payment-history') }}">

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


<script src="{{ asset('js/script.js') }}"></script>

</body>
</html>