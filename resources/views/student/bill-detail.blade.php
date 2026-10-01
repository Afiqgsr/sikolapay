@extends('layouts.sikolapayapp')

@section('title', 'Detail Tagihan - SikolaPay')

@section('page-title', 'Tagihan Saya')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/student/bill-detail.css') }}">
@endpush

@section('content')

@php
    $latestPayment = $bill->latestPayment;
    $latestVerification = $latestPayment?->latestVerification;

    $isPaid = $bill->status === 'paid' || $latestPayment?->status === 'paid';
    $isRejected = ! $isPaid && ($latestVerification?->status === 'rejected' || $latestPayment?->status === 'rejected');
    $isPending = ! $isPaid && ! $isRejected && $latestPayment?->status === 'pending';
    $isFutureUnpaid = ! $isPaid && ! $isRejected && ! $isPending && $bill->status === 'unpaid' && ! $bill->hasBillingPeriodStarted();
@endphp

<section class="bill-detail-page">

    <a
        href="{{ route('student.bills.index') }}"
        class="bill-back"
    >
        <span>←</span>
        <span>Kembali ke Daftar Tagihan</span>
    </a>

    <div class="bill-title-row">

        <div class="bill-title">

            <h2>Detail Tagihan</h2>

            <p>
                {{ $bill->name }}
            </p>

        </div>

        @if($isPaid)

            <span class="bill-status paid">
                Tagihan Sudah Lunas
            </span>

        @elseif($isRejected)

            <span class="bill-status rejected">
                Bukti Pembayaran Ditolak
            </span>

        @elseif($isPending)

            <span class="bill-status pending">
                Menunggu Verifikasi
            </span>

        @elseif($isFutureUnpaid)

            <div style="text-align: right;">
                <span class="bill-status unpaid" style="background: #E5E7EB; color: #4B5563;">
                    Belum dapat dibayar
                </span>
                <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">
                    Tersedia mulai {{ $bill->billing_period ? \Carbon\Carbon::parse($bill->billing_period)->translatedFormat('j F Y') : '-' }}
                </div>
            </div>

        @else

            <a
                href="{{ route('student.payment', $bill->id) }}"
                class="pay-button"
            >
                Bayar Sekarang
            </a>

        @endif

    </div>

    {{-- Informasi penolakan --}}
    @if($isRejected)

        <div class="bill-rejection-alert">

            <div class="bill-rejection-header">

                <strong>
                    Bukti pembayaran ditolak
                </strong>

            </div>

            <p>
                Bukti pembayaran yang Anda kirim belum dapat diterima oleh admin.
            </p>

            @if($latestVerification?->note)

                <div class="bill-rejection-note">

                    <span>
                        Catatan Admin
                    </span>

                    <strong>
                        {{ $latestVerification->note }}
                    </strong>

                </div>

            @endif

            <p class="bill-rejection-help">
                Jika pembayaran sudah dilakukan, Anda tidak perlu membayar ulang.
                Silakan upload ulang bukti pembayaran sesuai catatan admin.
            </p>

            <a
                href="{{ route('student.payment', $bill->id) }}"
                class="bill-reupload-button"
            >
                Upload Ulang Bukti
            </a>

        </div>

    @endif

    <div class="bill-content-grid">

        <div class="bill-left-column">

            <div class="bill-card bill-information-card">

                <h3>Informasi Tagihan</h3>

                <div class="bill-info-grid">

                    <div class="bill-info-item">
                        <span>No. Tagihan</span>

                        <strong>
                            #TAG-{{ str_pad($bill->id, 4, '0', STR_PAD_LEFT) }}
                        </strong>
                    </div>

                    <div class="bill-info-item">
                        <span>Jenis Tagihan</span>

                        <strong>
                            {{ $bill->name }}
                        </strong>
                    </div>

                    <div class="bill-info-item">
                        <span>Periode Tagihan</span>

                        <strong>
                            {{ $bill->billing_period
                                ? \Carbon\Carbon::parse($bill->billing_period)->translatedFormat('F Y')
                                : '-'
                            }}
                        </strong>
                    </div>

                    @if($bill->description)
                        <div class="bill-info-item">
                            <span>Keterangan</span>

                            <strong>
                                {{ $bill->description }}
                            </strong>
                        </div>
                    @endif

                    <div class="bill-info-item">
                        <span>Jatuh Tempo</span>

                        <strong>
                            {{ $bill->due_date
                                ? \Carbon\Carbon::parse($bill->due_date)->translatedFormat('d F Y')
                                : '-'
                            }}
                        </strong>
                    </div>

                    <div class="bill-info-item">
                        <span>Kelas</span>

                        <strong>
                            {{ $student->classRoom?->name ?? '-' }}
                        </strong>
                    </div>

                    <div class="bill-info-item">
                        <span>Status</span>

                        <strong>

                            @if($isPaid)

                                <span class="status-text paid">
                                    Lunas
                                </span>

                            @elseif($isRejected)

                                <span class="status-text rejected">
                                    Ditolak
                                </span>

                            @elseif($isPending)

                                <span class="status-text pending">
                                    Menunggu
                                </span>

                            @elseif($isFutureUnpaid)

                                <span class="status-text unpaid" style="color: #4B5563;">
                                    Belum dapat dibayar
                                </span>

                            @else

                                <span class="status-text unpaid">
                                    Belum Bayar
                                </span>

                            @endif

                        </strong>
                    </div>

                </div>

                <div class="bill-divider"></div>

                <div class="bill-price-list">

                    <div class="bill-price-row">

                        <span>
                            {{ $bill->name }}
                        </span>

                        <strong>
                            Rp {{ number_format($bill->amount, 0, ',', '.') }}
                        </strong>

                    </div>

                    <div class="bill-price-row">

                        <span>
                            Biaya Admin
                        </span>

                        <strong>
                            Rp 0
                        </strong>

                    </div>

                </div>

                <div class="bill-divider"></div>

                <div class="bill-total-row">

                    <span>
                        Total Tagihan
                    </span>

                    <strong>
                        Rp {{ number_format($bill->amount, 0, ',', '.') }}
                    </strong>

                </div>

            </div>

            <div class="bill-card student-card">

                <h3>Informasi Siswa</h3>

                <div class="student-info-grid">

                    <div class="bill-info-item">

                        <span>Nama Siswa</span>

                        <strong>
                            {{ $student->name }}
                        </strong>

                    </div>

                    <div class="bill-info-item">

                        <span>NISN</span>

                        <strong>
                            {{ $student->nisn ?? '-' }}
                        </strong>

                    </div>

                    <div class="bill-info-item">

                        <span>NIS</span>

                        <strong>
                            {{ $student->nis ?? '-' }}
                        </strong>

                    </div>

                    <div class="bill-info-item">

                        <span>Kelas</span>

                        <strong>
                            {{ $student->classRoom?->name ?? '-' }}
                        </strong>

                    </div>

                    <div class="bill-info-item">

                        <span>Nama Wali</span>

                        <strong>
                            {{ $student->guardian?->name ?? '-' }}
                        </strong>

                    </div>

                    <div class="bill-info-item">

                        <span>No. HP Wali</span>

                        <strong>
                            {{ $student->guardian?->phone ?? '-' }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>

        <div class="bill-right-column">

            <div class="bill-card summary-card">

                <h3>Ringkasan</h3>

                <div class="summary-amount">

                    <strong>
                        Rp {{ number_format($bill->amount, 0, ',', '.') }}
                    </strong>

                    <span>
                        Total yang harus dibayar
                    </span>

                </div>

                <div class="summary-due" style="display: flex; flex-direction: column; gap: 4px;">

                    <span>
                        Periode Tagihan:
                        {{ $bill->billing_period
                            ? \Carbon\Carbon::parse($bill->billing_period)->translatedFormat('F Y')
                            : '-'
                        }}
                    </span>

                    <span>
                        Jatuh tempo:
                        {{ $bill->due_date
                            ? \Carbon\Carbon::parse($bill->due_date)->translatedFormat('d F Y')
                            : '-'
                        }}
                    </span>

                </div>

                @if($isPaid)

                    <span class="paid-button">
                        Tagihan Sudah Lunas
                    </span>

                @elseif($isRejected)

                    <a
                        href="{{ route('student.payment', $bill->id) }}"
                        class="pay-button"
                    >
                        Upload Ulang Bukti
                    </a>

                @elseif($isPending)

                    <span class="pending-button">
                        Menunggu Verifikasi
                    </span>

                @elseif($isFutureUnpaid)

                    <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                        <span class="paid-button" style="background: #E5E7EB; color: #6B7280; cursor: not-allowed;">
                            Belum dapat dibayar
                        </span>
                        <div style="font-size: 12px; color: #6B7280; text-align: center;">
                            Tersedia mulai {{ $bill->billing_period ? \Carbon\Carbon::parse($bill->billing_period)->translatedFormat('j F Y') : '-' }}
                        </div>
                    </div>

                @else

                    <a
                        href="{{ route('student.payment', $bill->id) }}"
                        class="pay-button"
                    >
                        Bayar Sekarang
                    </a>

                @endif

            </div>

            <div class="bill-card payment-method-card">

                <h3>Metode Pembayaran</h3>

                <div class="payment-method-list">

                    <div class="payment-method">
                        <span class="payment-check">✓</span>
                        <span>Transfer Bank</span>
                    </div>

                    <div class="payment-method">
                        <span class="payment-check">✓</span>
                        <span>Virtual Account</span>
                    </div>

                    <div class="payment-method">
                        <span class="payment-check">✓</span>
                        <span>QRIS</span>
                    </div>

                    <div class="payment-method">
                        <span class="payment-check">✓</span>
                        <span>E-Wallet</span>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection