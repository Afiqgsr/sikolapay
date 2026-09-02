@extends('layouts.sikolapayapp')

@section('title', 'Nota Pembayaran - SikolaPay')

@section('page-title', 'Nota Pembayaran')

@section('content')

@php

    $bill = $payment->bill;

    $student = $bill?->student;

    $verification = $payment->latestVerification;

@endphp


<section class="guardian-receipt-page">

    <div class="guardian-receipt-back">

        <a
            href="{{ route('guardian.payment-history') }}"
            class="guardian-receipt-back-button"
        >
            <span>
                ‹
            </span>

            Kembali ke Riwayat Pembayaran
        </a>

    </div>


    <div class="guardian-receipt-card">

        <div class="guardian-receipt-header">

            <div>

                <span class="guardian-receipt-label">
                    Nota Pembayaran
                </span>

                <h2>
                    SikolaPay
                </h2>

                <p>
                    Bukti pembayaran sekolah
                </p>

            </div>


            <div class="guardian-receipt-status">
                Lunas
            </div>

        </div>


        <div class="guardian-receipt-divider"></div>


        <div class="guardian-receipt-meta">

            <div class="guardian-receipt-number">

                <span>
                    Nomor Pembayaran
                </span>

                <strong>
                    {{ $payment->payment_number }}
                </strong>

            </div>


            <div class="guardian-receipt-dates">

                <div>

                    <span>
                        Tanggal Bayar
                    </span>

                    <strong>
                        {{ $payment->paid_at
                            ? \Illuminate\Support\Carbon::parse(
                                $payment->paid_at
                            )->translatedFormat('d F Y')
                            : '-'
                        }}
                    </strong>

                </div>


                <div>

                    <span>
                        Diverifikasi
                    </span>

                    <strong>
                        {{ $verification?->processed_at
                            ? \Illuminate\Support\Carbon::parse(
                                $verification->processed_at
                            )->translatedFormat('d F Y')
                            : '-'
                        }}
                    </strong>

                </div>

            </div>

        </div>


        <div class="guardian-receipt-section">

            <h3>
                Informasi Siswa
            </h3>


            <div class="guardian-receipt-grid">

                <div class="guardian-receipt-row">

                    <span>
                        Nama Siswa
                    </span>

                    <strong>
                        {{ $student?->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-receipt-row">

                    <span>
                        Kelas
                    </span>

                    <strong>
                        {{ $student?->classRoom?->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-receipt-row">

                    <span>
                        NIS
                    </span>

                    <strong>
                        {{ $student?->nis ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-receipt-row">

                    <span>
                        NISN
                    </span>

                    <strong>
                        {{ $student?->nisn ?? '-' }}
                    </strong>

                </div>

            </div>

        </div>


        <div class="guardian-receipt-section">

            <h3>
                Informasi Tagihan
            </h3>


            <div class="guardian-receipt-grid">

                <div class="guardian-receipt-row">

                    <span>
                        Jenis Tagihan
                    </span>

                    <strong>
                        {{ $bill?->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-receipt-row">

                    <span>
                        Keterangan
                    </span>

                    <strong>
                        {{ $bill?->description ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-receipt-row">

                    <span>
                        Metode Pembayaran
                    </span>

                    <strong>
                        {{ $payment->paymentMethod?->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-receipt-row">

                    <span>
                        Status
                    </span>

                    <strong class="guardian-receipt-paid-text">
                        Lunas
                    </strong>

                </div>

            </div>

        </div>


        <div class="guardian-receipt-total">

            <span>
                Total Pembayaran
            </span>

            <strong>
                Rp {{ number_format(
                    $payment->amount ?? 0,
                    0,
                    ',',
                    '.'
                ) }}
            </strong>

        </div>


        <div class="guardian-receipt-footer">

            <p>
                Pembayaran telah berhasil diverifikasi oleh admin.
            </p>

            <p>
                Simpan nota ini sebagai bukti pembayaran yang sah.
            </p>

        </div>


        <div class="guardian-receipt-actions">

            <button
                type="button"
                class="guardian-receipt-print"
                onclick="window.print()"
            >
                Cetak Nota
            </button>


            <a
                href="{{ route('guardian.payment-history') }}"
                class="guardian-receipt-history"
            >
                Kembali
            </a>

        </div>

    </div>

</section>

@endsection