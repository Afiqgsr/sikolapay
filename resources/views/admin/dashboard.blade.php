@extends('layouts.sikolapayapp')

@section('title', 'Dashboard Admin - SikolaPay')

@section('page-title', 'Dashboard Admin')

@section('content')

<section class="admin-dashboard">

    {{-- Header --}}
    <div class="admin-page-header">

        <h2>Dashboard Admin</h2>

        <p>
            Ringkasan data pembayaran sekolah
        </p>

    </div>

    {{-- Statistik --}}
    <div class="admin-stat-grid">

        <div class="admin-stat-card">

            <div class="admin-stat-icon">

                <img
                    src="{{ asset('assets/img/Group_light-admin.svg') }}"
                    alt=""
                >

            </div>

            <div class="admin-stat-content">

                <span>
                    Total Siswa
                </span>

                <strong>
                    {{ $totalStudents }}
                </strong>

                <small>
                    Siswa aktif terdaftar
                </small>

            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">

                <img
                    src="{{ asset('assets/img/File_dock-yellow-admin.svg') }}"
                    alt=""
                >

            </div>

            <div class="admin-stat-content">

                <span>
                    Total Tagihan Aktif
                </span>

                <strong>
                    {{ $totalActiveBills }}
                </strong>

                <small>
                    Tagihan berjalan
                </small>

            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">

                <img
                    src="{{ asset('assets/img/Check_fill-admin.svg') }}"
                    alt=""
                >

            </div>

            <div class="admin-stat-content">

                <span>
                    Pembayaran Berhasil
                </span>

                <strong>
                    Rp {{ number_format(
                        $successfulPaymentsAmount,
                        0,
                        ',',
                        '.'
                    ) }}
                </strong>

                <small>
                    Bulan {{ now()->translatedFormat('F Y') }}
                </small>

            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">

                <img
                    src="{{ asset('assets/img/caution-admin.svg') }}"
                    alt=""
                >

            </div>

            <div class="admin-stat-content">

                <span>
                    Tagihan Belum Lunas
                </span>

                <strong>
                    {{ $unpaidBills }}
                </strong>

                <small>
                    Perlu perhatian
                </small>

            </div>

        </div>

    </div>


    {{-- Notifikasi Verifikasi --}}
    @if($pendingPayments > 0)

        <div class="admin-notification">

            <div class="admin-notification-content">

                <img
                    src="{{ asset('assets/img/caution-orange-admin.svg') }}"
                    alt=""
                >

                <p>
                    Terdapat

                    <strong>
                        {{ $pendingPayments }} Pembayaran
                    </strong>

                    yang menunggu verifikasi Anda.
                </p>

            </div>

            <a
                href="{{ route('admin.payments.index') }}"
                class="admin-button-primary"
            >
                Verifikasi Sekarang
            </a>

        </div>

    @endif


    {{-- Pembayaran Terbaru --}}
    <section class="admin-card payment-table-card">

        <div class="admin-card-header">

            <div>

                <h3>
                    Pembayaran Terbaru
                </h3>

                <p>
                    Aktivitas pembayaran terbaru
                </p>

            </div>

            <a
                href="{{ route('admin.payments.index') }}"
                class="admin-view-all"
            >
                Lihat Semua
            </a>

        </div>


        <div class="admin-table-wrapper">

            <table class="admin-payment-table">

                <thead>

                    <tr>
                        <th>Siswa</th>
                        <th>Jenis Tagihan</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($latestPayments as $payment)

                        @php
                            $latestVerification =
                                $payment->latestVerification;

                            $isRejected =
                                $latestVerification?->status === 'rejected';

                            $isResubmitted =
                                $isRejected
                                && $payment->proof_uploaded_at
                                && $latestVerification?->processed_at
                                && $payment->proof_uploaded_at->gt(
                                    $latestVerification->processed_at
                                );
                        @endphp

                        <tr>

                            {{-- Siswa --}}
                            <td>

                                <div class="student-name">
                                    {{ $payment->bill?->student?->name ?? '-' }}
                                </div>

                                <span class="student-class">
                                    {{ $payment->bill?->student?->classRoom?->name ?? '-' }}
                                </span>

                            </td>

                            {{-- Tagihan --}}
                            <td>
                                {{ $payment->bill?->name ?? '-' }}
                            </td>

                            {{-- Nominal --}}
                            <td class="payment-amount">

                                Rp {{ number_format(
                                    $payment->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                            {{-- Metode --}}
                            <td>
                                {{ $payment->paymentMethod?->name ?? '-' }}
                            </td>

                            {{-- Waktu --}}
                            <td>

                                {{ $payment->created_at
                                    ->translatedFormat('d F Y, H:i') }}

                            </td>

                            {{-- Status --}}
                            <td>

                                @if($payment->status === 'paid')

                                    <span class="status success">
                                        Lunas
                                    </span>

                                @elseif($isRejected && !$isResubmitted)

                                    <span class="status rejected">
                                        Ditolak
                                    </span>

                                @elseif($payment->status === 'pending')

                                    <span class="status pending">
                                        Menunggu Verifikasi
                                    </span>

                                @else

                                    <span class="status">
                                        {{ ucfirst($payment->status) }}
                                    </span>

                                @endif

                            </td>

                            {{-- Aksi --}}
                            <td>

                                <a
                                    href="{{ route(
                                        'admin.payments.show',
                                        $payment->id
                                    ) }}"
                                    class="table-action"
                                >

                                    @if($payment->status === 'paid')

                                        Detail

                                    @elseif($isRejected && $isResubmitted)

                                        Tinjau Ulang

                                    @elseif($isRejected)

                                        Detail

                                    @elseif($payment->status === 'pending')

                                        Tinjau

                                    @else

                                        Detail

                                    @endif

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="admin-empty"
                            >
                                Belum ada pembayaran.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    {{-- Bagian Bawah --}}
    <div class="admin-bottom-grid">

        {{-- Progress Pembayaran --}}
        <section class="admin-card payment-progress-card">

            <div class="admin-card-header">

                <div>

                    <h3>
                        Progress Pembayaran
                    </h3>

                    <p>
                        Progress pelunasan seluruh tagihan
                    </p>

                </div>

            </div>


            <div class="progress-summary">

                <div>

                    <strong>
                        {{ $processedBills }}
                    </strong>

                    <span>
                        dari {{ $totalBills }} tagihan
                    </span>

                </div>

                <strong>
                    {{ $progressPercentage }}%
                </strong>

            </div>


            <div class="progress-bar">

                <div
                    class="progress-value"
                    style="--progress-width: {{ $progressPercentage }}%;"
                ></div>

            </div>


            <div class="progress-status-list">

                {{-- Lunas --}}
                <div class="progress-status-item">

                    <span class="status success">
                        Lunas
                    </span>

                    <div>

                        <strong>
                            {{ $paidBills }}
                        </strong>

                        <span>
                            ({{ $paidPercentage }}%)
                        </span>

                    </div>

                </div>


                {{-- Menunggu --}}
                <div class="progress-status-item">

                    <span class="status pending">
                        Menunggu Verifikasi
                    </span>

                    <div>

                        <strong>
                            {{ $pendingBills }}
                        </strong>

                        <span>
                            ({{ $pendingPercentage }}%)
                        </span>

                    </div>

                </div>


                {{-- Belum Bayar --}}
                <div class="progress-status-item">

                    <span class="status unpaid">
                        Belum Bayar
                    </span>

                    <div>

                        <strong>
                            {{ $unpaidProgressBills }}
                        </strong>

                        <span>
                            ({{ $unpaidPercentage }}%)
                        </span>

                    </div>

                </div>

            </div>

        </section>


        {{-- Aksi Cepat --}}
        <section class="admin-card quick-action-card">

            <h3>
                Aksi Cepat
            </h3>

            <div class="quick-action-list">

                <a
                    href="{{ route('admin.bills.index') }}"
                    class="quick-action"
                >

                    <div>

                        <strong>
                            Tambah Tagihan Baru
                        </strong>

                        <span>
                            Per kelas / angkatan / sekolah
                        </span>

                    </div>

                    <img
                        src="{{ asset('assets/img/right-arrow.svg') }}"
                        alt=""
                    >

                </a>


                <a
                    href="{{ route('admin.payments.index') }}"
                    class="quick-action"
                >

                    <div>

                        <strong>
                            Verifikasi Pembayaran
                        </strong>

                        <span>
                            {{ $pendingPayments }} Menunggu
                        </span>

                    </div>

                    <img
                        src="{{ asset('assets/img/right-arrow.svg') }}"
                        alt=""
                    >

                </a>


                <a
                    href="{{ route('admin.students.index') }}"
                    class="quick-action"
                >

                    <div>

                        <strong>
                            Data Siswa
                        </strong>

                        <span>
                            {{ $totalStudents }} Siswa terdaftar
                        </span>

                    </div>

                    <img
                        src="{{ asset('assets/img/right-arrow.svg') }}"
                        alt=""
                    >

                </a>


                <a
                    href="#"
                    class="quick-action"
                >

                    <div>

                        <strong>
                            Laporan Pembayaran
                        </strong>

                        <span>
                            PDF &amp; Excel
                        </span>

                    </div>

                    <img
                        src="{{ asset('assets/img/right-arrow.svg') }}"
                        alt=""
                    >

                </a>

            </div>

        </section>

    </div>

</section>

@endsection