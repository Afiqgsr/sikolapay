@extends('layouts.sikolapayapp')

@section('title', 'Nota Pembayaran - SikolaPay')

@section('page-title', 'Nota')

@push('styles') <link rel="stylesheet" href="{{ asset('css/pages/student/payment-receipt.css') }}">
@endpush

@section('content')

<section class="invoice-page">

<!-- HEADER -->
<div class="invoice-header">

    <button
        class="back-button"
        type="button"
        onclick="history.back()"
    >
        <span>←</span>
        <span>Kembali ke Riwayat</span>
    </button>


    <div class="invoice-actions">

        <button
            type="button"
            class="invoice-btn outline"
            onclick="window.print()"
        >
            Download PDF
        </button>

        <button
            type="button"
            class="invoice-btn primary"
            onclick="window.print()"
        >
            Cetak
        </button>

    </div>

</div>


<!-- INVOICE CARD -->
<div class="invoice-card">


    <!-- SCHOOL HEADER -->
    <div class="invoice-school">

        <div class="school-logo">
            SP
        </div>


        <div class="school-info">

            <h2>SMA Nusantara 1</h2>

            <p>
                Jl. Pendidikan No. 10, Jakarta Selatan
            </p>

            <p>
                Telp: (021) 123-4567 |
                info@smanusantara1.sch.id
            </p>

        </div>


        <div class="invoice-status">
            Lunas
        </div>

    </div>


    <!-- INVOICE INFO -->
    <div class="invoice-info">

        <div class="invoice-number">

            <span>Nota Pembayaran</span>

            <h3>
                #PAY-{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}
            </h3>

        </div>


        <div class="invoice-date">

            <div class="invoice-date-row">

                <span>Tanggal Bayar</span>

                <strong>
                    {{ $payment->created_at
                        ? $payment->created_at->translatedFormat('d F Y')
                        : '-'
                    }}
                </strong>

            </div>


            <div class="invoice-date-row">

                <span>Diverifikasi</span>

                <strong>
                    {{ $payment->latestVerification?->processed_at
                        ? \Carbon\Carbon::parse(
                            $payment->latestVerification->processed_at
                          )->translatedFormat('d F Y')
                        : '-'
                    }}
                </strong>

            </div>

        </div>

    </div>


    <!-- STUDENT + PAYMENT -->
    <div class="invoice-detail-box">


        <!-- DATA SISWA -->
        <div>

            <small>DATA SISWA</small>

            <h4>
                {{ $student->name }}
            </h4>

            <p>
                NISN: {{ $student->nisn ?? '-' }}
            </p>

            <p>
                Kelas: {{ $student->classRoom?->name ?? '-' }}
            </p>

        </div>


        <!-- METODE PEMBAYARAN -->
        <div>

            <small>METODE PEMBAYARAN</small>

            <h4>
                {{ $payment->paymentMethod?->name ?? '-' }}
            </h4>

            <p>
                No. Ref:
                {{ $payment->reference_number ?? $payment->id }}
            </p>

            <p>
                TA: 2026/2027
            </p>

        </div>

    </div>


    <!-- PAYMENT TABLE -->
    <div class="invoice-table">


        <div class="invoice-row heading">

            <span>Keterangan</span>

            <span>Nominal</span>

        </div>


        <div class="invoice-row">

            <span>
                {{ $payment->bill->name }}
            </span>

            <span>
                Rp {{ number_format(
                    $payment->bill->amount,
                    0,
                    ',',
                    '.'
                ) }}
            </span>

        </div>


        <div class="invoice-row">

            <span>
                Biaya Administrasi
            </span>

            <span>
                Rp 0
            </span>

        </div>


        <div class="invoice-row total">

            <strong>
                TOTAL
            </strong>

            <strong>
                Rp {{ number_format(
                    $payment->bill->amount,
                    0,
                    ',',
                    '.'
                ) }}
            </strong>

        </div>

    </div>


    <!-- FOOTER -->
    <div class="invoice-footer">

        <p>
            Nota ini dicetak secara otomatis oleh sistem
            SikolaPay. Dokumen ini sah tanpa tanda tangan.
        </p>

        <p>
            Dicetak pada:
            {{ now()->translatedFormat('d F Y H:i') }}
        </p>

    </div>

</div>

</section>

@endsection
