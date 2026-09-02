@extends('layouts.sikolapayapp')

@section('title', 'Edit Profil Orang Tua - SikolaPay')

@section('page-title', 'Edit Profil')

@section('content')

<section class="guardian-profile-page">

    <div class="guardian-profile-header">

        <div>

            <h2>
                Edit Profil Orang Tua / Wali
            </h2>

            <p>
                Perbarui informasi pribadi Anda
            </p>

        </div>

    </div>


    <div class="guardian-profile-card guardian-profile-edit-card">

        <form
            method="POST"
            action="{{ route('guardian.profile.update') }}"
            class="guardian-profile-form"
        >

            @csrf
            @method('PUT')


            <div class="guardian-profile-form-group">

                <label for="name">
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old(
                        'name',
                        $guardian->name ?? $user->name
                    ) }}"
                    required
                >

                @error('name')

                    <span class="guardian-profile-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            <div class="guardian-profile-form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    value="{{ $user->email }}"
                    disabled
                >

                <small>
                    Email akun tidak dapat diubah dari halaman ini.
                </small>

            </div>


            <div class="guardian-profile-form-group">

                <label for="phone">
                    Nomor Telepon
                </label>

                <input
                    type="text"
                    name="phone"
                    id="phone"
                    value="{{ old(
                        'phone',
                        $guardian->phone
                    ) }}"
                    placeholder="Contoh: 081234567890"
                >

                @error('phone')

                    <span class="guardian-profile-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            <div class="guardian-profile-form-group">

                <label for="address">
                    Alamat
                </label>

                <textarea
                    name="address"
                    id="address"
                    rows="5"
                    placeholder="Masukkan alamat lengkap"
                >{{ old(
                    'address',
                    $guardian->address
                ) }}</textarea>

                @error('address')

                    <span class="guardian-profile-error">
                        {{ $message }}
                    </span>

                @enderror

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
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</section>

@endsection