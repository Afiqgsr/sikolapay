@extends('layouts.sikolapayapp')

@section('title', 'Dashboard Super Admin - SikolaPay')

@section('page-title', 'Dashboard Super Admin')

@section('content')

<section class="superadmin-dashboard">

    {{-- Header --}}
    <div class="superadmin-page-header">

        <h2>
            Dashboard Super Admin
        </h2>

        <p>
            Ringkasan keseluruhan sistem SikolaPay
        </p>

    </div>


    {{-- Statistik --}}
    <div class="superadmin-stat-grid">

        {{-- Total Admin --}}
        <div class="superadmin-stat-card">

            <div class="superadmin-stat-icon">

                <img
                    src="{{ asset('assets/img/Lock_alt-superadmin.svg') }}"
                    alt=""
                >

            </div>

            <div class="superadmin-stat-content">

                <span>
                    Total Admin
                </span>

                <strong>
                    {{ $totalAdmins }}
                </strong>

            </div>

        </div>


        {{-- Total Siswa --}}
        <div class="superadmin-stat-card">

            <div class="superadmin-stat-icon">

                <img
                    src="{{ asset('assets/img/Group_light-superadmin.svg') }}"
                    alt=""
                >

            </div>

            <div class="superadmin-stat-content">

                <span>
                    Total Siswa
                </span>

                <strong>
                    {{ $totalStudents }}
                </strong>

            </div>

        </div>


        {{-- Total Tagihan --}}
        <div class="superadmin-stat-card">

            <div class="superadmin-stat-icon">

                <img
                    src="{{ asset('assets/img/Clock_light-superadmin.svg') }}"
                    alt=""
                >

            </div>

            <div class="superadmin-stat-content">

                <span>
                    Total Tagihan
                </span>

                <strong>
                    {{ $totalBills }}
                </strong>

            </div>

        </div>


        {{-- Total Pembayaran --}}
        <div class="superadmin-stat-card">

            <div class="superadmin-stat-icon">

                <img
                    src="{{ asset('assets/img/money-superadmin.svg') }}"
                    alt=""
                >

            </div>

            <div class="superadmin-stat-content">

                <span>
                    Total Pembayaran
                </span>

                <strong>
                    {{ $totalPayments }}
                </strong>

            </div>

        </div>

    </div>


    {{-- Daftar Admin --}}
    <section class="superadmin-card admin-list-card">

        {{-- Header Card --}}
        <div class="superadmin-card-header">

            <div>

                <h3>
                    Daftar Admin
                </h3>

                <p>
                    Kelola akun administrator sistem SikolaPay
                </p>

            </div>

            <a
                href="{{ route('superadmin.admins.index') }}"
                class="superadmin-button-primary"
            >
                Kelola Admin
            </a>

        </div>


        {{-- Tabel Admin --}}
        <div class="superadmin-table-wrapper">

            <table class="superadmin-admin-table">

                <thead>

                    <tr>

                        <th>
                            Nama Admin
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Dibuat
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($admins as $admin)

                        <tr>

                            {{-- Nama --}}
                            <td>

                                <strong>
                                    {{ $admin->name }}
                                </strong>

                            </td>


                            {{-- Email --}}
                            <td>
                                {{ $admin->email }}
                            </td>


                            {{-- Role --}}
                            <td>

                                <span class="admin-role">
                                    Admin
                                </span>

                            </td>


                            {{-- Status --}}
                            <td>

                                @if(isset($admin->status) && $admin->status === 'inactive')

                                    <span class="admin-status inactive">
                                        Non-Aktif
                                    </span>

                                @else

                                    <span class="admin-status active">
                                        Aktif
                                    </span>

                                @endif

                            </td>


                            {{-- Dibuat --}}
                            <td>

                                {{ $admin->created_at
                                    ? $admin->created_at->translatedFormat('d F Y')
                                    : '-'
                                }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="superadmin-empty"
                            >
                                Belum ada akun admin.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>

</section>

@endsection