<header class="topbar">

    <!-- LEFT -->
    <div class="topbar-left">

        <!-- MENU TOGGLE -->
        <button class="menu-toggle" id="menuToggle" type="button">
            ☰
        </button>

        <!-- PAGE TITLE -->
        <h1 class="page-title">
            @yield('page-title', 'Dashboard')
        </h1>

    </div>


    <!-- RIGHT -->
    <div class="topbar-actions">

        <!-- NOTIFICATION -->
        <button class="notification-button" type="button">
            <img 
                src="{{ asset('assets/img/Bell_pin.svg') }}" 
                alt="Notifikasi"
            >
        </button>


        <!-- USER AVATAR -->
        <div class="avatar small">
            {{ auth()->user()->initials() }}
        </div>

    </div>

</header>