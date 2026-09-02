<aside class="sidebar">

    {{-- Logo --}}
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
                Super Admin
            </p>

        </div>

    </div>


    {{-- Menu --}}
    <nav class="sidebar-menu">

        <a
            href="{{ route('superadmin.dashboard') }}"
            class="menu-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
        >

            <img
                src="{{ asset('assets/img/Home.svg') }}"
                alt=""
            >

            <span>
                Dashboard
            </span>

        </a>

        <a
            href="{{ route('superadmin.admins.index') }}"
            class="menu-item {{ request()->routeIs('superadmin.admins.*') ? 'active' : '' }}"
        >
            <img
                src="{{ asset('assets/img/User_alt_light.svg') }}"
                alt=""
            >

            <span>
                Kelola Admin
            </span>
        </a>

    </nav>


    {{-- Profile --}}
    <div class="sidebar-profile">

        <div class="profile-info">

            <div class="avatar">

                {{ strtoupper(
                    substr(
                        auth()->user()?->name ?? 'SA',
                        0,
                        2
                    )
                ) }}

            </div>

            <div>

                <div class="profile-name">
                    {{ auth()->user()?->name ?? 'Super Admin' }}
                </div>

                <div class="profile-id">
                    Super Admin
                </div>

            </div>

        </div>


        {{-- Logout --}}
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