@extends('layouts.sikolapayapp')

@section('title', 'Detail Tagihan - SikolaPay')

@section('page-title', 'Detail Tagihan')

@section('content')

@php
    $latestPayment = $bill->latestPayment;
    $latestVerification = $latestPayment?->latestVerification;

    $isPaid = $bill->status === 'paid' || $latestPayment?->status === 'paid';
    $isRejected = ! $isPaid && ($latestVerification?->status === 'rejected' || $latestPayment?->status === 'rejected');
    $isPending = ! $isPaid && ! $isRejected && $latestPayment?->status === 'pending';
    $isFutureUnpaid = ! $isPaid && ! $isRejected && ! $isPending && $bill->status === 'unpaid' && ! $bill->hasBillingPeriodStarted();
@endphp

<section class="guardian-bill-detail-page">

    <div class="guardian-detail-header">

        <button
            type="button"
            class="guardian-back-button"
            onclick="history.back()"
        >
            ← Kembali
        </button>

        <div>
            <h2>Detail Tagihan</h2>

            <p>
                Informasi lengkap tagihan sekolah anak Anda
            </p>
        </div>

    </div>


    <div class="guardian-detail-layout">

        <div class="guardian-detail-card">

            <div class="guardian-detail-card-header">

                <div>
                    <span class="guardian-detail-label">
                        Jenis Tagihan
                    </span>

                    <h3>
                        {{ $bill->name ?? '-' }}
                    </h3>
                </div>


                @if($isPaid)

                    <span class="guardian-detail-status paid">
                        Lunas
                    </span>

                @elseif($isRejected)

                    <span class="guardian-detail-status rejected" style="background-color: #ffd6d6; color: #b91c1c;">
                        Ditolak
                    </span>

                @elseif($isPending)

                    <span class="guardian-detail-status pending">
                        Menunggu Verifikasi
                    </span>

                @elseif($isFutureUnpaid)

                    <div style="text-align: right;">
                        <span class="guardian-detail-status unpaid" style="background-color: #E5E7EB; color: #4B5563;">
                            Belum dapat dibayar
                        </span>
                        <div style="font-size: 11px; color: #6B7280; margin-top: 4px;">
                            Tersedia mulai {{ $bill->billing_period ? \Illuminate\Support\Carbon::parse($bill->billing_period)->translatedFormat('j F Y') : '-' }}
                        </div>
                    </div>

                @else

                    <span class="guardian-detail-status unpaid">
                        Belum Bayar
                    </span>

                @endif

            </div>


            <div class="guardian-detail-section">

                <h4>Data Siswa</h4>

                <div class="guardian-detail-row">

                    <span>Nama Siswa</span>

                    <strong>
                        {{ $bill->student?->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>NIS</span>

                    <strong>
                        {{ $bill->student?->nis ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>NISN</span>

                    <strong>
                        {{ $bill->student?->nisn ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>Kelas</span>

                    <strong>
                        {{ $bill->student?->classRoom?->name ?? '-' }}
                    </strong>

                </div>

            </div>


            <div class="guardian-detail-section">

                <h4>Informasi Tagihan</h4>

                <div class="guardian-detail-row">

                    <span>Periode Tagihan</span>

                    <strong>
                        {{ $bill->billing_period
                            ? \Illuminate\Support\Carbon::parse($bill->billing_period)->translatedFormat('F Y')
                            : '-'
                        }}
                    </strong>

                </div>

                @if(!empty($bill->description))

                    <div class="guardian-detail-row">

                        <span>Keterangan</span>

                        <strong>
                            {{ $bill->description }}
                        </strong>

                    </div>

                @endif

                <div class="guardian-detail-row">

                    <span>Nominal</span>

                    <strong>
                        Rp {{ number_format($bill->amount ?? 0, 0, ',', '.') }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>Jatuh Tempo</span>

                    <strong>
                        {{ $bill->due_date
                            ? \Illuminate\Support\Carbon::parse($bill->due_date)->translatedFormat('d M Y')
                            : '-'
                        }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>Status</span>

                    <strong>
                        @if($isPaid)
                            Lunas
                        @elseif($isRejected)
                            Ditolak
                        @elseif($isPending)
                            Menunggu Verifikasi
                        @elseif($isFutureUnpaid)
                            Belum dapat dibayar
                        @else
                            Belum Bayar
                        @endif
                    </strong>

                </div>

            </div>

        </div>


        <aside class="guardian-detail-summary">

            <h3>Ringkasan Tagihan</h3>

            <div class="guardian-summary-row">

                <span>Total Tagihan</span>

                <strong>
                    Rp {{ number_format($bill->amount ?? 0, 0, ',', '.') }}
                </strong>

            </div>


            @if($isPaid)

                <div class="guardian-paid-box">
                    Tagihan ini sudah lunas.
                </div>

            @elseif($isRejected)

                <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                    <div class="guardian-waiting-box" style="background-color: #fee2e2; color: #b91c1c;">
                        Pembayaran sebelumnya ditolak. Silakan ajukan ulang pembayaran.
                    </div>

                    <a
                        href="{{ route('guardian.payments.create', $bill->id) }}"
                        class="guardian-pay-button"
                    >
                        Bayar Lagi
                    </a>
                </div>

            @elseif($isPending)

                <div class="guardian-waiting-box">
                    Pembayaran sedang menunggu verifikasi admin.
                </div>

            @elseif($isFutureUnpaid)

                <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                    <div class="guardian-waiting-box" style="background-color: #F3F4F6; color: #4B5563;">
                        Tagihan periode ini belum dapat dibayar. Tersedia mulai {{ $bill->billing_period ? \Illuminate\Support\Carbon::parse($bill->billing_period)->translatedFormat('j F Y') : '-' }}.
                    </div>

                    <button
                        type="button"
                        class="guardian-pay-button"
                        disabled
                        style="background-color: #E5E7EB; color: #9CA3AF; cursor: not-allowed; border: none;"
                    >
                        Belum Dapat Dibayar
                    </button>
                </div>

            @else

                <a
                    href="{{ route('guardian.payments.create', $bill->id) }}"
                    class="guardian-pay-button"
                >
                    Bayar Sekarang
                </a>

            @endif

        </aside>

    </div>

</section>

@endsection