@extends('layouts.sikolapayapp')

@section('title', 'Dashboard Admin - SikolaPay')
@section('page-title', 'Dashboard Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/admin/dashboard.css') }}">
@endpush

@section('content')

<section class="admin-dashboard">

    <div class="admin-dashboard-header">

        <div>
            <h2>Dashboard Admin</h2>

            <p>
                Selamat datang, {{ auth()->user()->name }}
            </p>
        </div>

    </div>

    <div class="admin-stats">

        <div class="admin-stat-card">

            <span>Total Siswa</span>

            <h3>
                {{ $totalStudents }}
            </h3>

            <small>
                Siswa terdaftar
            </small>

        </div>

        <div class="admin-stat-card">

            <span>Total Tagihan</span>

            <h3>
                {{ $totalBills }}
            </h3>

            <small>
                Seluruh tagihan
            </small>

        </div>

        <div class="admin-stat-card warning">

            <span>Menunggu Verifikasi</span>

            <h3>
                {{ $pendingPayments }}
            </h3>

            <small>
                Pembayaran pending
            </small>

        </div>

        <div class="admin-stat-card success">

            <span>Pembayaran Lunas</span>

            <h3>
                {{ $paidPayments }}
            </h3>

            <small>
                Pembayaran terverifikasi
            </small>

        </div>

    </div>

    <div class="admin-dashboard-grid">

        <div class="admin-card">

            <div class="admin-card-header">

                <div>
                    <h3>Pembayaran Terbaru</h3>

                    <p>
                        Transaksi pembayaran terbaru dari siswa.
                    </p>
                </div>

                <a
                    href="{{ route('admin.payments.index') }}"
                    class="admin-card-link"
                >
                    Lihat Semua
                </a>

            </div>

            <div class="admin-payment-list">

                @forelse($latestPayments as $payment)

                    <div class="admin-payment-item">

                        <div class="admin-payment-info">

                            <strong>
                                {{ $payment->bill?->student?->name ?? '-' }}
                            </strong>

                            <span>
                                {{ $payment->bill?->name ?? '-' }}
                            </span>

                        </div>

                        <div class="admin-payment-amount">

                            Rp {{ number_format($payment->amount, 0, ',', '.') }}

                        </div>

                        <div class="admin-payment-status">

                            @if($payment->status === 'paid')

                                <span class="admin-badge success">
                                    Lunas
                                </span>

                            @elseif($payment->status === 'pending')

                                <span class="admin-badge pending">
                                    Menunggu
                                </span>

                            @elseif($payment->status === 'rejected')

                                <span class="admin-badge danger">
                                    Ditolak
                                </span>

                            @else

                                <span class="admin-badge">
                                    {{ ucfirst($payment->status) }}
                                </span>

                            @endif

                        </div>

                    </div>

                @empty

                    <div class="admin-empty">
                        Belum ada pembayaran.
                    </div>

                @endforelse

            </div>

        </div>

        <div class="admin-card">

            <div class="admin-card-header">

                <div>
                    <h3>Aksi Cepat</h3>

                    <p>
                        Menu yang sering digunakan admin.
                    </p>
                </div>

            </div>

            <div class="admin-quick-actions">

                <a href="{{ route('admin.payments.index') }}">
                    Verifikasi Pembayaran
                </a>

            </div>

        </div>

    </div>

</section>

@endsection