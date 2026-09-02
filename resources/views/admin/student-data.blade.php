@extends('layouts.sikolapayapp')

@section('title', 'Data Siswa - SikolaPay')

@section('page-title', 'Data Siswa')

@section('content')

<section class="student-page">

    {{-- Header --}}
    <div class="page-header">

        <div class="page-header-info">
            <h2>Data Siswa</h2>

            <p>
                {{ $totalStudents }} Siswa terdaftar
            </p>
        </div>

        <button
            type="button"
            class="btn-primary"
            id="openAddStudentModal"
        >
            <img
                src="{{ asset('assets/img/Add_round-admin.svg') }}"
                alt=""
            >

            <span>Tambah Siswa</span>
        </button>

    </div>

    {{-- Alert Success --}}
    @if(session('success'))
        <div class="student-alert student-alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Alert Error --}}
    @if($errors->any())
        <div class="student-alert student-alert-error">

            <strong>Data belum dapat disimpan.</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif

    {{-- Card --}}
    <div class="student-card">

        {{-- Toolbar --}}
        <form
            method="GET"
            action="{{ route('admin.students.index') }}"
            class="student-toolbar"
        >

            <div class="search-box">

                <img
                    src="{{ asset('assets/img/search-admin.svg') }}"
                    alt=""
                >

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama, NIS atau NISN..."
                >

            </div>

            <div class="toolbar-actions">

                <select
                    name="class_room_id"
                    class="btn-filter"
                    onchange="this.form.submit()"
                >
                    <option value="">
                        Semua Kelas
                    </option>

                    @foreach($classRooms as $classRoom)

                        <option
                            value="{{ $classRoom->id }}"
                            {{ request('class_room_id') == $classRoom->id ? 'selected' : '' }}
                        >
                            {{ $classRoom->name }}
                        </option>

                    @endforeach
                </select>

                <button
                    type="submit"
                    class="btn-filter"
                >
                    Cari
                </button>

                @if(request()->filled('search') || request()->filled('class_room_id'))

                    <a
                        href="{{ route('admin.students.index') }}"
                        class="btn-filter"
                    >
                        Reset
                    </a>

                @endif

                <button
                    type="button"
                    class="btn-export"
                    id="exportStudentData"
                >
                    <img
                        src="{{ asset('assets/img/export-data-siswa-admin.svg') }}"
                        alt=""
                    >

                    <span>Export</span>
                </button>

            </div>

        </form>

        {{-- Table --}}
        <div class="student-table-wrapper">

            <table class="student-table">

                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Wali Murid</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($students as $student)

                        <tr>

                            <td>

                                <div class="nis">

                                    <span>
                                        {{ $student->nis ?? '-' }}
                                    </span>

                                    <span>
                                        {{ $student->nisn ?? '-' }}
                                    </span>

                                </div>

                            </td>

                            <td>
                                {{ $student->name }}
                            </td>

                            <td>
                                {{ $student->classRoom?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $student->guardian?->name ?? '-' }}
                            </td>

                            <td>

                                @if($student->status === 'active')

                                    <span class="status status-active">
                                        Aktif
                                    </span>

                                @else

                                    <span class="status status-inactive">
                                        NonAktif
                                    </span>

                                @endif

                            </td>

                            <td>

                                <div class="table-actions">

                                    <button
                                        type="button"
                                        class="action-detail"

                                        data-id="{{ $student->id }}"
                                        data-name="{{ $student->name }}"

                                        data-nis="{{ $student->nis ?? '' }}"
                                        data-nisn="{{ $student->nisn ?? '' }}"

                                        data-class="{{ $student->classRoom?->name ?? '' }}"
                                        data-class-id="{{ $student->class_room_id }}"

                                        data-entry-year="{{ $student->entry_year ?? '' }}"
                                        data-gender="{{ $student->gender ?? '' }}"
                                        data-status="{{ $student->status ?? 'active' }}"

                                        data-email="{{ $student->user?->email ?? '' }}"

                                        data-guardian="{{ $student->guardian?->name ?? '' }}"
                                        data-guardian-email="{{ $student->guardian?->user?->email ?? '' }}"
                                        data-phone="{{ $student->guardian?->phone ?? '' }}"
                                    >
                                        Detail
                                    </button>

                                    <button
                                        type="button"
                                        class="action-edit"

                                        data-id="{{ $student->id }}"
                                        data-name="{{ $student->name }}"

                                        data-nis="{{ $student->nis ?? '' }}"
                                        data-nisn="{{ $student->nisn ?? '' }}"

                                        data-class="{{ $student->classRoom?->name ?? '' }}"
                                        data-class-id="{{ $student->class_room_id }}"

                                        data-entry-year="{{ $student->entry_year ?? '' }}"
                                        data-gender="{{ $student->gender ?? '' }}"
                                        data-status="{{ $student->status ?? 'active' }}"

                                        data-email="{{ $student->user?->email ?? '' }}"

                                        data-guardian="{{ $student->guardian?->name ?? '' }}"
                                        data-guardian-email="{{ $student->guardian?->user?->email ?? '' }}"
                                        data-phone="{{ $student->guardian?->phone ?? '' }}"

                                        data-update-url="{{ route('admin.students.update', $student) }}"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="action-delete"

                                        data-id="{{ $student->id }}"
                                        data-name="{{ $student->name }}"
                                        data-nis="{{ $student->nis ?? '-' }}"

                                        data-delete-url="{{ route('admin.students.destroy', $student) }}"
                                    >
                                        Hapus
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                style="text-align: center; padding: 32px;"
                            >
                                Tidak ada data siswa ditemukan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        @if($students->hasPages())

            <div class="pagination">

                @if($students->onFirstPage())

                    <span class="pagination-arrow">

                        <img
                            src="{{ asset('assets/img/left-admin.svg') }}"
                            alt=""
                        >

                    </span>

                @else

                    <a
                        href="{{ $students->previousPageUrl() }}"
                        class="pagination-arrow"
                    >
                        <img
                            src="{{ asset('assets/img/left-admin.svg') }}"
                            alt=""
                        >
                    </a>

                @endif

                @foreach(range(1, $students->lastPage()) as $page)

                    @if($page == $students->currentPage())

                        <span class="page-number active">
                            {{ $page }}
                        </span>

                    @else

                        <a
                            href="{{ $students->url($page) }}"
                            class="page-number"
                        >
                            {{ $page }}
                        </a>

                    @endif

                @endforeach

                @if($students->hasMorePages())

                    <a
                        href="{{ $students->nextPageUrl() }}"
                        class="pagination-arrow"
                    >
                        <img
                            src="{{ asset('assets/img/right-admin.svg') }}"
                            alt=""
                        >
                    </a>

                @else

                    <span class="pagination-arrow">

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


