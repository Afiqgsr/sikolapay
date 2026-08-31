@extends('layouts.sikolapayapp')

@section('title', 'Profil - SikolaPay')
@section('page-title', 'Profil')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/student/profile.css') }}">
@endpush

@section('content')

<section class="profile-page">

    @if(session('success'))
        <div class="profile-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="profile-header">

        <div>
            <h2>Profil Akun</h2>
            <p>Informasi data siswa dan orang tua / wali.</p>
        </div>

        <a
            href="{{ route('student.profile.edit') }}"
            class="profile-edit-button"
        >
            Edit Profil
        </a>

    </div>

    <div class="profile-layout">

        <div class="profile-card profile-main-card">

            <div class="profile-identity">

                <div class="profile-avatar">
                    {{ strtoupper(substr($student->name, 0, 2)) }}
                </div>

                <div class="profile-identity-info">

                    <h3>
                        {{ $student->name }}
                    </h3>

                    <p>
                        {{ $student->nis ?? '-' }}
                    </p>

                    <span class="profile-status">
                        Siswa Aktif
                    </span>

                </div>

            </div>

            <div class="profile-divider"></div>

            <div class="profile-info-grid">

                <div class="profile-info-item">
                    <span>NIS</span>
                    <strong>
                        {{ $student->nis ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>NISN</span>
                    <strong>
                        {{ $student->nisn ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>Email</span>
                    <strong>
                        {{ $student->user?->email ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>Jenis Kelamin</span>
                    <strong>
                        {{ $student->gender ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>Tempat Lahir</span>
                    <strong>
                        {{ $student->birth_place ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>Tanggal Lahir</span>
                    <strong>
                        {{
                            $student->birth_date
                                ? \Carbon\Carbon::parse($student->birth_date)
                                    ->translatedFormat('d F Y')
                                : '-'
                        }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>Kelas</span>
                    <strong>
                        {{ $student->classRoom?->name ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item">
                    <span>Tahun Ajaran</span>
                    <strong>
                        {{ $student->classRoom?->academicYear?->name ?? '-' }}
                    </strong>
                </div>

                <div class="profile-info-item profile-info-full">
                    <span>Alamat</span>
                    <strong>
                        {{ $student->address ?? '-' }}
                    </strong>
                </div>

            </div>

        </div>

        <div class="profile-card">

            <div class="profile-card-header">
                <h3>Orang Tua / Wali</h3>
            </div>

            @if($student->guardian)

                <div class="profile-info-list">

                    <div class="profile-info-row">
                        <span>Nama</span>
                        <strong>
                            {{ $student->guardian->name ?? '-' }}
                        </strong>
                    </div>

                    <div class="profile-info-row">
                        <span>No. Telepon</span>
                        <strong>
                            {{ $student->guardian->phone ?? '-' }}
                        </strong>
                    </div>

                    <div class="profile-info-row">
                        <span>Email</span>
                        <strong>
                            {{ $student->guardian->email ?? '-' }}
                        </strong>
                    </div>

                    <div class="profile-info-row">
                        <span>Hubungan</span>
                        <strong>
                            {{ $student->guardian->relationship ?? '-' }}
                        </strong>
                    </div>

                </div>

            @else

                <div class="profile-empty">
                    Data orang tua / wali belum tersedia.
                </div>

            @endif

        </div>

    </div>

</section>

@endsection