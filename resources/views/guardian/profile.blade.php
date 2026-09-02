@extends('layouts.sikolapayapp')

@section('title', 'Profil Orang Tua - SikolaPay')

@section('page-title', 'Profil')

@section('content')

<section class="guardian-profile-page">

    {{-- Header --}}
    <div class="guardian-profile-header">

        <div>

            <h2>
                Profil Orang Tua / Wali
            </h2>

            <p>
                Kelola informasi akun dan data pribadi Anda
            </p>

        </div>


        <a
            href="{{ route('guardian.profile.edit') }}"
            class="guardian-profile-edit-button"
        >
            Edit Profil
        </a>

    </div>


    {{-- Alert --}}
    @if(session('success'))

        <div class="guardian-profile-alert success">

            {{ session('success') }}

        </div>

    @endif


    {{-- Layout --}}
    <div class="guardian-profile-layout">


        {{-- Card Profil --}}
        <div class="guardian-profile-card guardian-profile-main-card">

            <div class="guardian-profile-identity">

                <div class="guardian-profile-avatar">

                    {{ strtoupper(
                        substr(
                            $user->name ?? 'GU',
                            0,
                            2
                        )
                    ) }}

                </div>


                <div class="guardian-profile-identity-info">

                    <h3>
                        {{ $user->name }}
                    </h3>

                    <p>
                        Orang Tua / Wali
                    </p>


                    <span class="guardian-profile-status active">
                        Aktif
                    </span>

                </div>

            </div>


            <div class="guardian-profile-divider"></div>


            <div class="guardian-profile-info-list">


                <div class="guardian-profile-info-row">

                    <span>
                        Nama Lengkap
                    </span>

                    <strong>
                        {{ $guardian->name ?? $user->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-profile-info-row">

                    <span>
                        Email
                    </span>

                    <strong>
                        {{ $user->email ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-profile-info-row">

                    <span>
                        Nomor Telepon
                    </span>

                    <strong>
                        {{ $guardian->phone ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-profile-info-row">

                    <span>
                        Alamat
                    </span>

                    <strong>
                        {{ $guardian->address ?? '-' }}
                    </strong>

                </div>

            </div>

        </div>


        {{-- Card Anak --}}
        <div class="guardian-profile-card">

            <div class="guardian-profile-card-header">

                <div>

                    <h3>
                        Data Anak
                    </h3>

                    <p>
                        Anak yang terhubung dengan akun Anda
                    </p>

                </div>

            </div>


            <div class="guardian-profile-children">

                @forelse($guardian->students as $student)

                    <div class="guardian-profile-child">

                        <div class="guardian-profile-child-avatar">

                            {{ strtoupper(
                                substr(
                                    $student->name ?? 'AN',
                                    0,
                                    2
                                )
                            ) }}

                        </div>


                        <div class="guardian-profile-child-info">

                            <strong>
                                {{ $student->name }}
                            </strong>

                            <span>
                                {{ $student->classRoom?->name ?? '-' }}
                            </span>

                            <small>
                                NIS: {{ $student->nis ?? '-' }}
                            </small>

                        </div>

                    </div>

                @empty

                    <div class="guardian-profile-empty">

                        Belum ada data anak yang terhubung.

                    </div>

                @endforelse

            </div>

        </div>

    </div>


    {{-- Account Security --}}
    <div class="guardian-profile-card guardian-profile-security">

        <div class="guardian-profile-card-header">

            <div>

                <h3>
                    Keamanan Akun
                </h3>

                <p>
                    Kelola keamanan akun SikolaPay Anda
                </p>

            </div>

        </div>


        <div class="guardian-profile-security-row">

            <div>

                <strong>
                    Password
                </strong>

                <p>
                    Ubah password akun Anda secara berkala
                </p>

            </div>

            <a
                href="{{ route('guardian.profile.password.edit') }}"
                class="guardian-profile-security-button"
            >
                Ubah Password
            </a>

        </div>

    </div>

</section>

@endsection