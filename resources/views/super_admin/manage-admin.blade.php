@extends('layouts.sikolapayapp')

@section('title', 'Kelola Admin - SikolaPay')

@section('page-title', 'Kelola Admin')

@section('content')

<section class="admin-page">

    {{-- Header --}}
    <div class="admin-page-header">

        <h2>
            Kelola Admin
        </h2>

        <p>
            Manajemen akun admin sistem SikolaPay
        </p>

    </div>


    {{-- Alert --}}
    @if(session('success'))

        <div class="admin-alert success">
            {{ session('success') }}
        </div>

    @endif


    @if($errors->any())

        <div class="admin-alert error">

            @foreach($errors->all() as $error)

                <div>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- Card --}}
    <section class="admin-management-card">

        {{-- Toolbar --}}
        <div class="admin-toolbar">

            <form
                action="{{ route('superadmin.admins.index') }}"
                method="GET"
                class="admin-search"
            >

                <img
                    src="{{ asset('assets/img/search-black-superadmin.svg') }}"
                    alt=""
                >

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama atau email..."
                >

            </form>


            <button
                type="button"
                class="admin-add-button"
                id="openAdminAddButton"
            >

                <img
                    src="{{ asset('assets/img/Add_round-superadmin.svg') }}"
                    alt=""
                >

                <span>
                    Tambah Admin
                </span>

            </button>

        </div>


        {{-- Table --}}
        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>Nama Admin</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse($admins as $admin)

                        @php
                            $roleLabel =
                                $admin->role === 'super_admin'
                                    ? 'Super Admin'
                                    : 'Admin';

                            $statusLabel =
                                $admin->status === 'active'
                                    ? 'Aktif'
                                    : 'Non-Aktif';

                            $initials = collect(
                                explode(' ', $admin->name)
                            )
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) =>
                                    mb_strtoupper(
                                        mb_substr(
                                            $part,
                                            0,
                                            1
                                        )
                                    )
                                )
                                ->implode('');
                        @endphp

                        <tr>

                            {{-- Nama --}}
                            <td class="admin-name">
                                {{ $admin->name }}
                            </td>


                            {{-- Email --}}
                            <td>
                                {{ $admin->email }}
                            </td>


                            {{-- Role --}}
                            <td>

                                <span
                                    class="admin-role {{ $admin->role === 'super_admin' ? 'super-admin' : '' }}"
                                >
                                    {{ $roleLabel }}
                                </span>

                            </td>


                            {{-- Status --}}
                            <td>

                                <span
                                    class="admin-status {{ $admin->status === 'active' ? 'active' : 'inactive' }}"
                                >
                                    {{ $statusLabel }}
                                </span>

                            </td>


                            {{-- Dibuat --}}
                            <td>

                                {{ $admin->created_at
                                    ? $admin->created_at->translatedFormat(
                                        'd F Y'
                                    )
                                    : '-'
                                }}

                            </td>


                            {{-- Aksi --}}
                            <td>

                                <div class="admin-actions">

                                    <button
                                        type="button"
                                        class="admin-action detail"
                                        data-id="{{ $admin->id }}"
                                    >
                                        Detail
                                    </button>


                                    <button
                                        type="button"
                                        class="admin-action edit"
                                        data-id="{{ $admin->id }}"
                                        data-name="{{ $admin->name }}"
                                        data-email="{{ $admin->email }}"
                                        data-role="{{ $admin->role }}"
                                        data-status="{{ $admin->status }}"
                                        data-update-url="{{ route(
                                            'superadmin.admins.update',
                                            $admin->id
                                        ) }}"
                                    >
                                        Edit
                                    </button>


                                    @if(auth()->id() !== $admin->id)

                                        <button
                                            type="button"
                                            class="admin-action delete"
                                            data-id="{{ $admin->id }}"
                                            data-name="{{ $admin->name }}"
                                            data-email="{{ $admin->email }}"
                                            data-delete-url="{{ route(
                                                'superadmin.admins.destroy',
                                                $admin->id
                                            ) }}"
                                        >
                                            Hapus
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="admin-empty"
                            >
                                Belum ada data admin.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($admins->hasPages())

            <div class="admin-pagination">
                {{ $admins->links() }}
            </div>

        @endif

    </section>

