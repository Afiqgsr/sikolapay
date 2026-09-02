<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'SikolaPay')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @stack('styles')
</head>

<body>

<div class="dashboard-layout">

    @if(auth()->user()?->role === 'admin')

        @include('components.sidebar-admin')

    @elseif(auth()->user()?->role === 'student')

        @include('components.sidebar-student')

    @elseif(auth()->user()?->role === 'guardian')

        @include('components.sidebar-guardian')

    @elseif(auth()->user()?->role === 'super_admin')

        @include('components.sidebar-superadmin')

    @endif


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <main class="main-content">

        @include('components.topbar')


        @yield('content')

    </main>

</div>


@stack('scripts')

</body>

</html>