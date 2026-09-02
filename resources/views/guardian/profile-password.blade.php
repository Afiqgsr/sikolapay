@extends('layouts.sikolapayapp')

@section('title', 'Ubah Password - SikolaPay')

@section('page-title', 'Ubah Password')

@section('content')

<section class="guardian-profile-page">

    <div class="guardian-profile-header">

        <div>

            <h2>
                Ubah Password
            </h2>

            <p>
                Perbarui password akun untuk menjaga keamanan akun Anda
            </p>

        </div>

    </div>


    <div class="guardian-profile-card guardian-profile-password-card">

        <form
            method="POST"
            action="{{ route('guardian.profile.password.update') }}"
            class="guardian-profile-form"
        >

            @csrf
            @method('PUT')


            <div class="guardian-profile-form-group">

                <label for="current_password">
                    Password Saat Ini
                </label>

                <input
                    type="password"
                    name="current_password"
                    id="current_password"
                    autocomplete="current-password"
                    required
                >

                @error('current_password')

                    <span class="guardian-profile-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            <div class="guardian-profile-form-group">

                <label for="password">
                    Password Baru
                </label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    autocomplete="new-password"
                    required
                >

                <small>
                    Gunakan minimal 8 karakter.
                </small>

                @error('password')

                    <span class="guardian-profile-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            <div class="guardian-profile-form-group">

                <label for="password_confirmation">
                    Konfirmasi Password Baru
                </label>

                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    autocomplete="new-password"
                    required
                >

            </div>


            <div class="guardian-profile-form-actions">

                <a
                    href="{{ route('guardian.profile') }}"
                    class="guardian-profile-cancel-button"
                >
                    Batal
                </a>


                <button
                    type="submit"
                    class="guardian-profile-save-button"
                >
                    Simpan Password
                </button>

            </div>

        </form>

    </div>

</section>

@endsection