</section>


{{-- Data Admin --}}
@php
    $adminDetailData = [];

    foreach ($admins as $admin) {

        $initials = collect(
            explode(' ', $admin->name)
        )
            ->filter()
            ->take(2)
            ->map(function ($part) {
                return mb_strtoupper(
                    mb_substr(
                        $part,
                        0,
                        1
                    )
                );
            })
            ->implode('');

        $adminDetailData[$admin->id] = [
            'id' => $admin->id,

            'initials' =>
                $initials,

            'name' =>
                $admin->name,

            'email' =>
                $admin->email,

            'role' =>
                $admin->role,

            'role_label' =>
                $admin->role === 'super_admin'
                    ? 'Super Admin'
                    : 'Admin',

            'status' =>
                $admin->status,

            'status_label' =>
                $admin->status === 'active'
                    ? 'Aktif'
                    : 'Non-Aktif',

            'created_at' =>
                $admin->created_at
                    ? $admin->created_at
                        ->translatedFormat(
                            'd F Y'
                        )
                    : '-',

            'update_url' =>
                route(
                    'superadmin.admins.update',
                    $admin->id
                ),

            'delete_url' =>
                route(
                    'superadmin.admins.destroy',
                    $admin->id
                ),
        ];
    }
@endphp


<script
    type="application/json"
    id="adminDetailData"
>
{!! json_encode(
    $adminDetailData,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
) !!}
</script>


{{-- Modal Detail --}}
<div
    class="admin-detail-overlay"
    id="adminDetailModal"
>

    <div class="detail-data-admin">

        {{-- Header --}}
        <div class="frame">

            <div class="div">

                <div class="div-wrapper">

                    <div class="text-wrapper">
                        Detail Admin
                    </div>

                </div>


                <button
                    type="button"
                    class="icon-set-wrapper admin-detail-close"
                >

                    <img
                        class="icon-set"
                        src="{{ asset('assets/img/close-superadmin.svg') }}"
                        alt="Tutup"
                    >

                </button>

            </div>


            {{-- Profile --}}
            <div class="frame-2">

                <div class="frame-3">

                    <div class="frame-4">

                        <div
                            class="text-wrapper-2"
                            id="detailAdminInitial"
                        >
                            -
                        </div>

                    </div>


                    <div class="frame-5">

                        <div
                            class="text-wrapper-3"
                            id="detailAdminProfileName"
                        >
                            -
                        </div>

                    </div>

                </div>


                <div class="frame-6">

                    <div
                        class="text-wrapper-4"
                        id="detailAdminProfileRole"
                    >
                        -
                    </div>

                </div>

            </div>

        </div>


        {{-- Detail --}}
        <div class="frame-7">

            <div class="frame-8">

                <div class="frame-9">

                    <div class="frame-10">

                        <div class="text-wrapper-5">
                            Nama Lengkap
                        </div>

                    </div>

                    <div class="frame-10">

                        <div
                            class="text-wrapper"
                            id="detailAdminName"
                        >
                            -
                        </div>

                    </div>

                </div>


                <div class="frame-9">

                    <div class="frame-10">

                        <div class="text-wrapper-5">
                            Email
                        </div>

                    </div>

                    <div class="frame-10">

                        <div
                            class="text-wrapper"
                            id="detailAdminEmail"
                        >
                            -
                        </div>

                    </div>

                </div>


                <div class="frame-9">

                    <div class="frame-10">

                        <div class="text-wrapper-5">
                            Role
                        </div>

                    </div>

                    <div class="frame-10">

                        <div
                            class="text-wrapper"
                            id="detailAdminRole"
                        >
                            -
                        </div>

                    </div>

                </div>


                <div class="frame-9">

                    <div class="frame-10">

                        <div class="text-wrapper-5">
                            Status
                        </div>

                    </div>

                    <div class="frame-10">

                        <div
                            class="text-wrapper"
                            id="detailAdminStatus"
                        >
                            -
                        </div>

                    </div>

                </div>


                <div class="frame-9">

                    <div class="frame-10">

                        <div class="text-wrapper-5">
                            Dibuat
                        </div>

                    </div>

                    <div class="frame-10">

                        <div
                            class="text-wrapper"
                            id="detailAdminCreated"
                        >
                            -
                        </div>

                    </div>

                </div>

            </div>


            {{-- Button --}}
            <div class="frame-11">

                <button
                    type="button"
                    class="button admin-detail-close"
                >
                    <div class="button-2">
                        Tutup
                    </div>
                </button>


                <button
                    type="button"
                    class="button-3"
                    id="detailAdminEditButton"
                >
                    <div class="button-4">
                        Edit Data
                    </div>
                </button>

            </div>

        </div>

    </div>

</div>


{{-- Modal Edit --}}
<div
    class="admin-edit-overlay"
    id="adminEditModal"
>

    <form
        method="POST"
        class="form-edit-admin"
        id="adminEditForm"
    >

        @csrf
        @method('PUT')


        {{-- Header --}}
        <div class="frame">

            <div class="div-wrapper">

                <div class="text-wrapper">
                    Edit Admin
                </div>

            </div>

            <button
                type="button"
                class="icon-set-wrapper admin-edit-close"
            >

                <img
                    class="icon-set"
                    src="{{ asset('assets/img/close-superadmin.svg') }}"
                    alt="Tutup"
                >

            </button>

        </div>


        {{-- Form --}}
        <div class="div">

            {{-- Nama --}}
            <div class="inputs-wrapper">

                <div class="inputs">

                    <div class="input-master">

                        <label class="label-input">

                            <span class="span">
                                Nama Lengkap
                            </span>

                            <span class="text-wrapper-2">
                                *
                            </span>

                        </label>


                        <div class="input-filed">

                            <input
                                type="text"
                                id="editNama"
                                name="name"
                                placeholder="Nama Lengkap Admin"
                                required
                            >

                        </div>

                    </div>

                </div>

            </div>


            {{-- Email --}}
            <div class="inputs-wrapper">

                <div class="inputs">

                    <div class="input-master">

                        <label class="label-input">

                            <span class="span">
                                Email
                            </span>

                            <span class="text-wrapper-2">
                                *
                            </span>

                        </label>


                        <div class="input-filed">

                            <input
                                type="email"
                                id="editEmail"
                                name="email"
                                placeholder="Email Admin"
                                required
                            >

                        </div>

                    </div>

                </div>

            </div>


            {{-- Role --}}
            <div class="inputs-wrapper">

                <div class="inputs">

                    <div class="input-master">

                        <label class="label-input">

                            <span class="span">
                                Role
                            </span>

                            <span class="text-wrapper-2">
                                *
                            </span>

                        </label>


                        <div class="input-filed select-field">

                            <select
                                id="editRole"
                                name="role"
                                required
                            >

                                <option value="admin">
                                    Admin
                                </option>

                                <option value="super_admin">
                                    Super Admin
                                </option>

                            </select>


                            <img
                                class="img select-icon"
                                src="{{ asset('assets/img/dropdown-superadmin.svg') }}"
                                alt=""
                            >

                        </div>

                    </div>

                </div>

            </div>


            {{-- Status --}}
            <div class="inputs-wrapper">

                <div class="inputs">

                    <div class="input-master">

                        <label class="label-input-2">
                            Status
                        </label>


                        <div class="input-filed select-field">

                            <select
                                id="editStatus"
                                name="status"
                                required
                            >

                                <option value="active">
                                    Aktif
                                </option>

                                <option value="inactive">
                                    Non-Aktif
                                </option>

                            </select>


                            <img
                                class="img select-icon"
                                src="{{ asset('assets/img/dropdown-superadmin.svg') }}"
                                alt=""
                            >

                        </div>

                    </div>

                </div>

            </div>


            {{-- Password --}}
            <div class="frame-2">

                <div class="text-wrapper-3">
                    Password
                </div>

            </div>


            <div class="inputs-wrapper">

                <div class="inputs">

                    <div class="input-master">

                        <label class="label-input">

                            <span class="span">
                                Password Baru
                            </span>

                        </label>


                        <div class="input-filed-2">

                            <input
                                type="password"
                                id="editPassword"
                                name="password"
                                placeholder="Kosongkan jika tidak diubah"
                            >


                            <button
                                type="button"
                                class="edit-password-toggle"
                                data-password-target="editPassword"
                            >

                                <img
                                    src="{{ asset('assets/img/View_light-superadmin.svg') }}"
                                    alt="Lihat password"
                                >

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Konfirmasi Password --}}
            <div class="inputs-wrapper">

                <div class="inputs">

                    <div class="input-master">

                        <label class="label-input">

                            <span class="span">
                                Konfirmasi Password
                            </span>

                        </label>


                        <div class="input-filed-2">

                            <input
                                type="password"
                                id="editPasswordConfirm"
                                name="password_confirmation"
                                placeholder="Ulangi password baru"
                            >


                            <button
                                type="button"
                                class="edit-password-toggle"
                                data-password-target="editPasswordConfirm"
                            >

                                <img
                                    src="{{ asset('assets/img/View_light-superadmin.svg') }}"
                                    alt="Lihat password"
                                >

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Footer --}}
        <div class="frame-3">

            <div class="button-tombol-menu-wrapper">

                <button
                    type="button"
                    class="button-tombol-menu admin-edit-close"
                >

                    <div class="semua">
                        Batal
                    </div>

                </button>

            </div>


            <div class="frame-4">

                <button
                    type="submit"
                    class="semua-wrapper"
                >

                    <div class="semua-2">
                        Simpan Perubahan
                    </div>

                </button>

            </div>

        </div>

    </form>

</div>


{{-- Modal Tambah --}}
<div
    class="admin-add-overlay"
    id="adminAddModal"
>

    <form
        action="{{ route('superadmin.admins.store') }}"
        method="POST"
        class="admin-add-modal"
    >

        @csrf


        {{-- Header --}}
        <div class="admin-add-header">

            <div class="admin-add-title">
                Tambah Admin
            </div>


            <button
                type="button"
                class="admin-add-close"
            >

                <img
                    src="{{ asset('assets/img/close-superadmin.svg') }}"
                    alt="Tutup"
                >

            </button>

        </div>


        {{-- Form --}}
        <div class="admin-add-form">

            {{-- Nama --}}
            <div class="admin-add-field">

                <label for="addNama">

                    Nama Lengkap

                    <span>
                        *
                    </span>

                </label>


                <input
                    type="text"
                    id="addNama"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Nama Lengkap Admin"
                    required
                >

            </div>


            {{-- Email --}}
            <div class="admin-add-field">

                <label for="addEmail">

                    Email

                    <span>
                        *
                    </span>

                </label>


                <input
                    type="email"
                    id="addEmail"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Email Admin"
                    required
                >

            </div>


            {{-- Role --}}
            <div class="admin-add-field">

                <label for="addRole">

                    Role

                    <span>
                        *
                    </span>

                </label>


                <div class="admin-add-select">

                    <select
                        id="addRole"
                        name="role"
                        required
                    >

                        <option
                            value="admin"
                            @selected(old('role', 'admin') === 'admin')
                        >
                            Admin
                        </option>

                        <option
                            value="super_admin"
                            @selected(old('role') === 'super_admin')
                        >
                            Super Admin
                        </option>

                    </select>


                    <img
                        src="{{ asset('assets/img/dropdown-superadmin.svg') }}"
                        alt=""
                    >

                </div>

            </div>


            {{-- Status --}}
            <div class="admin-add-field">

                <label for="addStatus">
                    Status
                </label>


                <div class="admin-add-select">

                    <select
                        id="addStatus"
                        name="status"
                        required
                    >

                        <option
                            value="active"
                            @selected(
                                old(
                                    'status',
                                    'active'
                                ) === 'active'
                            )
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            @selected(
                                old('status') === 'inactive'
                            )
                        >
                            Non-Aktif
                        </option>

                    </select>


                    <img
                        src="{{ asset('assets/img/dropdown-superadmin.svg') }}"
                        alt=""
                    >

                </div>

            </div>


            <div class="admin-add-password-title">
                Password
            </div>


            {{-- Password --}}
            <div class="admin-add-field">

                <label for="addPassword">

                    Password

                    <span>
                        *
                    </span>

                </label>


                <div class="admin-add-password">

                    <input
                        type="password"
                        id="addPassword"
                        name="password"
                        placeholder="Minimal 8 Karakter"
                        required
                    >


                    <button
                        type="button"
                        class="admin-add-password-toggle"
                        data-password-target="addPassword"
                    >

                        <img
                            src="{{ asset('assets/img/View_light-superadmin.svg') }}"
                            alt="Lihat password"
                        >

                    </button>

                </div>

            </div>


            {{-- Konfirmasi --}}
            <div class="admin-add-field">

                <label for="addPasswordConfirm">

                    Konfirmasi Password

                    <span>
                        *
                    </span>

                </label>


                <div class="admin-add-password">

                    <input
                        type="password"
                        id="addPasswordConfirm"
                        name="password_confirmation"
                        placeholder="Ulangi Password"
                        required
                    >


                    <button
                        type="button"
                        class="admin-add-password-toggle"
                        data-password-target="addPasswordConfirm"
                    >

                        <img
                            src="{{ asset('assets/img/View_light-superadmin.svg') }}"
                            alt="Lihat password"
                        >

                    </button>

                </div>

            </div>

        </div>


        {{-- Footer --}}
        <div class="admin-add-actions">

            <button
                type="button"
                class="admin-add-action cancel admin-add-close"
            >
                Batal
            </button>


            <button
                type="submit"
                class="admin-add-action save"
            >
                Simpan
            </button>

        </div>

    </form>

