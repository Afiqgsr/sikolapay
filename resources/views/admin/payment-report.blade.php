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
            Rekapitulasi dan rincian data pembayaran tagihan siswa
        </p>
    </div>

    {{-- Tab Navigation --}}
    <div class="report-tabs">
        <a
            href="{{ route('admin.reports.index', ['tab' => 'detail']) }}"
            class="report-tab-btn {{ $activeTab !== 'recap' ? 'active' : '' }}"
        >
            Rincian Transaksi
        </a>
        <a
            href="{{ route('admin.reports.index', ['tab' => 'recap']) }}"
            class="report-tab-btn {{ $activeTab === 'recap' ? 'active' : '' }}"
        >
            Rekap per Kelas
        </a>
    </div>

    @if($activeTab !== 'recap')
        {{-- ========================================================== --}}
        {{-- TAB 1: RINCIAN TRANSAKSI (FITUR EXISTING UTUH)              --}}
        {{-- ========================================================== --}}

        {{-- Filter --}}
        <div class="report-filter-card">
            <form
                action="{{ route('admin.reports.index') }}"
                method="GET"
                class="report-filter"
            >
                <input type="hidden" name="tab" value="detail">

                {{-- Periode Tagihan Awal --}}
                <div class="report-filter-item">
                    <label for="startDate">
                        Periode Tagihan Awal
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

                {{-- Periode Tagihan Akhir --}}
                <div class="report-filter-item">
                    <label for="endDate">
                        Periode Tagihan Akhir
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

                {{-- Kelas (Rombel) --}}
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
                                    @selected((string) request('class_room_id') === (string) $classRoom->id)
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
                    <span>Filter</span>
                </button>

                {{-- Reset --}}
                @if(
                    request('start_date')
                    || request('end_date')
                    || request('class_room_id')
                    || request('status')
                )
                    <a
                        href="{{ route('admin.reports.index', ['tab' => 'detail']) }}"
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
                        Rp {{ number_format($totalIncome, 0, ',', '.') }}
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

        {{-- Tabel Laporan Rincian Transaksi --}}
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

                {{-- Export Dummy Existing --}}
                <div class="report-export-actions">
                    <a
                        href="#"
                        class="btn-export btn-export-pdf"
                    >
                        <img
                            src="{{ asset('assets/img/export-red-admin.svg') }}"
                            alt=""
                        >
                        <span>Export PDF</span>
                    </a>
                    <a
                        href="#"
                        class="btn-export btn-export-excel"
                    >
                        <img
                            src="{{ asset('assets/img/export-green-admin.svg') }}"
                            alt=""
                        >
                        <span>Export Excel</span>
                    </a>
                </div>
            </div>

            {{-- Table --}}
            <div class="report-table-wrapper">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-date">Tanggal</th>
                            <th class="col-student">Nama Siswa</th>
                            <th class="col-class">Kelas</th>
                            <th class="col-bill">Jenis Tagihan</th>
                            <th class="col-nominal">Nominal</th>
                            <th class="col-status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $bill)
                            @php
                                $payment = $bill->latestPayment;
                                $latestVerification = $payment?->latestVerification;
                                $hasRejectedVerification = $latestVerification?->status === 'rejected';
                                $isResubmitted = $hasRejectedVerification
                                    && $payment?->proof_uploaded_at
                                    && $latestVerification?->processed_at
                                    && $payment->proof_uploaded_at->gt($latestVerification->processed_at);

                                if ($bill->status === 'paid' || $payment?->status === 'paid') {
                                    $displayStatus = 'paid';
                                } elseif (
                                    $payment?->status === 'pending'
                                    && $payment?->proof_of_payment
                                    && (!$hasRejectedVerification || $isResubmitted)
                                ) {
                                    $displayStatus = 'pending';
                                } else {
                                    $displayStatus = 'unpaid';
                                }

                                $reportDate = $payment?->paid_at ?? $payment?->created_at ?? $bill->created_at;
                            @endphp
                            <tr>
                                <td class="col-no">
                                    {{ $reports->firstItem() + $loop->index }}
                                </td>
                                <td class="col-date">
                                    {{ $reportDate ? $reportDate->translatedFormat('d M Y') : '-' }}
                                </td>
                                <td class="col-student">
                                    <div class="report-student-name">
                                        {{ $bill->student?->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="col-class">
                                    {{ $bill->student?->classRoom?->name ?? '-' }}
                                </td>
                                <td class="col-bill">
                                    {{ $bill->name }}
                                </td>
                                <td class="col-nominal">
                                    Rp {{ number_format($bill->amount, 0, ',', '.') }}
                                </td>
                                <td class="col-status">
                                    @if($displayStatus === 'paid')
                                        <span class="status-badge status-paid">Lunas</span>
                                    @elseif($displayStatus === 'pending')
                                        <span class="status-badge status-pending">Menunggu</span>
                                    @else
                                        <span class="status-badge status-unpaid">Belum Lunas</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="report-empty">
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

    @else
        {{-- ========================================================== --}}
        {{-- TAB 2: REKAP PER KELAS (FITUR REKAPITULASI BARU)            --}}
        {{-- ========================================================== --}}

        {{-- Filter Card Rekap --}}
        <div class="report-filter-card">
            <form
                action="{{ route('admin.reports.index') }}"
                method="GET"
                class="report-filter"
            >
                <input type="hidden" name="tab" value="recap">

                {{-- Bulan --}}
                <div class="report-filter-item">
                    <label for="recapMonth">
                        Bulan
                    </label>
                    <div class="report-select">
                        <select
                            id="recapMonth"
                            name="month"
                            required
                        >
                            @php
                                $months = [
                                    1 => 'Januari',
                                    2 => 'Februari',
                                    3 => 'Maret',
                                    4 => 'April',
                                    5 => 'Mei',
                                    6 => 'Juni',
                                    7 => 'Juli',
                                    8 => 'Agustus',
                                    9 => 'September',
                                    10 => 'Oktober',
                                    11 => 'November',
                                    12 => 'Desember',
                                ];
                            @endphp
                            @foreach($months as $mNum => $mName)
                                <option
                                    value="{{ $mNum }}"
                                    @selected($selectedMonth === $mNum)
                                >
                                    {{ $mName }}
                                </option>
                            @endforeach
                        </select>
                        <img
                            src="{{ asset('assets/img/Expand_down_light-black-admin.svg') }}"
                            alt=""
                        >
                    </div>
                </div>

                {{-- Tahun --}}
                <div class="report-filter-item">
                    <label for="recapYear">
                        Tahun
                    </label>
                    <div class="report-select">
                        <select
                            id="recapYear"
                            name="year"
                            required
                        >
                            @foreach($availableYears as $yr)
                                <option
                                    value="{{ $yr }}"
                                    @selected($selectedYear === $yr)
                                >
                                    {{ $yr }}
                                </option>
                            @endforeach
                        </select>
                        <img
                            src="{{ asset('assets/img/Expand_down_light-black-admin.svg') }}"
                            alt=""
                        >
                    </div>
                </div>

                {{-- Jenis Tagihan --}}
                <div class="report-filter-item" style="flex: 1.2 1 180px;">
                    <label for="recapBillName">
                        Jenis Tagihan
                    </label>
                    <div class="report-select">
                        <select
                            id="recapBillName"
                            name="bill_name"
                            required
                        >
                            <option value="">
                                -- Pilih Jenis Tagihan --
                            </option>
                            @foreach($availableBillTypes as $bType)
                                <option
                                    value="{{ $bType }}"
                                    @selected($selectedBillName === $bType)
                                >
                                    {{ $bType }}
                                </option>
                            @endforeach
                        </select>
                        <img
                            src="{{ asset('assets/img/Expand_down_light-black-admin.svg') }}"
                            alt=""
                        >
                    </div>
                </div>

                {{-- Tingkat Kelas --}}
                <div class="report-filter-item" style="flex: 1 1 150px;">
                    <label for="recapGrade">
                        Tingkat Kelas
                    </label>
                    <div class="report-select">
                        <select
                            id="recapGrade"
                            name="grade"
                        >
                            <option value="" @selected(empty($selectedGrade))>
                                Semua Tingkat
                            </option>
                            <option value="X" @selected($selectedGrade === 'X')>
                                Kelas X
                            </option>
                            <option value="XI" @selected($selectedGrade === 'XI')>
                                Kelas XI
                            </option>
                            <option value="XII" @selected($selectedGrade === 'XII')>
                                Kelas XII
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
                    <span>Tampilkan</span>
                </button>

                {{-- Reset --}}
                @if($recapFiltered)
                    <a
                        href="{{ route('admin.reports.index', ['tab' => 'recap']) }}"
                        class="btn-report-reset"
                    >
                        Reset
                    </a>
                @endif
            </form>
        </div>

        @if($recapFiltered && $recapData)
            @if(!$recapData['has_data'])
                <div class="report-empty-box">
                    <p>Tidak ada data tagihan untuk periode, jenis tagihan, dan tingkat kelas yang dipilih.</p>
                </div>
            @else
                {{-- Header Aksi Preview & Export --}}
                <div class="recap-action-bar">
                    <div class="recap-info-title">
                        <h3>Rekapitulasi {{ $recapData['period']['bill_name'] }}</h3>
                        <p>Periode: <strong>{{ $recapData['period']['formatted_period'] }}</strong> | Tingkat: <strong>{{ $recapData['period']['grade_label'] }}</strong></p>
                    </div>

                    <div class="report-export-actions">
                        <a
                            href="{{ route('admin.reports.preview', ['month' => $selectedMonth, 'year' => $selectedYear, 'bill_name' => $selectedBillName, 'grade' => $selectedGrade]) }}"
                            target="_blank"
                            class="btn-export btn-export-pdf"
                        >
                            <img
                                src="{{ asset('assets/img/export-red-admin.svg') }}"
                                alt=""
                            >
                            <span>Preview Laporan</span>
                        </a>

                        <a
                            href="{{ route('admin.reports.export-excel', ['month' => $selectedMonth, 'year' => $selectedYear, 'bill_name' => $selectedBillName, 'grade' => $selectedGrade]) }}"
                            class="btn-export btn-export-excel"
                        >
                            <img
                                src="{{ asset('assets/img/export-green-admin.svg') }}"
                                alt=""
                            >
                            <span>Export Excel</span>
                        </a>
                    </div>
                </div>

                {{-- TABEL REKAP PER TINGKAT / GRADE --}}
                @foreach($recapData['grades'] as $gradeSection)
                    <div class="report-table-card recap-grade-card">
                        <div class="recap-grade-header">
                            <h4>REKAP {{ $gradeSection['grade_title'] }}</h4>
                        </div>

                        <div class="report-table-wrapper">
                            <table class="report-table recap-table">
                                <thead>
                                    <tr>
                                        <th style="width: 6%;">No</th>
                                        <th style="width: 26%;">Kelas</th>
                                        <th style="width: 16%; text-align: center;">Lunas</th>
                                        <th style="width: 20%; text-align: right;">Total Pembayaran</th>
                                        <th style="width: 16%; text-align: center;">Belum Lunas</th>
                                        <th style="width: 20%; text-align: right;">Total Tagihan Belum Lunas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeSection['rows'] as $idx => $row)
                                        <tr>
                                            <td class="col-no">{{ $idx + 1 }}</td>
                                            <td><strong>{{ $row['class_name'] }}</strong></td>
                                            <td style="text-align: center;">{{ $row['paid_count'] }} siswa</td>
                                            <td style="text-align: right; font-weight: 500;">Rp {{ number_format($row['paid_total'], 0, ',', '.') }}</td>
                                            <td style="text-align: center;">{{ $row['unpaid_count'] }} siswa</td>
                                            <td style="text-align: right; font-weight: 500;">Rp {{ number_format($row['unpaid_total'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach

                                    {{-- Baris Total Grade --}}
                                    <tr class="recap-grade-total-row">
                                        <td colspan="2" style="text-align: center;">
                                            <strong>TOTAL {{ $gradeSection['grade_title'] }}</strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <strong>{{ $gradeSection['totals']['paid_count'] }} siswa</strong>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong>Rp {{ number_format($gradeSection['totals']['paid_total'], 0, ',', '.') }}</strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <strong>{{ $gradeSection['totals']['unpaid_count'] }} siswa</strong>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong>Rp {{ number_format($gradeSection['totals']['unpaid_total'], 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- GRAND TOTAL KESELURUHAN --}}
                <div class="report-table-card recap-grand-total-card">
                    <div class="recap-grade-header recap-grand-header">
                        <h4>GRAND TOTAL KESELURUHAN</h4>
                    </div>

                    <div class="report-table-wrapper">
                        <table class="report-table recap-table">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Target</th>
                                    <th style="width: 16%; text-align: center;">Lunas</th>
                                    <th style="width: 20%; text-align: right;">Total Pembayaran</th>
                                    <th style="width: 16%; text-align: center;">Belum Lunas</th>
                                    <th style="width: 20%; text-align: right;">Total Tagihan Belum Lunas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="recap-grand-total-row">
                                    <td>
                                        <strong>{{ $recapData['period']['grade_label'] }}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong>{{ $recapData['grand_totals']['paid_count'] }} siswa</strong>
                                    </td>
                                    <td style="text-align: right; color: #00b724;">
                                        <strong>Rp {{ number_format($recapData['grand_totals']['paid_total'], 0, ',', '.') }}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong>{{ $recapData['grand_totals']['unpaid_count'] }} siswa</strong>
                                    </td>
                                    <td style="text-align: right; color: var(--secondarysc-40);">
                                        <strong>Rp {{ number_format($recapData['grand_totals']['unpaid_total'], 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @else
            <div class="report-empty-box">
                <p>Silakan pilih Bulan, Tahun, Jenis Tagihan, dan Tingkat Kelas lalu klik <strong>Tampilkan</strong> untuk melihat rekapitulasi per kelas.</p>
            </div>
        @endif

    @endif

</section>

@push('scripts')
    @vite('resources/js/pages/admin/payment-report.js')
@endpush

@endsection
