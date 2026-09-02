@extends('layouts.sikolapayapp')

@section('title', 'Dashboard Siswa - SikolaPay')

@section('page-title', 'Dashboard')

@section('content')

<div class="dashboard-content">

    {{-- Welcome --}}
    <section class="welcome-section">

        <h2>
            Selamat Datang, {{ $student->name }}
        </h2>

        <p>
            {{ $student->classRoom?->name ?? '-' }}
            · NISN : {{ $student->nisn ?? '-' }}
            · TA {{ $academicYearName }}
        </p>

    </section>

    {{-- Statistic --}}
    <section class="stats-section">

        <div class="stat-card">

            <img
                src="{{ asset('assets/img/Date_range_light2.svg') }}"
                alt=""
            >

            <div>

                <span>Total Tagihan</span>

                <h3>
                    {{ $totalBills }} Tagihan
                </h3>

                <small>
                    {{ $academicYearName }}
                </small>

            </div>

        </div>

        <div class="stat-card warning">

            <img
                src="{{ asset('assets/img/Hhourglass_move_light.svg') }}"
                alt=""
            >

            <div>

                <span>Belum Bayar</span>

                <h3>
                    {{ $unpaidBills }} Tagihan
                </h3>

                <small>
                    Segera Lunasi
                </small>

            </div>

        </div>

        <div class="stat-card success">

            <img
                src="{{ asset('assets/img/Check_fill.svg') }}"
                alt=""
            >

            <div>

                <span>Sudah Lunas</span>

                <h3>
                    {{ $paidBills }} Tagihan
                </h3>

                <small>
                    Semester Ini
                </small>

            </div>

        </div>

        <div class="stat-card">

            <img
                src="{{ asset('assets/img/Money.svg') }}"
                alt=""
            >

            <div>

                <span>Total Nominal</span>

                <h3>
                    Rp {{ number_format(
                        $totalAmount,
                        0,
                        ',',
                        '.'
                    ) }}
                </h3>

                <small>
                    {{ $academicYearName }}
                </small>

            </div>

        </div>

    </section>

    {{-- Alert jatuh tempo --}}
    @if($nearestBill)

        <section class="alert-card">

            <img
                src="{{ asset('assets/img/Hhourglass_move_light1.svg') }}"
                alt=""
            >

            <div class="alert-content">

                <div>

                    <h3>
                        Tagihan Akan Segera Jatuh Tempo
                    </h3>

                    <p>
                        {{ $nearestBill->name }}
                        akan jatuh tempo pada

                        {{ \Carbon\Carbon::parse(
                            $nearestBill->due_date
                        )->translatedFormat('d F Y') }}.

                        Segera lakukan pembayaran.
                    </p>

                </div>

                <a
                    href="{{ route('student.bills.index') }}"
                    class="alert-button"
                >
                    Bayar Sekarang
                </a>

            </div>

        </section>

    @endif

    {{-- Grid --}}
    <div class="dashboard-grid">

        {{-- Tagihan aktif --}}
        <section class="invoice-section">

            <div class="section-header">

                <h3>
                    Tagihan Aktif
                </h3>

                <a href="{{ route('student.bills.index') }}">
                    Lihat Semua
                </a>

            </div>

            <div class="invoice-list">

                @forelse($activeBills as $bill)

                    @php
                        $latestPayment = $bill->latestPayment;

                        $latestVerification =
                            $latestPayment?->latestVerification;

                        $hasRejectedVerification =
                            $latestVerification?->status === 'rejected';

                        $isResubmitted =
                            $hasRejectedVerification
                            && $latestPayment?->proof_uploaded_at
                            && $latestVerification?->processed_at
                            && $latestPayment->proof_uploaded_at->gt(
                                $latestVerification->processed_at
                            );

                        $isRejected =
                            $hasRejectedVerification
                            && !$isResubmitted;
                    @endphp

                    <div class="invoice-item">

                        {{-- Info --}}
                        <div class="invoice-info">

                            <div class="invoice-title">
                                {{ $bill->name }}
                            </div>

                            <div class="invoice-date">

                                @if($bill->due_date)

                                    Jatuh tempo:

                                    {{ \Carbon\Carbon::parse(
                                        $bill->due_date
                                    )->translatedFormat('d F Y') }}

                                @else

                                    Jatuh tempo: -

                                @endif

                            </div>

                        </div>

                        {{-- Harga --}}
                        <div class="invoice-price">

                            Rp {{ number_format(
                                $bill->amount,
                                0,
                                ',',
                                '.'
                            ) }}

                        </div>

                        {{-- Action --}}
                        <div class="invoice-action">

                            @if($bill->status === 'paid')

                                <span class="badge success">
                                    Lunas
                                </span>

                            @elseif($isRejected)

                                <span class="badge rejected">
                                    Ditolak
                                </span>

                                <a
                                    href="{{ route(
                                        'student.payment',
                                        $bill->id
                                    ) }}"
                                    class="invoice-pay-button"
                                >
                                    Upload Ulang
                                </a>

                            @elseif(
                                $latestPayment?->status === 'pending'
                            )

                                <span class="badge pending">

                                    @if($isResubmitted)

                                        Menunggu Verifikasi Ulang

                                    @else

                                        Menunggu

                                    @endif

                                </span>

                            @else

                                <span class="badge warning">
                                    Belum Bayar
                                </span>

                                <a
                                    href="{{ route(
                                        'student.payment',
                                        $bill->id
                                    ) }}"
                                    class="invoice-pay-button"
                                >
                                    Bayar
                                </a>

                            @endif

                        </div>

                    </div>

                @empty

                    <div class="empty-state">

                        <p>
                            Tidak ada tagihan aktif.
                        </p>

                    </div>

                @endforelse

            </div>

        </section>

        {{-- Sidebar kanan --}}
        <aside class="right-sidebar">

            {{-- Informasi siswa --}}
            <section class="student-info">

                <h3>
                    Informasi Siswa
                </h3>

                <div class="student-profile">

                    <div class="avatar">

                        {{ \Illuminate\Support\Str::initials(
                            $student->name
                        ) }}

                    </div>

                    <div>

                        <h4>
                            {{ $student->name }}
                        </h4>

                        <p>
                            {{ $student->classRoom?->name ?? '-' }}
                        </p>

                    </div>

                </div>

                <div class="student-detail">

                    <div class="detail-item">

                        <span>
                            NISN
                        </span>

                        <strong>
                            {{ $student->nisn ?? '-' }}
                        </strong>

                    </div>

                    <div class="detail-item">

                        <span>
                            NIS
                        </span>

                        <strong>
                            {{ $student->nis ?? '-' }}
                        </strong>

                    </div>

                    <div class="detail-item">

                        <span>
                            Wali
                        </span>

                        <strong>
                            {{ $student->guardian?->name ?? '-' }}
                        </strong>

                    </div>

                    <div class="detail-item">

                        <span>
                            Tahun Ajaran
                        </span>

                        <strong>
                            {{ $academicYearName }}
                        </strong>

                    </div>

                </div>

            </section>

            {{-- Quick action --}}
            <section class="quick-action">

                <h3>
                    Aksi Cepat
                </h3>

                <a href="{{ route('student.bills.index') }}">

                    <img
                        src="{{ asset('assets/img/Icon-set-pr.svg') }}"
                        alt=""
                    >

                    <span>
                        Lihat Semua Tagihan
                    </span>

                </a>

                <a href="{{ route('student.payment-history') }}">

                    <img
                        src="{{ asset('assets/img/File_dock.svg') }}"
                        alt=""
                    >

                    <span>
                        Riwayat Pembayaran
                    </span>

                </a>

                <a href="{{ route('student.profile') }}">

                    <img
                        src="{{ asset('assets/img/Icon-set.svg') }}"
                        alt=""
                    >

                    <span>
                        Profil Saya
                    </span>

                </a>

            </section>

        </aside>

    </div>

</div>

@endsection