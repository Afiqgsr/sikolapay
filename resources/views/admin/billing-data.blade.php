@extends('layouts.sikolapayapp')

@section('title', 'Data Tagihan - SikolaPay')

@section('page-title', 'Data Tagihan')

@section('content')

<section class="billing-page">

    {{-- Header --}}
    <div class="billing-page-header">

        <div class="billing-page-header-info">

            <h2>Data Tagihan</h2>

            <p>
                Kelola tagihan pembayaran seluruh siswa
            </p>

        </div>

        <button
            type="button"
            class="billing-btn-primary"
            id="openAddBillingModal"
        >
            <img
                src="{{ asset('assets/img/Add_round-admin.svg') }}"
                alt=""
            >

            <span>Tambah Tagihan</span>
        </button>

    </div>

    {{-- Alert --}}
    @if(session('success'))

        <div class="billing-alert billing-alert-success">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div class="billing-alert billing-alert-error">
            {{ session('error') }}
        </div>

    @endif

    @if($errors->any())

        <div class="billing-alert billing-alert-error">

            <strong>Data belum dapat disimpan.</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif

    {{-- Card --}}
    <div class="billing-card">

        {{-- Filter --}}
        <form
            method="GET"
            action="{{ route('admin.bills.index') }}"
            class="billing-toolbar"
        >

            <div class="billing-filters">

                <select
                    name="target_type"
                    class="billing-filter-select"
                    onchange="this.form.submit()"
                >

                    <option value="">
                        Semua Target
                    </option>

                    <option
                        value="class"
                        {{ request('target_type') === 'class' ? 'selected' : '' }}
                    >
                        Per Kelas
                    </option>

                    <option
                        value="cohort"
                        {{ request('target_type') === 'cohort' ? 'selected' : '' }}
                    >
                        Per Angkatan
                    </option>

                    <option
                        value="school"
                        {{ request('target_type') === 'school' ? 'selected' : '' }}
                    >
                        Seluruh Sekolah
                    </option>

                    <option
                        value="student"
                        {{ request('target_type') === 'student' ? 'selected' : '' }}
                    >
                        Siswa Tertentu
                    </option>

                </select>

                <select
                    name="semester"
                    class="billing-filter-select"
                    onchange="this.form.submit()"
                >

                    <option value="">
                        Semua Semester
                    </option>

                    @foreach($semesters as $semester)

                        <option
                            value="{{ $semester }}"
                            {{ request('semester') === $semester ? 'selected' : '' }}
                        >
                            {{ $semester }}
                        </option>

                    @endforeach

                </select>

                <div class="billing-search-box">

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari jenis tagihan..."
                    >

                    <img
                        src="{{ asset('assets/img/search-admin.svg') }}"
                        alt=""
                    >

                </div>

                <button
                    type="submit"
                    class="billing-search-button"
                >
                    Cari
                </button>

                @if(
                    request()->filled('search')
                    || request()->filled('target_type')
                    || request()->filled('semester')
                )

                    <a
                        href="{{ route('admin.bills.index') }}"
                        class="billing-reset-button"
                    >
                        Reset
                    </a>

                @endif

            </div>

        </form>

        {{-- Table --}}
        <div class="billing-table-wrapper">

            <table class="billing-table billing-batch-table">

                <thead>

                    <tr>
                        <th>No</th>
                        <th>Jenis Tagihan</th>
                        <th>Target</th>
                        <th>Semester</th>
                        <th>Nominal</th>
                        <th>Penerima</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($batches as $batch)

                        @php
                            $paidCount = 0;
                            $pendingCount = 0;
                            $unpaidCount = 0;
                            $overdueCount = 0;

                            foreach ($batch->bills as $childBill) {

                                if ($childBill->status === 'paid') {

                                    $paidCount++;

                                } elseif (
                                    $childBill->latestPayment
                                    && $childBill->latestPayment->status === 'pending'
                                ) {

                                    $pendingCount++;

                                } elseif ($childBill->status === 'overdue') {

                                    $overdueCount++;

                                } else {

                                    $unpaidCount++;

                                }

                            }

                            if ($batch->target_type === 'class') {

                                $targetLabel = $classRooms
                                    ->firstWhere('id', $batch->target_value)
                                    ?->name ?? 'Kelas';

                            } elseif ($batch->target_type === 'cohort') {

                                $targetLabel =
                                    'Angkatan ' . $batch->target_value;

                            } elseif ($batch->target_type === 'school') {

                                $targetLabel =
                                    'Seluruh Sekolah';

                            } elseif ($batch->target_type === 'student') {

                                $targetLabel =
                                    $batch->bills
                                        ->first()
                                        ?->student
                                        ?->name ?? 'Siswa';

                            } else {

                                $targetLabel = '-';

                            }
                        @endphp

                        <tr>

                            <td>
                                {{ $batches->firstItem() + $loop->index }}
                            </td>

                            <td>
                                {{ $batch->name }}
                            </td>

                            <td>
                                {{ $targetLabel }}
                            </td>

                            <td>
                                {{ $batch->semester ?? '-' }}
                            </td>

                            <td>

                                <strong>
                                    Rp {{ number_format(
                                        $batch->amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>

                            </td>

                            <td>
                                {{ $batch->bills_count }} siswa
                            </td>

                            <td>

                                {{ $batch->due_date
                                    ? $batch->due_date->translatedFormat('d M Y')
                                    : '-'
                                }}

                            </td>

                            <td>

                                <div class="billing-summary-status">

                                    @if($paidCount > 0)

                                        <span class="billing-status billing-status-paid">
                                            {{ $paidCount }} Lunas
                                        </span>

                                    @endif

                                    @if($pendingCount > 0)

                                        <span class="billing-status billing-status-pending">
                                            {{ $pendingCount }} Menunggu
                                        </span>

                                    @endif

                                    @if($unpaidCount > 0)

                                        <span class="billing-status billing-status-unpaid">
                                            {{ $unpaidCount }} Belum
                                        </span>

                                    @endif

                                    @if($overdueCount > 0)

                                        <span class="billing-status billing-status-overdue">
                                            {{ $overdueCount }} Terlambat
                                        </span>

                                    @endif

                                    @if(
                                        $paidCount === 0
                                        && $pendingCount === 0
                                        && $unpaidCount === 0
                                        && $overdueCount === 0
                                    )

                                        <span>-</span>

                                    @endif

                                </div>

                            </td>

                            <td>

                                <div class="billing-table-actions">

                                    <button
                                        type="button"
                                        class="billing-action-detail"
                                        data-id="{{ $batch->id }}"
                                    >
                                        Detail
                                    </button>

                                    <button
                                        type="button"
                                        class="billing-action-edit"

                                        data-id="{{ $batch->id }}"

                                        data-name="{{ $batch->name }}"

                                        data-description="{{ $batch->description ?? '' }}"

                                        data-semester="{{ $batch->semester ?? '' }}"

                                        data-amount="{{ (float) $batch->amount }}"

                                        data-due-date="{{ $batch->due_date?->format('Y-m-d') ?? '' }}"

                                        data-target-type="{{ $batch->target_type }}"

                                        data-target-value="{{ $batch->target_value ?? '' }}"

                                        data-update-url="{{ route('admin.bills.update', $batch) }}"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="billing-action-delete"

                                        data-name="{{ $batch->name }}"

                                        data-target="{{ $targetLabel }}"

                                        data-delete-url="{{ route('admin.bills.destroy', $batch) }}"
                                    >
                                        Hapus
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="billing-empty"
                            >
                                Belum ada data tagihan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        @if($batches->hasPages())

            <div class="billing-pagination">

                @if($batches->onFirstPage())

                    <span class="billing-pagination-arrow disabled">

                        <img
                            src="{{ asset('assets/img/left-admin.svg') }}"
                            alt=""
                        >

                    </span>

                @else

                    <a
                        href="{{ $batches->previousPageUrl() }}"
                        class="billing-pagination-arrow"
                    >
                        <img
                            src="{{ asset('assets/img/left-admin.svg') }}"
                            alt=""
                        >
                    </a>

                @endif

                @foreach(range(1, $batches->lastPage()) as $page)

                    @if($page === $batches->currentPage())

                        <span class="billing-page-number active">
                            {{ $page }}
                        </span>

                    @else

                        <a
                            href="{{ $batches->url($page) }}"
                            class="billing-page-number"
                        >
                            {{ $page }}
                        </a>

                    @endif

                @endforeach

                @if($batches->hasMorePages())

                    <a
                        href="{{ $batches->nextPageUrl() }}"
                        class="billing-pagination-arrow"
                    >
                        <img
                            src="{{ asset('assets/img/right-admin.svg') }}"
                            alt=""
                        >
                    </a>

                @else

                    <span class="billing-pagination-arrow disabled">

                        <img
                            src="{{ asset('assets/img/right-admin.svg') }}"
                            alt=""
                        >

                    </span>

                @endif

            </div>

        @endif

    </div>

</section>


{{-- Data Target untuk JavaScript --}}
@php
    $billingTargetData = [
        'students' => $students
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'class' => $student->classRoom?->name,
                ];
            })
            ->values(),

        'classes' => $classRooms
            ->map(function ($classRoom) {
                return [
                    'id' => $classRoom->id,
                    'name' => $classRoom->name,
                ];
            })
            ->values(),

        'cohorts' => $cohorts->values(),
    ];