{{-- Modal Tambah Siswa --}}
<div
    class="student-add-overlay {{ $errors->any() ? 'active' : '' }}"
    id="addStudentModal"
>

    <div class="student-add-modal">

        <div class="student-add-header">

            <h2>Tambah Siswa Baru</h2>

            <button
                type="button"
                class="student-add-close"
                id="addStudentClose"
            >
                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >
            </button>

        </div>

        <form
            id="addStudentForm"
            action="{{ route('admin.students.store') }}"
            method="POST"
        >

            @csrf

            <div class="student-add-content">

                {{-- NIS dan NISN --}}
                <div class="student-add-row">

                    <div class="student-add-field">

                        <label>
                            NIS
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <input
                                type="text"
                                name="nis"
                                value="{{ old('nis') }}"
                                placeholder="2026-001"
                                required
                            >

                        </div>

                    </div>

                    <div class="student-add-field">

                        <label>
                            NISN
                        </label>

                        <div class="student-add-input">

                            <input
                                type="text"
                                name="nisn"
                                value="{{ old('nisn') }}"
                                placeholder="0012345678"
                            >

                        </div>

                    </div>

                </div>

                {{-- Nama dan Kelas --}}
                <div class="student-add-row">

                    <div class="student-add-field">

                        <label>
                            Nama Lengkap
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Masukkan nama lengkap"
                                required
                            >

                        </div>

                    </div>

                    <div class="student-add-field">

                        <label>
                            Kelas
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <select
                                name="class_room_id"
                                required
                            >

                                <option value="">
                                    Pilih Kelas
                                </option>

                                @foreach($classRooms as $classRoom)

                                    <option
                                        value="{{ $classRoom->id }}"
                                        {{ old('class_room_id') == $classRoom->id ? 'selected' : '' }}
                                    >
                                        {{ $classRoom->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

                {{-- Tahun Masuk dan Gender --}}
                <div class="student-add-row">

                    <div class="student-add-field">

                        <label>
                            Tahun Masuk
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <select
                                name="entry_year"
                                required
                            >

                                @for($year = now()->year; $year >= now()->year - 10; $year--)

                                    <option
                                        value="{{ $year }}"
                                        {{ old('entry_year', now()->year) == $year ? 'selected' : '' }}
                                    >
                                        {{ $year }}
                                    </option>

                                @endfor

                            </select>

                        </div>

                    </div>

                    <div class="student-add-field">

                        <label>
                            Jenis Kelamin
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <select
                                name="gender"
                                required
                            >

                                <option value="">
                                    Pilih Jenis Kelamin
                                </option>

                                <option
                                    value="L"
                                    {{ old('gender') === 'L' ? 'selected' : '' }}
                                >
                                    Laki-laki
                                </option>

                                <option
                                    value="P"
                                    {{ old('gender') === 'P' ? 'selected' : '' }}
                                >
                                    Perempuan
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                {{-- Email dan Status --}}
                <div class="student-add-row">

                    <div class="student-add-field">

                        <label>
                            Email Siswa
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="siswa@example.com"
                                required
                            >

                        </div>

                    </div>

                    <div class="student-add-field">

                        <label>
                            Status
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <select
                                name="status"
                                required
                            >

                                <option
                                    value="active"
                                    {{ old('status', 'active') === 'active' ? 'selected' : '' }}
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    {{ old('status') === 'inactive' ? 'selected' : '' }}
                                >
                                    NonAktif
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                {{-- Wali dan HP --}}
                <div class="student-add-row">

                    <div class="student-add-field">

                        <label>
                            Nama Wali
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <input
                                type="text"
                                name="guardian_name"
                                value="{{ old('guardian_name') }}"
                                placeholder="Nama orang tua / wali"
                                required
                            >

                        </div>

                    </div>

                    <div class="student-add-field">

                        <label>
                            No. HP Wali
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <input
                                type="text"
                                name="guardian_phone"
                                value="{{ old('guardian_phone') }}"
                                placeholder="081234567890"
                                required
                            >

                        </div>

                    </div>

                </div>

                {{-- Email Wali --}}
                <div class="student-add-row">

                    <div class="student-add-field">

                        <label>
                            Email Wali
                            <span>*</span>
                        </label>

                        <div class="student-add-input">

                            <input
                                type="email"
                                name="guardian_email"
                                value="{{ old('guardian_email') }}"
                                placeholder="wali@example.com"
                                required
                            >

                        </div>

                    </div>

                    <div class="student-add-field empty"></div>

                </div>

            </div>

            <div class="student-add-footer">

                <button
                    type="button"
                    class="student-add-btn student-add-btn-cancel"
                    id="addStudentCancel"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="student-add-btn student-add-btn-save"
                >
                    Tambah Siswa
                </button>

            </div>

        </form>

    </div>

</div>


{{-- Modal Detail Siswa --}}
<div
    class="student-detail-overlay"
    id="studentDetailModal"
>

    <div class="student-detail-modal">

        <div class="student-detail-header">

            <h2>Detail Siswa</h2>

            <button
                type="button"
                class="student-detail-close"
                id="studentDetailClose"
            >
                ×
            </button>

        </div>

        <div class="student-detail-profile">

            <div
                class="student-avatar"
                id="detailStudentAvatar"
            >
                -
            </div>

            <div
                class="student-profile-name"
                id="detailStudentName"
            >
                -
            </div>

            <div
                class="student-profile-class"
                id="detailStudentClass"
            >
                -
            </div>

        </div>

        <div class="student-detail-data">

            <div class="student-detail-row">
                <span class="student-detail-label">
                    NIS
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentNis"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    NISN
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentNisn"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Nama Lengkap
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentFullName"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Jenis Kelamin
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentGender"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Status
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentStatus"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Kelas
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentClassValue"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Tahun Masuk
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentEntryYear"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Email Siswa
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentEmail"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Nama Wali
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentGuardian"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    Email Wali
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentGuardianEmail"
                >
                    -
                </span>
            </div>

            <div class="student-detail-row">
                <span class="student-detail-label">
                    No. HP Wali
                </span>

                <span
                    class="student-detail-value"
                    id="detailStudentPhone"
                >
                    -
                </span>
            </div>

        </div>

        <div class="student-detail-footer">

            <button
                type="button"
                class="student-detail-btn student-detail-btn-close"
                id="studentDetailBtnClose"
            >
                Tutup
            </button>

            <button
                type="button"
                class="student-detail-btn student-detail-btn-edit"
                id="studentDetailBtnEdit"
            >
                Edit Data
            </button>

        </div>

    </div>

</div>


{{-- Modal Edit Siswa --}}
<div
    class="student-edit-overlay"
    id="studentEditModal"
>

    <div class="student-edit-modal">

        <div class="student-edit-header">

            <h2>Edit Data Siswa</h2>

            <button
                type="button"
                class="student-edit-close"
                id="studentEditClose"
            >
                ×
            </button>

        </div>

        <form
            id="editStudentForm"
            method="POST"
        >

            @csrf
            @method('PUT')

            <input
                type="hidden"
                id="editStudentId"
                name="student_id"
            >

            <div class="student-edit-content">

                {{-- NIS dan NISN --}}
                <div class="student-edit-row">

                    <div class="student-edit-field">

                        <label>
                            NIS
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="nis"
                            id="editStudentNis"
                            required
                        >

                    </div>

                    <div class="student-edit-field">

                        <label>
                            NISN
                        </label>

                        <input
                            type="text"
                            name="nisn"
                            id="editStudentNisn"
                        >

                    </div>

                </div>

                {{-- Nama --}}
                <div class="student-edit-field full">

                    <label>
                        Nama Lengkap
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="editStudentName"
                        required
                    >

                </div>

                {{-- Kelas dan Tahun --}}
                <div class="student-edit-row">

                    <div class="student-edit-field">

                        <label>
                            Kelas
                            <span>*</span>
                        </label>

                        <select
                            name="class_room_id"
                            id="editStudentClass"
                            class="student-edit-select"
                            required
                        >

                            @foreach($classRooms as $classRoom)

                                <option value="{{ $classRoom->id }}">
                                    {{ $classRoom->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="student-edit-field">

                        <label>
                            Tahun Masuk
                            <span>*</span>
                        </label>

                        <select
                            name="entry_year"
                            id="editStudentEntryYear"
                            class="student-edit-select"
                            required
                        >

                            @for($year = now()->year; $year >= now()->year - 10; $year--)

                                <option value="{{ $year }}">
                                    {{ $year }}
                                </option>

                            @endfor

                        </select>

                    </div>

                </div>

                {{-- Gender dan Status --}}
                <div class="student-edit-row">

                    <div class="student-edit-field">

                        <label>
                            Jenis Kelamin
                            <span>*</span>
                        </label>

                        <select
                            name="gender"
                            id="editStudentGender"
                            class="student-edit-select"
                            required
                        >
                            <option value="L">
                                Laki-laki
                            </option>

                            <option value="P">
                                Perempuan
                            </option>
                        </select>

                    </div>

                    <div class="student-edit-field">

                        <label>
                            Status
                            <span>*</span>
                        </label>

                        <select
                            name="status"
                            id="editStudentStatus"
                            class="student-edit-select"
                            required
                        >
                            <option value="active">
                                Aktif
                            </option>

                            <option value="inactive">
                                NonAktif
                            </option>
                        </select>

                    </div>

                </div>

                {{-- Email Siswa --}}
                <div class="student-edit-field full">

                    <label>
                        Email Siswa
                        <span>*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="editStudentEmail"
                        required
                    >

                </div>

                {{-- Wali --}}
                <div class="student-edit-row">

                    <div class="student-edit-field">

                        <label>
                            Nama Wali
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="guardian_name"
                            id="editStudentGuardian"
                            required
                        >

                    </div>

                    <div class="student-edit-field">

                        <label>
                            No. HP Wali
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="guardian_phone"
                            id="editStudentPhone"
                            required
                        >

                    </div>

                </div>

                {{-- Email Wali --}}
                <div class="student-edit-field full">

                    <label>
                        Email Wali
                        <span>*</span>
                    </label>

                    <input
                        type="email"
                        name="guardian_email"
                        id="editStudentGuardianEmail"
                        required
                    >

                </div>

            </div>

            <div class="student-edit-footer">

                <button
                    type="button"
                    class="student-edit-btn student-edit-btn-cancel"
                    id="studentEditCancel"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="student-edit-btn student-edit-btn-save"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>


{{-- Modal Hapus Siswa --}}
<div
    class="student-delete-overlay"
    id="studentDeleteModal"
>

    <div class="student-delete-modal">

        <div class="student-delete-header">

            <h2>Hapus Siswa</h2>

            <button
                type="button"
                class="student-delete-close"
                id="studentDeleteClose"
            >
                ×
            </button>

        </div>

        <div class="student-delete-content">

            <div class="student-delete-warning">

                <img
                    src="{{ asset('assets/img/caution-admin.svg') }}"
                    alt=""
                >

                <p>
                    Tindakan ini tidak dapat dibatalkan!
                </p>

            </div>

            <p class="student-delete-message">

                Apakah Anda yakin ingin menghapus data siswa

                <strong id="deleteStudentIdentity">
                    -
                </strong>

                ?

            </p>

        </div>

        <div class="student-delete-footer">

            <button
                type="button"
                class="student-delete-btn student-delete-btn-cancel"
                id="studentDeleteCancel"
            >
                Batal
            </button>

            <form
                id="deleteStudentForm"
                method="POST"
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="student-delete-btn student-delete-btn-confirm"
                >
                    Hapus Siswa
                </button>

            </form>

        </div>

    </div>

</div>

    @push('scripts')

        @vite('resources/js/pages/admin/student-data.js')

    @endpush


@endsection