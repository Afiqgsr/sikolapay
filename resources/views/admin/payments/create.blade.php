@extends('layouts.sikolapayapp')

@section('title', 'Tambah Pembayaran')

@section('content')

<section class="admin-manual-payment">

    <div class="admin-manual-header">

        <div>
            <h2>
                Tambah Pembayaran
            </h2>

            <p>
                Catat pembayaran siswa yang dilakukan langsung melalui sekolah
            </p>
        </div>

        <a
            href="{{ route('admin.payments.index') }}"
            class="admin-manual-back"
        >
            Kembali
        </a>

    </div>


    @if ($errors->any())

        <div class="admin-manual-alert error">

            <strong>
                Data belum dapat disimpan.
            </strong>

            <ul>

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="admin-manual-card">

        <form
            action="{{ route('admin.payments.store-manual') }}"
            method="POST"
            class="admin-manual-form"
        >

            @csrf


            {{-- Siswa --}}
            <div class="admin-manual-group">

                <label for="student_id">
                    Siswa
                </label>

                <select
                    name="student_id"
                    id="student_id"
                    data-old-student="{{ old('student_id') }}"
                    required
                >

                    <option value="">
                        Pilih Siswa
                    </option>

                    @foreach ($students as $student)

                        <option
                            value="{{ $student->id }}"
                            @selected(old('student_id') == $student->id)
                        >
                            {{ $student->name }}
                            -
                            {{ $student->classRoom?->name ?? '-' }}
                            -
                            NIS {{ $student->nis }}
                        </option>

                    @endforeach

                </select>

                @error('student_id')

                    <span class="admin-manual-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            {{-- Tagihan --}}
            <div class="admin-manual-group">

                <label for="bill_id">
                    Tagihan
                </label>

                <select
                    name="bill_id"
                    id="bill_id"
                    data-old-bill="{{ old('bill_id') }}"
                    required
                    disabled
                >

                    <option value="">
                        Pilih siswa terlebih dahulu
                    </option>

                    @foreach ($bills as $bill)

                        <option
                            value="{{ $bill->id }}"
                            data-student="{{ $bill->student_id }}"
                            data-amount="{{ (float) $bill->amount }}"
                            @selected(old('bill_id') == $bill->id)
                        >
                            {{ $bill->name }}
                            -
                            Rp {{ number_format($bill->amount, 0, ',', '.') }}
                        </option>

                    @endforeach

                </select>

                <small>
                    Hanya tagihan yang belum lunas dan tidak sedang menunggu verifikasi yang ditampilkan.
                </small>

                @error('bill_id')

                    <span class="admin-manual-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            {{-- Nominal --}}
            <div class="admin-manual-group">

                <label>
                    Nominal Pembayaran
                </label>

                <div
                    class="admin-manual-amount"
                    id="paymentAmount"
                >
                    Rp 0
                </div>

                <small>
                    Nominal mengikuti jumlah tagihan yang dipilih.
                </small>

            </div>


            {{-- Metode pembayaran --}}
            <div class="admin-manual-group">

                <label for="payment_method_id">
                    Metode Pembayaran
                </label>

                <select
                    name="payment_method_id"
                    id="payment_method_id"
                    required
                >

                    <option value="">
                        Pilih Metode Pembayaran
                    </option>

                    @foreach ($paymentMethods as $method)

                        <option
                            value="{{ $method->id }}"
                            @selected(
                                old('payment_method_id') == $method->id
                            )
                        >
                            {{ $method->name }}
                        </option>

                    @endforeach

                </select>

                @error('payment_method_id')

                    <span class="admin-manual-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            {{-- Tanggal pembayaran --}}
            <div class="admin-manual-group">

                <label for="paid_at">
                    Tanggal Pembayaran
                </label>

                <input
                    type="datetime-local"
                    name="paid_at"
                    id="paid_at"
                    value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}"
                    max="{{ now()->format('Y-m-d\TH:i') }}"
                    required
                >

                @error('paid_at')

                    <span class="admin-manual-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            {{-- Informasi --}}
            <div class="admin-manual-information">

                Pembayaran yang dicatat oleh admin akan langsung berstatus
                <strong>Lunas</strong>
                dan tidak memerlukan verifikasi pembayaran lagi.

            </div>


            {{-- Action --}}
            <div class="admin-manual-actions">

                <a
                    href="{{ route('admin.payments.index') }}"
                    class="admin-manual-cancel"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="admin-manual-submit"
                >
                    Simpan Pembayaran
                </button>

            </div>

        </form>

    </div>

</section>

@endsection


@push('scripts')

    @vite('resources/js/pages/admin/manual-payment.js')

@endpush