@endphp

<script
    type="application/json"
    id="billingTargetData"
>
{!! json_encode(
    $billingTargetData,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
) !!}
</script>


{{-- Data Detail untuk JavaScript --}}
@php
    $batchDetailData = [];

    foreach ($batches as $batch) {

        $recipients = [];

        foreach ($batch->bills as $childBill) {

            $status = 'unpaid';

            if ($childBill->status === 'paid') {

                $status = 'paid';

            } elseif (
                $childBill->latestPayment
                && $childBill->latestPayment->status === 'pending'
            ) {

                $status = 'pending';

            } elseif ($childBill->status === 'overdue') {

                $status = 'overdue';

            }

            $recipients[] = [
                'name' =>
                    $childBill->student?->name ?? '-',

                'class' =>
                    $childBill->student?->classRoom?->name ?? '-',

                'status' =>
                    $status,
            ];
        }

        if ($batch->target_type === 'class') {

            $target = $classRooms
                ->firstWhere('id', $batch->target_value)
                ?->name ?? '-';

        } elseif ($batch->target_type === 'cohort') {

            $target =
                'Angkatan ' . $batch->target_value;

        } elseif ($batch->target_type === 'school') {

            $target =
                'Seluruh Sekolah';

        } elseif ($batch->target_type === 'student') {

            $target =
                $batch->bills
                    ->first()
                    ?->student
                    ?->name ?? '-';

        } else {

            $target = '-';

        }

        $batchDetailData[$batch->id] = [
            'id' =>
                $batch->id,

            'name' =>
                $batch->name,

            'target' =>
                $target,

            'semester' =>
                $batch->semester,

            'amount' =>
                (float) $batch->amount,

            'due_date' =>
                $batch->due_date
                    ?->translatedFormat('d F Y'),

            'description' =>
                $batch->description,

            'recipients' =>
                $recipients,
        ];
    }
