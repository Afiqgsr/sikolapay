<aside class="sidebar">

    <div class="logo-section">

        <img
            src="{{ asset('assets/img/logo-sikolapay.svg') }}"
            alt="Logo SikolaPay"
        >

        <div>

            <h2 class="logo-title">
                <span class="logo-red">Si</span><span class="logo-orange">kola</span><span class="logo-yellow">Pay</span>
            </h2>

            <p class="logo-subtitle">
                Orang Tua / Wali
            </p>

        </div>

    </div>


    @php

        $isDashboardActive =
            request()->routeIs('guardian.dashboard');


        $isBillsActive =
            request()->routeIs('guardian.bills.index')
            || request()->routeIs('guardian.bills.show')
            || request()->routeIs('guardian.payments.create')
            || request()->routeIs('guardian.payments.store')
            || request()->routeIs('guardian.payments.proof');


        $isHistoryActive =
            request()->routeIs('guardian.payment-history');


        $isProfileActive =
            request()->routeIs('guardian.profile')
            || request()->routeIs('guardian.profile.edit');

    @endphp


    <nav class="sidebar-menu">

        {{-- Dashboard --}}
        <a
            href="{{ route('guardian.dashboard') }}"
            class="menu-item {{ $isDashboardActive ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Home.svg') }}"
                alt=""
            >

            <span>
                Dashboard
            </span>

        </a>


        {{-- Tagihan Anak --}}
        <a
            href="{{ route('guardian.bills.index') }}"
            class="menu-item {{ $isBillsActive ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Date_range_light.svg') }}"
                alt=""
            >

            <span>
                Tagihan Anak
            </span>

        </a>


        {{-- Riwayat Pembayaran --}}
        <a
            href="{{ route('guardian.payment-history') }}"
            class="menu-item {{ $isHistoryActive ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Time_light.svg') }}"
                alt=""
            >

            <span>
                Riwayat Pembayaran
            </span>

        </a>


        {{-- Profil --}}
        <a
            href="{{ route('guardian.profile') }}"
            class="menu-item {{ $isProfileActive ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/User_alt_light.svg') }}"
                alt=""
            >

            <span>
                Profil
            </span>

        </a>

    </nav>


    <div class="sidebar-profile">

        <div class="profile-info">

            <div class="avatar">
                {{ strtoupper(
                    substr(
                        auth()->user()?->name ?? 'GU',
                        0,
                        2
                    )
                ) }}
            </div>


            <div>

                <div class="profile-name">
                    {{ auth()->user()?->name ?? 'Orang Tua' }}
                </div>

                <div class="profile-id">
                    Orang Tua / Wali
                </div>

            </div>

        </div>


        <form
            method="POST"
            action="{{ route('logout') }}"
        >

            @csrf


            <button
                type="submit"
                class="sidebar-logout"
            >

                <img
                    src="{{ asset('assets/img/Sign_out_squre.svg') }}"
                    alt="Logout"
                >

            </button>

        </form>

    </div>

</aside>