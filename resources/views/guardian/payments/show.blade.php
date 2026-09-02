@extends('layouts.sikolapayapp')

@section('title', 'Detail Pembayaran - SikolaPay')

@section('page-title', 'Pembayaran')

@push('styles')
    @vite('resources/css/pages/guardian/payment-detail.css')
@endpush

@section('content')

@php

    $bill = $payment->bill;
    $student = $bill?->student;

    $statusLabel = match ($payment->status) {
        'paid' => 'Lunas',
        'pending' => 'Menunggu Verifikasi',
        'rejected' => 'Ditolak',
        default => ucfirst($payment->status),
    };

@endphp


<section class="guardian-payment-detail-page">

    <div class="payment-detail-back">

        <a
            href="{{ route('guardian.bills.index') }}"
            class="payment-detail-back-button"
        >

            <img
                src="{{ asset('assets/img/back.svg') }}"
                alt=""
            >

            <span>
                Kembali ke Daftar Tagihan
            </span>

        </a>

    </div>


    <div class="payment-detail-header">

        <div>

            <h2>
                Detail Pembayaran
            </h2>

            <p>
                Informasi pembayaran dan status verifikasi
            </p>

        </div>


        <span class="payment-detail-status {{ $payment->status }}">
            {{ $statusLabel }}
        </span>

    </div>


    @if(session('success'))

        <div class="payment-detail-alert success">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="payment-detail-alert error">
            {{ session('error') }}
        </div>

    @endif


    <div class="payment-detail-grid">


        <div class="payment-detail-left">


            <div class="payment-detail-card">

                <div class="payment-detail-card-header">

                    <h3>
                        Informasi Pembayaran
                    </h3>

                </div>


                <div class="payment-detail-content">


                    <div class="payment-detail-row">

                        <span>
                            Nomor Pembayaran
                        </span>

                        <strong class="payment-number">
                            {{ $payment->payment_number }}
                        </strong>

                    </div>


                    <div class="payment-detail-row">

                        <span>
                            Jenis Tagihan
                        </span>

                        <strong>
                            {{ $bill?->name ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-detail-row">

                        <span>
                            Periode
                        </span>

                        <strong>
                            {{ $bill?->description ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-detail-row">

                        <span>
                            Nama Anak
                        </span>

                        <strong>
                            {{ $student?->name ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-detail-row">

                        <span>
                            Kelas
                        </span>

                        <strong>
                            {{ $student?->classRoom?->name ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-detail-row">

                        <span>
                            Metode Pembayaran
                        </span>

                        <strong>
                            {{ $payment->paymentMethod?->name ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-detail-row">

                        <span>
                            Tanggal Pembayaran
                        </span>

                        <strong>
                            {{ $payment->created_at
                                ? $payment->created_at->translatedFormat('d F Y, H:i')
                                : '-'
                            }}
                        </strong>

                    </div>

                </div>

            </div>


            <div class="payment-detail-card">

                <div class="payment-detail-card-header">

                    <h3>
                        Bukti Pembayaran
                    </h3>

                </div>


                <div class="payment-proof-content">

                    @if($payment->proof_of_payment)

                        <div class="payment-proof-preview">

                            <img
                                src="{{ asset(
                                    'storage/' . $payment->proof_of_payment
                                ) }}"
                                alt="Bukti Pembayaran"
                            >

                        </div>


                        <div class="payment-proof-actions">

                            <a
                                href="{{ asset(
                                    'storage/' . $payment->proof_of_payment
                                ) }}"
                                target="_blank"
                                class="payment-proof-button"
                            >
                                Lihat Bukti Pembayaran
                            </a>

                        </div>

                    @else

                        <div class="payment-proof-empty">

                            <p>
                                Bukti pembayaran belum tersedia.
                            </p>

                        </div>

                    @endif

                </div>

            </div>


            @if($payment->status === 'rejected')

                <div class="payment-detail-card rejection-card">

                    <div class="payment-detail-card-header">

                        <h3>
                            Pembayaran Ditolak
                        </h3>

                    </div>


                    <div class="rejection-content">

                        <p>
                            {{ $payment->latestVerification?->note
                                ?? 'Pembayaran ditolak oleh admin.'
                            }}
                        </p>


                        <a
                            href="{{ route(
                                'guardian.payments.create',
                                $bill->id
                            ) }}"
                            class="payment-retry-button"
                        >
                            Bayar Lagi
                        </a>

                    </div>

                </div>

            @endif

        </div>


        <aside class="payment-detail-right">

            <div class="payment-summary-card">

                <div class="payment-summary-header">

                    <h3>
                        Ringkasan
                    </h3>

                </div>


                <div class="payment-summary-content">

                    <div class="payment-summary-row">

                        <span>
                            Tagihan
                        </span>

                        <strong>
                            {{ $bill?->name ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-summary-row">

                        <span>
                            Metode
                        </span>

                        <strong>
                            {{ $payment->paymentMethod?->name ?? '-' }}
                        </strong>

                    </div>


                    <div class="payment-summary-row">

                        <span>
                            Status
                        </span>

                        <strong class="summary-status {{ $payment->status }}">
                            {{ $statusLabel }}
                        </strong>

                    </div>


                    <div class="payment-summary-divider"></div>


                    <div class="payment-summary-total">

                        <span>
                            Total
                        </span>

                        <strong>
                            Rp {{ number_format(
                                $payment->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                    </div>


                    @if($payment->status === 'pending')

                        <div class="payment-waiting-info">

                            <strong>
                                Menunggu Verifikasi
                            </strong>

                            <p>
                                Bukti pembayaran telah diterima.
                                Admin akan melakukan verifikasi
                                dalam 1×24 jam kerja.
                            </p>

                        </div>

                    @elseif($payment->status === 'paid')

                        <div class="payment-paid-info">

                            <strong>
                                Pembayaran Berhasil
                            </strong>

                            <p>
                                Pembayaran telah diverifikasi dan
                                tagihan dinyatakan lunas.
                            </p>

                        </div>

                    @elseif($payment->status === 'rejected')

                        <div class="payment-rejected-info">

                            <strong>
                                Pembayaran Ditolak
                            </strong>

                            <p>
                                Silakan periksa alasan penolakan
                                dan lakukan pembayaran kembali.
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </aside>

    </div>

</section>

@endsection