@endphp

<script
    type="application/json"
    id="billingDetailData"
>
{!! json_encode(
    $batchDetailData,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
) !!}
</script>


{{-- Modal Tambah --}}
<div
    class="billing-add-overlay {{ $errors->any() ? 'active' : '' }}"
    id="addBillingModal"
>

    <div class="billing-add-modal">

        <div class="billing-add-header">

            <h2>
                Tambah Tagihan
            </h2>

            <button
                type="button"
                class="billing-add-close"
                id="addBillingClose"
            >

                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >

            </button>

        </div>

        <form
            action="{{ route('admin.bills.store') }}"
            method="POST"
            id="addBillingForm"
        >

            @csrf

            <div class="billing-add-content">

                {{-- Target --}}
                <div class="billing-add-row">

                    <div class="billing-add-field">

                        <label>
                            Target Tagihan
                            <span>*</span>
                        </label>

                        <select
                            name="target_type"
                            id="billingTargetType"
                            class="billing-add-input"
                            required
                        >

                            <option value="">
                                -- Pilih Target --
                            </option>

                            <option
                                value="class"
                                {{ old('target_type') === 'class' ? 'selected' : '' }}
                            >
                                Per Kelas
                            </option>

                            <option
                                value="cohort"
                                {{ old('target_type') === 'cohort' ? 'selected' : '' }}
                            >
                                Per Angkatan
                            </option>

                            <option
                                value="school"
                                {{ old('target_type') === 'school' ? 'selected' : '' }}
                            >
                                Seluruh Sekolah
                            </option>

                            <option
                                value="student"
                                {{ old('target_type') === 'student' ? 'selected' : '' }}
                            >
                                Siswa Tertentu
                            </option>

                        </select>

                    </div>

                    <div
                        class="billing-add-field"
                        id="billingTargetValueField"
                    >

                        <label>
                            Pilih Target
                            <span>*</span>
                        </label>

                        <select
                            name="target_value"
                            id="billingTargetValue"
                            class="billing-add-input"
                            data-old-value="{{ old('target_value') }}"
                        >

                            <option value="">
                                -- Pilih Target --
                            </option>

                        </select>

                    </div>

                </div>

                {{-- Jenis Tagihan dan Semester --}}
                <div class="billing-add-row">

                    <div class="billing-add-field">

                        <label>
                            Jenis Tagihan
                            <span>*</span>
                        </label>

                        <select
                            name="name"
                            class="billing-add-input"
                            required
                        >

                            <option value="">
                                -- Pilih Jenis Tagihan --
                            </option>

                            <option
                                value="SPP Bulanan"
                                {{ old('name') === 'SPP Bulanan' ? 'selected' : '' }}
                            >
                                SPP Bulanan
                            </option>

                            <option
                                value="Uang Ujian"
                                {{ old('name') === 'Uang Ujian' ? 'selected' : '' }}
                            >
                                Uang Ujian
                            </option>

                            <option
                                value="Uang Gedung"
                                {{ old('name') === 'Uang Gedung' ? 'selected' : '' }}
                            >
                                Uang Gedung
                            </option>

                            <option
                                value="Kegiatan"
                                {{ old('name') === 'Kegiatan' ? 'selected' : '' }}
                            >
                                Kegiatan
                            </option>

                            <option
                                value="Seragam"
                                {{ old('name') === 'Seragam' ? 'selected' : '' }}
                            >
                                Seragam
                            </option>

                        </select>

                    </div>

                    <div class="billing-add-field">

                        <label>
                            Semester
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="semester"
                            class="billing-add-input"
                            value="{{ old('semester') }}"
                            placeholder="Contoh: Ganjil 2026/2027"
                            required
                        >

                    </div>

                </div>

                {{-- Nominal dan Jatuh Tempo --}}
                <div class="billing-add-row">

                    <div class="billing-add-field">

                        <label>
                            Nominal
                            <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="amount"
                            class="billing-add-input"
                            value="{{ old('amount') }}"
                            min="1"
                            placeholder="350000"
                            required
                        >

                    </div>

                    <div class="billing-add-field">

                        <label>
                            Jatuh Tempo
                        </label>

                        <input
                            type="date"
                            name="due_date"
                            class="billing-add-input"
                            value="{{ old('due_date') }}"
                        >

                    </div>

                </div>

                {{-- Deskripsi --}}
                <div class="billing-add-field billing-add-field-full">

                    <label>
                        Deskripsi
                    </label>

                    <textarea
                        name="description"
                        class="billing-add-textarea"
                        placeholder="Deskripsi tagihan..."
                    >{{ old('description') }}</textarea>

                </div>

            </div>

            <div class="billing-add-footer">

                <button
                    type="button"
                    class="billing-add-btn billing-add-btn-cancel"
                    id="addBillingCancel"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="billing-add-btn billing-add-btn-save"
                >
                    Tambah Tagihan
                </button>

            </div>

        </form>

    </div>

