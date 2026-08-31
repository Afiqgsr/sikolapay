<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'SikolaPay')</title>

    <!-- CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    @if(auth()->user()?->role === 'admin')

        @include('components.sidebar-admin')

    @elseif(auth()->user()?->role === 'student')

        @include('components.sidebar-student')

    @endif

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        @include('components.topbar')

        <!-- CONTENT -->
        @yield('content')

    </main>

</div>

<!-- JAVASCRIPT -->
@stack('scripts')

</body>
</html>