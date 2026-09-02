<aside class="sidebar">

    <!-- Logo -->
    <div class="logo-section">

        <img
            src="{{ asset('assets/img/logo-sikolapay.svg') }}"
            alt="Logo"
            class="logo-image"
        >

        <div class="logo-content">

            <h2 class="logo-title">
                <span class="logo-red">Si</span><span class="logo-orange">kola</span><span class="logo-yellow">Pay</span>
            </h2>

            <p class="logo-subtitle">
                Admin
            </p>

        </div>

    </div>

    <!-- Menu -->
    <nav class="sidebar-menu">

        <a
            href="{{ route('admin.dashboard') }}"
            class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Home.svg') }}"
                alt="Dashboard"
            >

            <span>Dashboard</span>

        </a>

        <a
            href="{{ route('admin.students.index') }}"
            class="menu-item {{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/User_alt_light.svg') }}"
                alt="Data Siswa"
            >

            <span>Data Siswa</span>

        </a>

        <a
            href="{{ route('admin.bills.index') }}"
            class="menu-item {{ request()->routeIs('admin.bills.*') ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Date_range_light.svg') }}"
                alt="Data Tagihan"
            >

            <span>Data Tagihan</span>

        </a>

        <a
            href="{{ route('admin.payments.index') }}"
            class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Time_light.svg') }}"
                alt="Verifikasi Pembayaran"
            >

            <span>Verifikasi Pembayaran</span>

        </a>

        <a
            href="{{ route('admin.reports.index') }}"
            class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/File_dock.svg') }}"
                alt="Laporan Pembayaran"
            >

            <span>Laporan Pembayaran</span>

        </a>

    </nav>

    <!-- Profile -->
    <div class="sidebar-profile">

        <div class="profile-info">

            <div class="avatar">
                {{ auth()->user()->initials() }}
            </div>

            <div>

                <div class="profile-name">
                    {{ auth()->user()->name }}
                </div>

                <div class="profile-id">
                    Admin
                </div>

            </div>

        </div>

        <form
            action="{{ route('logout') }}"
            method="POST"
            class="logout-form"
        >
            @csrf

            <button
                type="submit"
                class="logout-button"
            >

                <img
                    src="{{ asset('assets/img/Sign_out_squre.svg') }}"
                    alt="Logout"
                >

            </button>

        </form>

    </div>

</aside>