</div>


{{-- Modal Detail --}}
<div
    class="billing-modal-overlay"
    id="billingDetailModal"
>

    <div class="billing-modal">

        <div class="billing-modal-header">

            <h2>
                Detail Tagihan
            </h2>

            <button
                type="button"
                class="billing-modal-close"
                id="billingDetailClose"
            >

                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >

            </button>

        </div>

        <div class="billing-modal-content">

            <div class="billing-detail-list">

                <div class="billing-detail-item">

                    <span class="billing-detail-label">
                        Jenis Tagihan
                    </span>

                    <span
                        class="billing-detail-value"
                        id="detailBillingName"
                    >
                        -
                    </span>

                </div>

                <div class="billing-detail-item">

                    <span class="billing-detail-label">
                        Target
                    </span>

                    <span
                        class="billing-detail-value"
                        id="detailBillingTarget"
                    >
                        -
                    </span>

                </div>

                <div class="billing-detail-item">

                    <span class="billing-detail-label">
                        Semester
                    </span>

                    <span
                        class="billing-detail-value"
                        id="detailBillingSemester"
                    >
                        -
                    </span>

                </div>

                <div class="billing-detail-item">

                    <span class="billing-detail-label">
                        Nominal
                    </span>

                    <span
                        class="billing-detail-value"
                        id="detailBillingAmount"
                    >
                        -
                    </span>

                </div>

                <div class="billing-detail-item">

                    <span class="billing-detail-label">
                        Jatuh Tempo
                    </span>

                    <span
                        class="billing-detail-value"
                        id="detailBillingDueDate"
                    >
                        -
                    </span>

                </div>

                <div class="billing-detail-item">

                    <span class="billing-detail-label">
                        Deskripsi
                    </span>

                    <span
                        class="billing-detail-value"
                        id="detailBillingDescription"
                    >
                        -
                    </span>

                </div>

            </div>

            {{-- Daftar Penerima --}}
            <div class="billing-recipient-section">

                <h3>
                    Daftar Penerima
                </h3>

                <div class="billing-recipient-table-wrapper">

                    <table class="billing-recipient-table">

                        <thead>

                            <tr>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody id="billingRecipientBody">
                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <div class="billing-modal-footer">

            <button
                type="button"
                class="billing-btn billing-btn-close"
                id="billingDetailBtnClose"
            >
                Tutup
            </button>

            <button
                type="button"
                class="billing-btn billing-btn-edit"
                id="billingDetailBtnEdit"
            >
                Edit Tagihan
            </button>

        </div>

    </div>