</div>


{{-- Modal Hapus --}}
<div
    class="admin-delete-overlay"
    id="adminDeleteModal"
>

    <div class="hapus-admin">

        <div class="frame">

            <div class="div">

                <div class="div-wrapper">

                    <div class="text-wrapper">
                        Hapus Admin
                    </div>

                </div>


                <button
                    type="button"
                    class="icon-set-wrapper admin-delete-close"
                >

                    <img
                        class="icon-set"
                        src="{{ asset('assets/img/close-superadmin.svg') }}"
                        alt="Tutup"
                    >

                </button>

            </div>

        </div>


        <div class="frame-2">

            <div class="frame-3">

                <div class="frame-4">

                    <img
                        class="icon-set"
                        src="{{ asset('assets/img/caution-red-superadmin.svg') }}"
                        alt=""
                    >

                    <p class="p">
                        Tindakan ini tidak dapat dibatalkan!
                    </p>

                </div>


                <div class="hapus-akun-admin-wrapper">

                    <p class="hapus-akun-admin">

                        <span class="span">
                            Hapus akun admin
                        </span>

                        <span
                            class="text-wrapper-2"
                            id="deleteAdminIdentity"
                        >
                            -
                        </span>

                        <span class="span">
                            ? Admin ini tidak akan dapat mengakses sistem.
                        </span>

                    </p>

                </div>

            </div>


            <div class="frame-5">

                <button
                    type="button"
                    class="button admin-delete-close"
                >
                    Batal
                </button>


                <form
                    method="POST"
                    id="adminDeleteForm"
                >

                    @csrf
                    @method('DELETE')


                    <button
                        type="submit"
                        class="button-3"
                    >
                        Hapus Admin
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


@push('scripts')
    @vite('resources/js/pages/superadmin/manage-admin.js')
@endpush

@endsection