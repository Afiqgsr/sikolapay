@extends('layouts.sikolapayapp')

@section('title', 'Edit Profil - SikolaPay')
@section('page-title', 'Profil')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/student/profile.css') }}">
@endpush

@section('content')

<section class="profile-page">

    <div class="profile-header">

        <div>
            <h2>Edit Profil</h2>
            <p>Perbarui informasi akun dan keamanan Anda.</p>
        </div>

    </div>

    <form
        action="{{ route('student.profile.update') }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div class="profile-layout">

            <div class="profile-card">

                <div class="profile-card-header">
                    <h3>Informasi Siswa</h3>
                </div>

                <div class="profile-info-grid">

                    <div class="profile-info-item">
                        <label>Nama</label>

                        <input
                            type="text"
                            value="{{ $student->name }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label>NIS</label>

                        <input
                            type="text"
                            value="{{ $student->nis }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label>NISN</label>

                        <input
                            type="text"
                            value="{{ $student->nisn }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label for="email">Email</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $student->user?->email) }}"
                            required
                        >

                        @error('email')
                            <small class="form-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="profile-info-item">
                        <label>Jenis Kelamin</label>

                        <input
                            type="text"
                            value="{{ $student->gender ?? '-' }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label>Tempat Lahir</label>

                        <input
                            type="text"
                            value="{{ $student->birth_place ?? '-' }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label>Tanggal Lahir</label>

                        <input
                            type="text"
                            value="{{
                                $student->birth_date
                                    ? \Carbon\Carbon::parse($student->birth_date)
                                        ->translatedFormat('d F Y')
                                    : '-'
                            }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label>Kelas</label>

                        <input
                            type="text"
                            value="{{ $student->classRoom?->name ?? '-' }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item">
                        <label>Tahun Ajaran</label>

                        <input
                            type="text"
                            value="{{ $student->classRoom?->academicYear?->name ?? '-' }}"
                            readonly
                        >
                    </div>

                    <div class="profile-info-item profile-info-full">
                        <label for="address">Alamat</label>

                        <textarea
                            id="address"
                            name="address"
                        >{{ old('address', $student->address) }}</textarea>

                        @error('address')
                            <small class="form-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="profile-edit-right">

                <div class="profile-card">

                    <div class="profile-card-header">
                        <h3>Orang Tua / Wali</h3>
                    </div>

                    @if($student->guardian)

                        <div class="profile-info-list">

                            <div class="profile-info-item">
                                <label>Nama</label>

                                <input
                                    type="text"
                                    value="{{ $student->guardian->name }}"
                                    readonly
                                >
                            </div>

                            <div class="profile-info-item">
                                <label for="guardian_phone">
                                    No. Telepon
                                </label>

                                <input
                                    type="text"
                                    id="guardian_phone"
                                    name="guardian_phone"
                                    value="{{ old('guardian_phone', $student->guardian->phone) }}"
                                >

                                @error('guardian_phone')
                                    <small class="form-error">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                            <div class="profile-info-item">
                                <label for="guardian_email">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    id="guardian_email"
                                    name="guardian_email"
                                    value="{{ old('guardian_email', $student->guardian->email) }}"
                                >

                                @error('guardian_email')
                                    <small class="form-error">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                            <div class="profile-info-item">
                                <label>Hubungan</label>

                                <input
                                    type="text"
                                    value="{{ $student->guardian->relationship ?? '-' }}"
                                    readonly
                                >
                            </div>

                        </div>

                    @endif

                </div>

                <div class="profile-card profile-password-card">

                    <div class="profile-card-header">
                        <h3>Ganti Password</h3>

                        <p>
                            Kosongkan jika tidak ingin mengganti password.
                        </p>
                    </div>

                    <div class="profile-info-list">

                        <div class="profile-info-item">
                            <label for="current_password">
                                Password Saat Ini
                            </label>

                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                            >

                            @error('current_password')
                                <small class="form-error">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="profile-info-item">
                            <label for="password">
                                Password Baru
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                            >

                            @error('password')
                                <small class="form-error">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="profile-info-item">
                            <label for="password_confirmation">
                                Konfirmasi Password Baru
                            </label>

                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                autocomplete="new-password"
                            >
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="profile-edit-actions">

            <a
                href="{{ route('student.profile') }}"
                class="profile-cancel-button"
            >
                Batal
            </a>

            <button
                type="submit"
                class="profile-save-button"
            >
                Simpan Perubahan
            </button>

        </div>

    </form>

</section>

@endsection