</div>


{{-- Modal Edit --}}
<div
    class="billing-edit-overlay"
    id="editBillingModal"
>

    <div class="billing-edit-modal">

        <div class="billing-edit-header">

            <h2>
                Edit Tagihan
            </h2>

            <button
                type="button"
                class="billing-edit-close"
                id="editBillingClose"
            >

                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >

            </button>

        </div>

        <form
            method="POST"
            id="editBillingForm"
        >

            @csrf
            @method('PUT')

            <div class="billing-edit-content">

                {{-- Target --}}
                <div class="billing-edit-row">

                    <div class="billing-edit-field">

                        <label>
                            Target Tagihan
                            <span>*</span>
                        </label>

                        <select
                            name="target_type"
                            id="editBillingTargetType"
                            class="billing-edit-input"
                            required
                        >

                            <option value="class">
                                Per Kelas
                            </option>

                            <option value="cohort">
                                Per Angkatan
                            </option>

                            <option value="school">
                                Seluruh Sekolah
                            </option>

                            <option value="student">
                                Siswa Tertentu
                            </option>

                        </select>

                    </div>

                    <div
                        class="billing-edit-field"
                        id="editBillingTargetValueField"
                    >

                        <label>
                            Pilih Target
                            <span>*</span>
                        </label>

                        <select
                            name="target_value"
                            id="editBillingTargetValue"
                            class="billing-edit-input"
                        >
                        </select>

                    </div>

                </div>

                {{-- Jenis Tagihan dan Semester --}}
                <div class="billing-edit-row">

                    <div class="billing-edit-field">

                        <label>
                            Jenis Tagihan
                            <span>*</span>
                        </label>

                        <select
                            name="name"
                            id="editBillingName"
                            class="billing-edit-input"
                            required
                        >

                            <option value="SPP Bulanan">
                                SPP Bulanan
                            </option>

                            <option value="Uang Ujian">
                                Uang Ujian
                            </option>

                            <option value="Uang Gedung">
                                Uang Gedung
                            </option>

                            <option value="Kegiatan">
                                Kegiatan
                            </option>

                            <option value="Seragam">
                                Seragam
                            </option>

                        </select>

                    </div>

                    <div class="billing-edit-field">

                        <label>
                            Semester
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="semester"
                            id="editBillingSemester"
                            class="billing-edit-input"
                            required
                        >

                    </div>

                </div>

                {{-- Nominal dan Jatuh Tempo --}}
                <div class="billing-edit-row">

                    <div class="billing-edit-field">

                        <label>
                            Nominal
                            <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="amount"
                            id="editBillingAmount"
                            class="billing-edit-input"
                            min="1"
                            required
                        >

                    </div>

                    <div class="billing-edit-field">

                        <label>
                            Jatuh Tempo
                        </label>

                        <input
                            type="date"
                            name="due_date"
                            id="editBillingDueDate"
                            class="billing-edit-input"
                        >

                    </div>

                </div>

                {{-- Deskripsi --}}
                <div class="billing-edit-field billing-edit-field-full">

                    <label>
                        Deskripsi
                    </label>

                    <textarea
                        name="description"
                        id="editBillingDescription"
                        class="billing-edit-textarea"
                    ></textarea>

                </div>

            </div>

            <div class="billing-edit-footer">

                <button
                    type="button"
                    class="billing-edit-btn billing-edit-btn-cancel"
                    id="editBillingCancel"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="billing-edit-btn billing-edit-btn-save"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>


