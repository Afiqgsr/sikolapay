@extends('layouts.sikolapayapp')

@section('title', 'Riwayat Pembayaran - SikolaPay')

@section('page-title', 'Riwayat Pembayaran')

@section('content')

<section class="guardian-history-page">

    <div class="guardian-history-header">

        <h2>
            Riwayat Pembayaran
        </h2>

        <p>
            Lihat riwayat pembayaran sekolah seluruh anak Anda
        </p>

    </div>


    {{-- Statistik --}}
    <div class="guardian-history-stats">

        <div class="guardian-history-stat">

            <span>
                Total Pembayaran
            </span>

            <strong>
                {{ $totalPayments }}
            </strong>

        </div>


        <div class="guardian-history-stat">

            <span>
                Menunggu Verifikasi
            </span>

            <strong>
                {{ $pendingPayments }}
            </strong>

        </div>


        <div class="guardian-history-stat">

            <span>
                Lunas
            </span>

            <strong>
                {{ $paidPayments }}
            </strong>

        </div>


        <div class="guardian-history-stat">

            <span>
                Ditolak
            </span>

            <strong>
                {{ $rejectedPayments }}
            </strong>

        </div>

    </div>


    {{-- Filter --}}
    <div class="guardian-history-filter-card">

        <form
            method="GET"
            action="{{ route('guardian.payment-history') }}"
            class="guardian-history-filter"
        >

            <div class="guardian-history-filter-group">

                <label for="student">
                    Anak
                </label>

                <select
                    name="student"
                    id="student"
                >

                    <option value="">
                        Semua Anak
                    </option>

                    @foreach($students as $student)

                        <option
                            value="{{ $student->id }}"
                            {{ request('student') == $student->id ? 'selected' : '' }}
                        >
                            {{ $student->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="guardian-history-filter-group">

                <label for="status">
                    Status
                </label>

                <select
                    name="status"
                    id="status"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="pending"
                        {{ request('status') === 'pending' ? 'selected' : '' }}
                    >
                        Menunggu Verifikasi
                    </option>

                    <option
                        value="paid"
                        {{ request('status') === 'paid' ? 'selected' : '' }}
                    >
                        Lunas
                    </option>

                    <option
                        value="rejected"
                        {{ request('status') === 'rejected' ? 'selected' : '' }}
                    >
                        Ditolak
                    </option>

                </select>

            </div>


            <div class="guardian-history-filter-group guardian-history-search">

                <label for="search">
                    Cari
                </label>

                <div class="guardian-history-search-box">

                    <img
                        src="{{ asset('assets/img/search-black-superadmin.svg') }}"
                        alt=""
                    >

                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Cari transaksi, tagihan, anak..."
                    >

                </div>

            </div>


            <div class="guardian-history-filter-actions">

                <button type="submit">
                    Terapkan
                </button>

                @if(
                    request()->filled('student')
                    || request()->filled('status')
                    || request()->filled('search')
                )

                    <a
                        href="{{ route('guardian.payment-history') }}"
                    >
                        Reset
                    </a>

                @endif

            </div>

        </form>

    </div>


    {{-- Riwayat Pembayaran --}}
    <div class="guardian-history-card">

        <div class="guardian-history-card-header">

            <div>

                <h3>
                    Daftar Pembayaran
                </h3>

                <p>
                    Riwayat transaksi pembayaran sekolah anak Anda
                </p>

            </div>

        </div>


        <div class="guardian-history-table-wrapper">

            <table class="guardian-history-table">

                <thead>

                    <tr>

                        <th>
                            No. Transaksi
                        </th>

                        <th>
                            Anak
                        </th>

                        <th>
                            Tagihan
                        </th>

                        <th>
                            Nominal
                        </th>

                        <th>
                            Metode
                        </th>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($payments as $payment)

                        @php

                            $statusLabel = match ($payment->status) {
                                'paid' => 'Lunas',
                                'pending' => 'Menunggu',
                                'rejected' => 'Ditolak',
                                default => ucfirst($payment->status),
                            };

                        @endphp


                        <tr>

                            {{-- Nomor Transaksi --}}
                            <td>

                                <span
                                    class="guardian-history-number"
                                    title="{{ $payment->payment_number }}"
                                >
                                    {{ $payment->payment_number }}
                                </span>

                            </td>


                            {{-- Anak --}}
                            <td>

                                <div class="guardian-history-student">

                                    <strong>
                                        {{ $payment->bill?->student?->name ?? '-' }}
                                    </strong>

                                    <span>
                                        {{ $payment->bill?->student?->classRoom?->name ?? '-' }}
                                    </span>

                                </div>

                            </td>


                            {{-- Tagihan --}}
                            <td>

                                <div class="guardian-history-bill">

                                    <strong>
                                        {{ $payment->bill?->name ?? '-' }}
                                    </strong>

                                    @if(!empty($payment->bill?->description))

                                        <span>
                                            {{ $payment->bill->description }}
                                        </span>

                                    @endif

                                </div>

                            </td>


                            {{-- Nominal --}}
                            <td>

                                <strong class="guardian-history-amount">

                                    Rp {{ number_format(
                                        $payment->amount ?? 0,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </strong>

                            </td>


                            {{-- Metode --}}
                            <td>

                                <span class="guardian-history-method">
                                    {{ $payment->paymentMethod?->name ?? '-' }}
                                </span>

                            </td>


                            {{-- Tanggal --}}
                            <td>

                                <span class="guardian-history-date">

                                    {{ $payment->paid_at
                                        ? \Illuminate\Support\Carbon::parse(
                                            $payment->paid_at
                                        )->translatedFormat('d M Y')
                                        : $payment->created_at?->translatedFormat('d M Y')
                                    }}

                                </span>

                            </td>


                            {{-- Status --}}
                            <td>

                                <span
                                    class="guardian-history-status {{ $payment->status }}"
                                >
                                    {{ $statusLabel }}
                                </span>

                            </td>


                            {{-- Aksi --}}
                            <td>

                                <div class="guardian-history-actions">

                                    <a
                                        href="{{ route(
                                            'guardian.payments.show',
                                            $payment->id
                                        ) }}"
                                        class="guardian-history-detail"
                                    >
                                        Detail
                                    </a>


                                    @if($payment->status === 'paid')

                                        <a
                                            href="{{ route(
                                                'guardian.payments.receipt',
                                                $payment->id
                                            ) }}"
                                            class="guardian-history-receipt"
                                        >
                                            Nota
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="guardian-history-empty"
                            >

                                <div class="guardian-history-empty-state">

                                    <strong>
                                        Belum ada riwayat pembayaran
                                    </strong>

                                    <p>
                                        Riwayat transaksi pembayaran akan muncul di sini.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($payments->hasPages())

            <div class="guardian-history-pagination">

                <div class="guardian-history-pagination-info">

                    Menampilkan
                    {{ $payments->firstItem() }}
                    sampai
                    {{ $payments->lastItem() }}
                    dari
                    {{ $payments->total() }}
                    data

                </div>


                <div class="guardian-history-pagination-buttons">

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


                    @php

                        $startPage = max(
                            1,
                            $payments->currentPage() - 2
                        );

                        $endPage = min(
                            $payments->lastPage(),
                            $payments->currentPage() + 2
                        );

                    @endphp


                    @foreach(
                        $payments->getUrlRange(
                            $startPage,
                            $endPage
                        )
                        as $page => $url
                    )

                        <a
                            href="{{ $url }}"
                            class="pagination-button {{ $page === $payments->currentPage() ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </a>

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