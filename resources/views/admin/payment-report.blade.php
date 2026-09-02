@extends('layouts.sikolapayapp')

@section('title', 'Laporan Pembayaran - SikolaPay')

@section('page-title', 'Laporan Pembayaran')

@section('content')

<section class="report-page">

    {{-- Header --}}
    <div class="report-header">

        <h2 class="report-title">
            Laporan Pembayaran
        </h2>

        <p class="report-description">
            Rekap dan ekspor data pembayaran berdasarkan periode
        </p>

    </div>


    {{-- Filter --}}
    <div class="report-filter-card">

        <form
            action="{{ route('admin.reports.index') }}"
            method="GET"
            class="report-filter"
        >

            {{-- Tanggal Awal --}}
            <div class="report-filter-item">

                <label for="startDate">
                    Tanggal Awal
                </label>

                <div class="report-input">

                    <input
                        type="date"
                        id="startDate"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        autocomplete="off"
                    >

                    <img
                        src="{{ asset('assets/img/date-black-admin.svg') }}"
                        alt=""
                    >

                </div>

            </div>


            {{-- Tanggal Akhir --}}
            <div class="report-filter-item">

                <label for="endDate">
                    Tanggal Akhir
                </label>

                <div class="report-input">

                    <input
                        type="date"
                        id="endDate"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        autocomplete="off"
                    >

                    <img
                        src="{{ asset('assets/img/date-black-admin.svg') }}"
                        alt=""
                    >

                </div>

            </div>


            {{-- Kelas --}}
            <div class="report-filter-item">

                <label for="classFilter">
                    Kelas
                </label>

                <div class="report-select">

                    <select
                        id="classFilter"
                        name="class_room_id"
                    >

                        <option value="">
                            Semua Kelas
                        </option>

                        @foreach($classRooms as $classRoom)

                            <option
                                value="{{ $classRoom->id }}"
                                @selected(
                                    (string) request('class_room_id')
                                    === (string) $classRoom->id
                                )
                            >
                                {{ $classRoom->name }}
                            </option>

                        @endforeach

                    </select>

                    <img
                        src="{{ asset('assets/img/Expand_down_light-black-admin.svg') }}"
                        alt=""
                    >

                </div>

            </div>


            {{-- Status --}}
            <div class="report-filter-item">

                <label for="statusFilter">
                    Status
                </label>

                <div class="report-select">

                    <select
                        id="statusFilter"
                        name="status"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="paid"
                            @selected(request('status') === 'paid')
                        >
                            Lunas
                        </option>

                        <option
                            value="pending"
                            @selected(request('status') === 'pending')
                        >
                            Menunggu Verifikasi
                        </option>

                        <option
                            value="unpaid"
                            @selected(request('status') === 'unpaid')
                        >
                            Belum Lunas
                        </option>

                    </select>

                    <img
                        src="{{ asset('assets/img/Expand_down_light-black-admin.svg') }}"
                        alt=""
                    >

                </div>

            </div>


            {{-- Tombol Filter --}}
            <button
                type="submit"
                class="btn-report-filter"
            >

                <img
                    src="{{ asset('assets/img/filter-admin.svg') }}"
                    alt=""
                >

                <span>
                    Filter
                </span>

            </button>


            {{-- Reset --}}
            @if(
                request('start_date')
                || request('end_date')
                || request('class_room_id')
                || request('status')
            )

                <a
                    href="{{ route('admin.reports.index') }}"
                    class="btn-report-reset"
                >
                    Reset
                </a>

            @endif

        </form>

    </div>


    {{-- Summary --}}
    <div class="report-summary">

        {{-- Total Pemasukan --}}
        <div class="report-summary-card">

            <div class="report-summary-icon">

                <img
                    src="{{ asset('assets/img/money.svg') }}"
                    alt=""
                >

            </div>

            <div class="report-summary-info">

                <strong>
                    Rp {{ number_format(
                        $totalIncome,
                        0,
                        ',',
                        '.'
                    ) }}
                </strong>

                <span>
                    Total Pemasukan
                </span>

            </div>

        </div>


        {{-- Total Transaksi --}}
        <div class="report-summary-card">

            <div class="report-summary-icon">

                <img
                    src="{{ asset('assets/img/Done_all_alt_round-green-admin.svg') }}"
                    alt=""
                >

            </div>

            <div class="report-summary-info">

                <strong>
                    {{ $totalSuccessfulTransactions }}
                </strong>

                <span>
                    Total Transaksi Berhasil
                </span>

            </div>

        </div>

    </div>


    {{-- Tabel Laporan --}}
    <div class="report-table-card">

        <div class="report-table-header">

            <div>

                <h3>
                    Rincian Laporan
                </h3>

                <p>
                    {{ $reports->total() }} data ditemukan
                </p>

            </div>


            {{-- Export --}}
            <div class="report-export-actions">

                <a
                    href="#"
                    class="btn-export btn-export-pdf"
                >

                    <img
                        src="{{ asset('assets/img/export-red-admin.svg') }}"
                        alt=""
                    >

                    <span>
                        Export PDF
                    </span>

                </a>


                <a
                    href="#"
                    class="btn-export btn-export-excel"
                >

                    <img
                        src="{{ asset('assets/img/export-green-admin.svg') }}"
                        alt=""
                    >

                    <span>
                        Export Excel
                    </span>

                </a>

            </div>

        </div>


        {{-- Table --}}
        <div class="report-table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>

                        <th class="col-no">
                            No
                        </th>

                        <th class="col-date">
                            Tanggal
                        </th>

                        <th class="col-student">
                            Nama Siswa
                        </th>

                        <th class="col-class">
                            Kelas
                        </th>

                        <th class="col-bill">
                            Jenis Tagihan
                        </th>

                        <th class="col-nominal">
                            Nominal
                        </th>

                        <th class="col-status">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($reports as $bill)

                        @php
                            $payment = $bill->latestPayment;

                            $latestVerification =
                                $payment?->latestVerification;

                            $hasRejectedVerification =
                                $latestVerification?->status === 'rejected';

                            $isResubmitted =
                                $hasRejectedVerification
                                && $payment?->proof_uploaded_at
                                && $latestVerification?->processed_at
                                && $payment->proof_uploaded_at->gt(
                                    $latestVerification->processed_at
                                );

                            if (
                                $bill->status === 'paid'
                                || $payment?->status === 'paid'
                            ) {

                                $displayStatus = 'paid';

                            } elseif (
                                $payment?->status === 'pending'
                                && $payment?->proof_of_payment
                                && (
                                    !$hasRejectedVerification
                                    || $isResubmitted
                                )
                            ) {

                                $displayStatus = 'pending';

                            } else {

                                $displayStatus = 'unpaid';
                            }


                            $reportDate =
                                $payment?->paid_at
                                ?? $payment?->created_at
                                ?? $bill->created_at;
                        @endphp


                        <tr>

                            {{-- No --}}
                            <td class="col-no">

                                {{ $reports->firstItem() + $loop->index }}

                            </td>


                            {{-- Tanggal --}}
                            <td class="col-date">

                                {{ $reportDate
                                    ? $reportDate->translatedFormat('d M Y')
                                    : '-'
                                }}

                            </td>


                            {{-- Nama Siswa --}}
                            <td class="col-student">

                                <div class="report-student-name">

                                    {{ $bill->student?->name ?? '-' }}

                                </div>

                            </td>


                            {{-- Kelas --}}
                            <td class="col-class">

                                {{ $bill->student?->classRoom?->name ?? '-' }}

                            </td>


                            {{-- Jenis Tagihan --}}
                            <td class="col-bill">

                                {{ $bill->name }}

                            </td>


                            {{-- Nominal --}}
                            <td class="col-nominal">

                                Rp {{ number_format(
                                    $bill->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- Status --}}
                            <td class="col-status">

                                @if($displayStatus === 'paid')

                                    <span class="status-badge status-paid">
                                        Lunas
                                    </span>

                                @elseif($displayStatus === 'pending')

                                    <span class="status-badge status-pending">
                                        Menunggu
                                    </span>

                                @else

                                    <span class="status-badge status-unpaid">
                                        Belum Lunas
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="report-empty"
                            >
                                Tidak ada data laporan yang ditemukan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($reports->hasPages())

            <div class="report-pagination">

                {{ $reports->links() }}

            </div>

        @endif

    </div>

</section>


@push('scripts')
    @vite('resources/js/pages/admin/payment-report.js')
@endpush

@endsection