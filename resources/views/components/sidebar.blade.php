<aside class="sidebar">

    <!-- LOGO -->
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
                Siswa / Orang Tua
            </p>
        </div>
    </div>


    <!-- MENU -->
    <nav class="sidebar-menu">

        <!-- DASHBOARD -->
        <a href="{{ route('student.dashboard') }}"
           class="menu-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">

            <img 
                src="{{ asset('assets/img/Home.svg') }}" 
                alt="Dashboard"
            >

            <span>Dashboard</span>
        </a>


        <!-- TAGIHAN -->
        <a href="{{ route('student.bills.index') }}"
           class="menu-item {{ request()->routeIs('student.bills.*') ? 'active' : '' }}">

            <img 
                src="{{ asset('assets/img/Date_range_light.svg') }}" 
                alt="Tagihan"
            >

            <span>Tagihan Saya</span>
        </a>


        <!-- RIWAYAT PEMBAYARAN -->
        <a href="{{ route('student.payment-history') }}"
           class="menu-item {{ request()->routeIs('student.payment-history') ? 'active' : '' }}">

            <img 
                src="{{ asset('assets/img/Time_light.svg') }}" 
                alt="Riwayat Pembayaran"
            >

            <span>Riwayat Pembayaran</span>
        </a>


        <!-- PROFIL -->
        <a href="{{ route('student.profile') }}"
           class="menu-item {{ request()->routeIs('student.profile') ? 'active' : '' }}">

            <img 
                src="{{ asset('assets/img/User_alt_light.svg') }}" 
                alt="Profil"
            >

            <span>Profil</span>
        </a>

    </nav>
    
    <!-- PROFILE -->
    <div class="sidebar-profile">

        <div class="profile-info">

            <!-- AVATAR -->
            <div class="avatar">
                {{ auth()->user()->initials() }}
            </div>

            <!-- DATA SISWA -->
            <div class="profile-name">
                {{ auth()->user()->student?->name ?? auth()->user()->name }}
            </div>

            <div class="profile-id">
                {{ auth()->user()->student?->nis ?? '-' }}
            </div>

        </div>


        <!-- LOGOUT -->
        <form action="{{ route('logout') }}" method="POST" class="logout-form">
            @csrf

            <button type="submit" class="logout-button">
                <img 
                    src="{{ asset('assets/img/Sign_out_squre.svg') }}" 
                    alt="Logout"
                >
            </button>
        </form>

    </div>

</aside>