{{-- Modal Hapus --}}
<div
    class="billing-delete-overlay"
    id="deleteBillingModal"
>

    <div class="billing-delete-modal">

        <div class="billing-delete-header">

            <h2>
                Hapus Tagihan
            </h2>

            <button
                type="button"
                class="billing-delete-close"
                id="deleteBillingClose"
            >

                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >

            </button>

        </div>

        <div class="billing-delete-content">

            <div class="billing-delete-warning">

                <img
                    src="{{ asset('assets/img/caution-admin.svg') }}"
                    alt=""
                >

                <p>
                    Tindakan ini tidak dapat dibatalkan!
                </p>

            </div>

            <p class="billing-delete-message">

                Hapus tagihan

                <strong id="deleteBillingName">
                    -
                </strong>

                untuk

                <strong id="deleteBillingTarget">
                    -
                </strong>

                ?

            </p>

        </div>

        <div class="billing-delete-footer">

            <button
                type="button"
                class="billing-delete-btn billing-delete-btn-cancel"
                id="deleteBillingCancel"
            >
                Batal
            </button>

            <form
                method="POST"
                id="deleteBillingForm"
            >

                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="billing-delete-btn billing-delete-btn-confirm"
                >
                    Hapus Tagihan
                </button>

            </form>

        </div>

    </div>

</div>


@push('scripts')

    @vite('resources/js/pages/admin/billing-data.js')

@endpush

@endsection