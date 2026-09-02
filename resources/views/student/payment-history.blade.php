@extends('layouts.sikolapayapp')

@section('title', 'Riwayat Pembayaran - SikolaPay')

@section('page-title', 'Riwayat Pembayaran')

@section('content')

<section class="payment-history-content">

    {{-- Header --}}
    <div class="page-header">

        <div>

            <h2>
                Riwayat Pembayaran
            </h2>

            <p>
                Rekap seluruh transaksi pembayaran Anda
            </p>

        </div>

    </div>

    {{-- Statistik --}}
    <div class="history-stats">

        <div class="history-card">

            <span>
                Total Transaksi
            </span>

            <h3>
                {{ $totalTransactions }}
            </h3>

            <small>
                Tahun {{ now()->year }}
            </small>

        </div>

        <div class="history-card">

            <span>
                Total Dibayar
            </span>

            <h3>
                Rp {{ number_format(
                    $totalPaid,
                    0,
                    ',',
                    '.'
                ) }}
            </h3>

            <small>
                Tahun {{ now()->year }}
            </small>

        </div>

        <div class="history-card">

            <span>
                Terakhir Dibayar
            </span>

            @if($lastPayment)

                <h3>
                    {{ $lastPayment->paid_at
                        ->translatedFormat('d F Y') }}
                </h3>

                <small>
                    {{ $lastPayment->bill?->name ?? '-' }}
                </small>

            @else

                <h3>
                    -
                </h3>

                <small>
                    Belum ada pembayaran
                </small>

            @endif

        </div>

    </div>

    {{-- Tabel riwayat --}}
    <div class="history-table-card">

        <div class="history-toolbar">

            <div class="history-toolbar-title">

                <h3>
                    Riwayat Transaksi
                </h3>

                <span>
                    {{ $payments->total() }} transaksi
                </span>

            </div>

            <button
                type="button"
                class="btn-export"
                id="exportPaymentHistory"
            >

                <img
                    src="{{ asset('assets/img/export.svg') }}"
                    alt="Export"
                >

                Export

            </button>

        </div>

        {{-- Filter --}}
        <form
            method="GET"
            action="{{ route('student.payment-history') }}"
            class="history-filter"
        >

            <div class="search-box">

                <img
                    src="{{ asset('assets/img/search-admin.svg') }}"
                    alt="Search"
                >

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari transaksi..."
                >

            </div>

            <select name="year">

                <option value="">
                    Semua Tahun
                </option>

                @for(
                    $year = now()->year;
                    $year >= now()->year - 2;
                    $year--
                )

                    <option
                        value="{{ $year }}"
                        {{ request('year') == $year
                            ? 'selected'
                            : ''
                        }}
                    >
                        {{ $year }}
                    </option>

                @endfor

            </select>

            <select name="type">

                <option value="">
                    Semua Jenis
                </option>

                <option
                    value="spp"
                    {{ request('type') == 'spp'
                        ? 'selected'
                        : ''
                    }}
                >
                    SPP
                </option>

                <option
                    value="ujian"
                    {{ request('type') == 'ujian'
                        ? 'selected'
                        : ''
                    }}
                >
                    Ujian
                </option>

                <option
                    value="gedung"
                    {{ request('type') == 'gedung'
                        ? 'selected'
                        : ''
                    }}
                >
                    Gedung
                </option>

                <option
                    value="kegiatan"
                    {{ request('type') == 'kegiatan'
                        ? 'selected'
                        : ''
                    }}
                >
                    Kegiatan
                </option>

            </select>

            <select name="status">

                <option value="">
                    Semua Status
                </option>

                <option
                    value="paid"
                    {{ request('status') == 'paid'
                        ? 'selected'
                        : ''
                    }}
                >
                    Lunas
                </option>

                <option
                    value="pending"
                    {{ request('status') == 'pending'
                        ? 'selected'
                        : ''
                    }}
                >
                    Menunggu
                </option>

                <option
                    value="rejected"
                    {{ request('status') == 'rejected'
                        ? 'selected'
                        : ''
                    }}
                >
                    Ditolak
                </option>

            </select>

            <button
                type="submit"
                class="btn-filter"
            >
                Terapkan
            </button>

            @if(request()->hasAny([
                'search',
                'year',
                'type',
                'status'
            ]))

                <a
                    href="{{ route('student.payment-history') }}"
                    class="btn-reset"
                >
                    Reset
                </a>

            @endif

        </form>

        {{-- Table --}}
        <div class="table-wrapper">

            <table class="history-table">

                <thead>

                    <tr>
                        <th>No. Transaksi</th>
                        <th>Jenis Tagihan</th>
                        <th>Periode</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($payments as $payment)

                        @php
                            $latestVerification =
                                $payment->latestVerification;

                            $hasRejectedVerification =
                                $latestVerification?->status === 'rejected';

                            $isResubmitted =
                                $hasRejectedVerification
                                && $payment->proof_uploaded_at
                                && $latestVerification?->processed_at
                                && $payment->proof_uploaded_at->gt(
                                    $latestVerification->processed_at
                                );

                            $isRejected =
                                $hasRejectedVerification
                                && !$isResubmitted;
                        @endphp

                        <tr>

                            <td>

                                <span class="transaction-number">
                                    #{{ $payment->payment_number }}
                                </span>

                            </td>

                            <td>
                                {{ $payment->bill?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $payment->bill?->description ?? '-' }}
                            </td>

                            <td>

                                <strong>
                                    Rp {{ number_format(
                                        $payment->amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>

                            </td>

                            <td>
                                {{ $payment->paymentMethod?->name ?? '-' }}
                            </td>

                            <td>

                                @if($payment->paid_at)

                                    {{ $payment->paid_at
                                        ->translatedFormat('d F Y') }}

                                @elseif($payment->proof_uploaded_at)

                                    {{ $payment->proof_uploaded_at
                                        ->translatedFormat('d F Y') }}

                                @else

                                    {{ $payment->created_at
                                        ->translatedFormat('d F Y') }}

                                @endif

                            </td>

                            {{-- Status --}}
                            <td>

                                @if($payment->status === 'paid')

                                    <span class="badge success">
                                        Lunas
                                    </span>

                                @elseif($isRejected)

                                    <span class="badge danger">
                                        Ditolak
                                    </span>

                                @elseif(
                                    $payment->status === 'pending'
                                    && $isResubmitted
                                )

                                    <span class="badge pending">
                                        Menunggu Verifikasi Ulang
                                    </span>

                                @elseif($payment->status === 'pending')

                                    <span class="badge pending">
                                        Menunggu
                                    </span>

                                @else

                                    <span class="badge warning">
                                        {{ ucfirst($payment->status) }}
                                    </span>

                                @endif

                            </td>

                            {{-- Aksi --}}
                            <td>

                                @if($payment->status === 'paid')

                                    <a
                                        href="{{ route(
                                            'student.payment.receipt',
                                            $payment->id
                                        ) }}"
                                        class="btn-nota"
                                    >
                                        Nota
                                    </a>

                                @elseif($isRejected)

                                    <a
                                        href="{{ route(
                                            'student.payment',
                                            $payment->bill_id
                                        ) }}"
                                        class="btn-pay"
                                    >
                                        Upload Ulang Bukti
                                    </a>

                                @elseif(
                                    $payment->status === 'pending'
                                    && $isResubmitted
                                )

                                    <span class="btn-pending">
                                        Menunggu Verifikasi Ulang
                                    </span>

                                @elseif($payment->status === 'pending')

                                    <span class="btn-pending">
                                        Menunggu
                                    </span>

                                @else

                                    <span class="action-empty">
                                        -
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="empty-state"
                            >
                                Tidak ada transaksi yang ditemukan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        @if($payments->hasPages())

            <div class="history-pagination">

                <div class="pagination-info">

                    Menampilkan
                    {{ $payments->firstItem() ?? 0 }}
                    -
                    {{ $payments->lastItem() ?? 0 }}
                    dari
                    {{ $payments->total() }}
                    transaksi

                </div>

                <div class="pagination-links">

                    @if($payments->onFirstPage())

                        <span class="pagination-button disabled">
                            ‹
                        </span>

                    @else

                        <a
                            href="{{ $payments->previousPageUrl() }}"
                            class="pagination-button"
                        >
                            ‹
                        </a>

                    @endif

                    @foreach(
                        range(
                            1,
                            $payments->lastPage()
                        )
                        as $page
                    )

                        @if($page == $payments->currentPage())

                            <span class="pagination-button active">
                                {{ $page }}
                            </span>

                        @else

                            <a
                                href="{{ $payments->url($page) }}"
                                class="pagination-button"
                            >
                                {{ $page }}
                            </a>

                        @endif

                    @endforeach

                    @if($payments->hasMorePages())

                        <a
                            href="{{ $payments->nextPageUrl() }}"
                            class="pagination-button"
                        >
                            ›
                        </a>

                    @else

                        <span class="pagination-button disabled">
                            ›
                        </span>

                    @endif

                </div>

            </div>

        @endif

    </div>

</section>

@endsection