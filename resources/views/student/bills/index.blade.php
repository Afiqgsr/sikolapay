@extends('layouts.sikolapayapp')

@section('title', 'Tagihan Saya - SikolaPay')

@section('page-title', 'Tagihan Saya')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/student/bills.css') }}">
@endpush

@section('content')

<section class="billing-content">

    {{-- Header --}}
    <div class="page-header">

        <h2>Daftar Tagihan</h2>

        <p>
            Kelola semua tagihan sekolah Anda
        </p>

    </div>

    {{-- Summary --}}
    <div class="billing-summary">

        <div class="summary-info">

            <span>Total Tagihan Belum Dibayar</span>

            <h3>
                Rp {{ number_format($unpaidTotal, 0, ',', '.') }}
            </h3>

        </div>

        @if($unpaidBills->count() > 0)

            <a
                href="{{ route('student.payment.all') }}"
                class="pay-all-btn"
            >
                Bayar Semua
            </a>

        @endif

    </div>

    {{-- Billing card --}}
    <div class="billing-card">

        {{-- Filter --}}
        <div class="billing-filter">

            <a
                href="{{ route('student.bills.index') }}"
                class="filter-btn {{ !$status ? 'active' : '' }}"
            >
                Semua
            </a>

            <a
                href="{{ route(
                    'student.bills.index',
                    ['status' => 'unpaid']
                ) }}"
                class="filter-btn {{ $status === 'unpaid' ? 'active' : '' }}"
            >
                Belum Bayar
            </a>

            <a
                href="{{ route(
                    'student.bills.index',
                    ['status' => 'pending']
                ) }}"
                class="filter-btn {{ $status === 'pending' ? 'active' : '' }}"
            >
                Menunggu
            </a>

            <a
                href="{{ route(
                    'student.bills.index',
                    ['status' => 'rejected']
                ) }}"
                class="filter-btn {{ $status === 'rejected' ? 'active' : '' }}"
            >
                Ditolak
            </a>

            <a
                href="{{ route(
                    'student.bills.index',
                    ['status' => 'paid']
                ) }}"
                class="filter-btn {{ $status === 'paid' ? 'active' : '' }}"
            >
                Lunas
            </a>

        </div>

        {{-- Table --}}
        <div class="billing-table-wrapper">

            <table class="billing-table">

                <thead>

                    <tr>
                        <th>Jenis Tagihan</th>
                        <th>Periode Tagihan</th>
                        <th>Keterangan</th>
                        <th>Nominal</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($bills as $bill)

                        @php
                            $latestPayment = $bill->latestPayment;
                            $latestVerification = $latestPayment?->latestVerification;

                            $isPaid = $bill->status === 'paid' || $latestPayment?->status === 'paid';
                            $isRejected = ! $isPaid && ($latestVerification?->status === 'rejected' || $latestPayment?->status === 'rejected');
                            $isPending = ! $isPaid && ! $isRejected && $latestPayment?->status === 'pending';
                            $isFutureUnpaid = ! $isPaid && ! $isRejected && ! $isPending && $bill->status === 'unpaid' && ! $bill->hasBillingPeriodStarted();
                        @endphp

                        <tr>

                            {{-- Jenis tagihan --}}
                            <td>
                                {{ $bill->name }}
                            </td>

                            {{-- Periode Tagihan --}}
                            <td>
                                {{ $bill->billing_period
                                    ? \Carbon\Carbon::parse($bill->billing_period)->translatedFormat('F Y')
                                    : '-'
                                }}
                            </td>

                            {{-- Keterangan --}}
                            <td>
                                {{ $bill->description ?? '-' }}
                            </td>

                            {{-- Nominal --}}
                            <td>
                                Rp {{ number_format(
                                    $bill->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            {{-- Jatuh tempo --}}
                            <td>

                                @if($bill->due_date)

                                    {{ \Carbon\Carbon::parse(
                                        $bill->due_date
                                    )->translatedFormat('d F Y') }}

                                @else

                                    -

                                @endif

                            </td>

                            {{-- Status --}}
                            <td>

                                @if($isPaid)

                                    <span class="badge success">
                                        Lunas
                                    </span>

                                @elseif($isRejected)

                                    <span class="badge danger">
                                        Ditolak
                                    </span>

                                @elseif($isPending)

                                    <span class="badge pending">
                                        Menunggu
                                    </span>

                                @elseif($isFutureUnpaid)

                                    <div>
                                        <span class="badge secondary">
                                            Belum dapat dibayar
                                        </span>
                                        <div class="availability-notice" style="font-size: 11px; color: #6B7280; margin-top: 4px;">
                                            Tersedia mulai {{ $bill->billing_period ? \Carbon\Carbon::parse($bill->billing_period)->translatedFormat('j F Y') : '-' }}
                                        </div>
                                    </div>

                                @else

                                    <span class="badge warning">
                                        Belum Bayar
                                    </span>

                                @endif

                            </td>

                            {{-- Aksi --}}
                            <td>

                                <div class="billing-actions">

                                    <a
                                        href="{{ route(
                                            'student.bills.show',
                                            $bill->id
                                        ) }}"
                                        class="btn-detail"
                                    >
                                        Detail
                                    </a>

                                    @if($isPaid && $latestPayment)

                                        <a
                                            href="{{ route(
                                                'student.payment.receipt',
                                                $latestPayment->id
                                            ) }}"
                                            class="btn-nota"
                                        >
                                            Nota
                                        </a>

                                    @elseif($isRejected)

                                        <a
                                            href="{{ route(
                                                'student.payment',
                                                $bill->id
                                            ) }}"
                                            class="btn-pay"
                                        >
                                            Upload Ulang Bukti
                                        </a>

                                    @elseif($isPending)

                                        <span class="btn-pending">
                                            Menunggu
                                        </span>

                                    @elseif($isFutureUnpaid)

                                        <span class="btn-disabled" style="display: inline-flex; align-items: center; justify-content: center; min-height: 30px; padding: 6px 11px; border-radius: 6px; font-size: 10px; font-weight: 500; background: #E5E7EB; color: #9CA3AF; cursor: not-allowed; box-sizing: border-box;">
                                            Belum Tersedia
                                        </span>

                                    @else

                                        <a
                                            href="{{ route(
                                                'student.payment',
                                                $bill->id
                                            ) }}"
                                            class="btn-pay"
                                        >
                                            Bayar
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="empty-state"
                            >
                                Tidak ada tagihan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection