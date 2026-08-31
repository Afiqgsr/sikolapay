@extends('layouts.sikolapayapp')

@section('title', 'Pembayaran - SikolaPay')

@section('page-title', 'Pembayaran')

@push('styles') <link rel="stylesheet" href="{{ asset('css/pages/student/payment.css') }}">
@endpush

@section('content')

@php
// ==============================
// PAYMENT METHODS
// ==============================

$bankTransferMethods = $paymentMethods->where('type', 'bank_transfer');

$vaMethods = $paymentMethods->where('type', 'virtual_account');

$qrisMethod = $paymentMethods->where('type', 'qris')->first();

$ewalletMethods = $paymentMethods->where('type', 'e_wallet');


// ==============================
// DEFAULT METHOD
// ==============================

$defaultMethod = null;
$defaultMethodName = '-';

if ($bankTransferMethods->count()) {

    $defaultMethod = 'bank';
    $defaultMethodName = 'Transfer Bank';

} elseif ($vaMethods->count()) {

    $defaultMethod = 'va';
    $defaultMethodName = 'Virtual Account';

} elseif ($qrisMethod) {

    $defaultMethod = 'qris';
    $defaultMethodName = 'QRIS';

} elseif ($ewalletMethods->count()) {

    $defaultMethod = 'ewallet';
    $defaultMethodName = 'E-Wallet';

}

@endphp

<section class="payment-page">

{{-- BACK --}}
<div class="payment-back">

    <button
        type="button"
        class="back-button"
        onclick="history.back()"
    >

        <img
            src="{{ asset('assets/img/back.svg') }}"
            alt=""
        >

        <span>Kembali ke Detail Tagihan</span>

    </button>

</div>


{{-- PAGE HEADER --}}
<div class="payment-header">

    <h2>Pembayaran</h2>

    <p>
        {{ $bill->name }} · {{ $student->name }}
    </p>

</div>


{{-- MAIN GRID --}}
<div class="payment-main-grid">


    {{-- ==========================================
        LEFT COLUMN
    =========================================== --}}
    <div class="payment-left-column">


        {{-- ==========================================
            PAYMENT METHOD
        =========================================== --}}
        <div class="payment-card">

            <div class="payment-card-header">

                <h3>Pilih Metode Pembayaran</h3>

            </div>


            <div class="payment-methods">


                {{-- TRANSFER BANK --}}
                <button
                    type="button"
                    class="payment-method {{ $defaultMethod === 'bank' ? 'active' : '' }}"
                    data-method="bank"
                    {{ $bankTransferMethods->count() ? '' : 'disabled' }}
                >

                    <img
                        src="{{ asset('assets/img/tf-pr.svg') }}"
                        alt=""
                    >

                    <span>Transfer Bank</span>

                </button>


                {{-- VIRTUAL ACCOUNT --}}
                <button
                    type="button"
                    class="payment-method {{ $defaultMethod === 'va' ? 'active' : '' }}"
                    data-method="va"
                    {{ $vaMethods->count() ? '' : 'disabled' }}
                >

                    <img
                        src="{{ asset('assets/img/va-pr.svg') }}"
                        alt=""
                    >

                    <span>Virtual Account</span>

                </button>


                {{-- QRIS --}}
                <button
                    type="button"
                    class="payment-method {{ $defaultMethod === 'qris' ? 'active' : '' }}"
                    data-method="qris"
                    {{ $qrisMethod ? '' : 'disabled' }}
                >

                    <img
                        src="{{ asset('assets/img/qris-pr.svg') }}"
                        alt=""
                    >

                    <span>QRIS</span>

                </button>


                {{-- E-WALLET --}}
                <button
                    type="button"
                    class="payment-method {{ $defaultMethod === 'ewallet' ? 'active' : '' }}"
                    data-method="ewallet"
                    {{ $ewalletMethods->count() ? '' : 'disabled' }}
                >

                    <img
                        src="{{ asset('assets/img/e-wallet pr.svg') }}"
                        alt=""
                    >

                    <span>E-Wallet</span>

                </button>

            </div>

        </div>



        {{-- ==========================================
            TRANSFER BANK
        =========================================== --}}
        <div
            class="detail-card payment-option-content"
            id="bankContent"
            @if ($defaultMethod !== 'bank')
                style="display: none;"
            @endif
        >

            <div class="detail-header">

                <h3>Detail Transfer Bank</h3>

                <p>Pilih bank tujuan:</p>

            </div>


            @if ($bankTransferMethods->count())

                <div class="bank-list">

                    @foreach ($bankTransferMethods as $method)

                        <button
                            type="button"
                            class="bank-item {{ $loop->first ? 'active' : '' }}"

                            data-method-id="{{ $method->id }}"
                            data-code="{{ $method->code }}"
                            data-name="{{ $method->name }}"

                            data-account-number="{{ $method->account_number ?? '' }}"
                            data-account-name="{{ $method->account_name ?? '' }}"
                        >

                            <span>
                                {{ $method->name }}
                            </span>

                        </button>

                    @endforeach

                </div>


                {{-- ACCOUNT INFORMATION --}}
                <div class="account-information">

                    <div class="account-box">

                        <div
                            class="account-label"
                            id="accountLabel"
                        >
                            {{ $bankTransferMethods->first()->name }}
                        </div>


                        <div
                            class="account-number"
                            id="accountNumber"
                        >
                            {{ $bankTransferMethods->first()->account_number ?? 'Menunggu informasi rekening' }}
                        </div>


                        <div
                            class="account-owner"
                            id="accountOwner"
                        >
                            {{ $bankTransferMethods->first()->account_name ?? '-' }}
                        </div>

                    </div>


                    <div class="transfer-note">

                        <p>

                            <strong>
                                Nominal transfer harus sama persis
                            </strong>

                            <span>
                                Rp {{ number_format($bill->amount, 0, ',', '.') }}
                            </span>.

                            Tambahkan kode unik jika diperlukan.

                        </p>

                    </div>

                </div>

            @else

                <div class="payment-empty">

                    <p>
                        Metode Transfer Bank belum tersedia.
                    </p>

                </div>

            @endif

        </div>



        {{-- ==========================================
            VIRTUAL ACCOUNT
        =========================================== --}}
        <div
            class="detail-card payment-option-content"
            id="vaContent"
            @if ($defaultMethod !== 'va')
                style="display: none;"
            @endif
        >

            <div class="detail-header">

                <h3>Virtual Account</h3>

                <p>
                    Pilih bank Virtual Account:
                </p>

            </div>


            @if ($vaMethods->count())

                <div class="bank-list">

                    @foreach ($vaMethods as $method)

                        <button
                            type="button"
                            class="bank-item {{ $loop->first ? 'active' : '' }}"

                            data-method-id="{{ $method->id }}"
                            data-code="{{ $method->code }}"
                            data-name="{{ $method->name }}"

                            data-account-number="{{ $method->account_number ?? '' }}"
                            data-account-name="{{ $method->account_name ?? '' }}"
                        >

                            <span>
                                {{ $method->name }}
                            </span>

                        </button>

                    @endforeach

                </div>


                <div class="account-information">

                    <div class="account-box">

                        <div
                            class="account-label"
                            id="vaAccountLabel"
                        >
                            {{ $vaMethods->first()->name }}
                        </div>


                        <div
                            class="account-number"
                            id="vaAccountNumber"
                        >
                            {{ $vaMethods->first()->account_number ?? 'Menunggu nomor Virtual Account' }}
                        </div>


                        <div
                            class="account-owner"
                            id="vaAccountOwner"
                        >
                            {{ $vaMethods->first()->account_name ?? 'a.n. ' . $student->name }}
                        </div>

                    </div>


                    <div class="transfer-note">

                        <p>

                            <strong>
                                Nominal pembayaran
                            </strong>

                            <span>
                                Rp {{ number_format($bill->amount, 0, ',', '.') }}
                            </span>

                            harus dibayar sesuai nominal tagihan.

                        </p>

                    </div>

                </div>

            @else

                <div class="payment-empty">

                    <p>
                        Metode Virtual Account belum tersedia.
                    </p>

                </div>

            @endif

        </div>



        {{-- ==========================================
            QRIS
        =========================================== --}}
        <div
            class="detail-card payment-option-content"
            id="qrisContent"
            @if ($defaultMethod !== 'qris')
                style="display: none;"
            @endif
        >

            <div class="detail-header">

                <h3>Pembayaran QRIS</h3>

                <p>
                    Scan QR Code menggunakan aplikasi pembayaran Anda.
                </p>

            </div>


            @if ($qrisMethod)

                <div class="qris-payment">

                    <div class="qris-box">

                        <div class="qris-placeholder">

                            <span>QRIS</span>

                            <small>
                                QR Code Pembayaran
                            </small>

                        </div>

                    </div>


                    <div class="qris-information">

                        <strong>
                            Total Pembayaran
                        </strong>

                        <span>
                            Rp {{ number_format($bill->amount, 0, ',', '.') }}
                        </span>

                    </div>


                    <div class="transfer-note">

                        <p>
                            Scan QR Code di atas menggunakan
                            aplikasi pembayaran yang mendukung QRIS.
                        </p>

                    </div>

                </div>

            @else

                <div class="payment-empty">

                    <p>
                        Metode QRIS belum tersedia.
                    </p>

                </div>

            @endif

        </div>



        {{-- ==========================================
            E-WALLET
        =========================================== --}}
        <div
            class="detail-card payment-option-content"
            id="ewalletContent"
            @if ($defaultMethod !== 'ewallet')
                style="display: none;"
            @endif
        >

            <div class="detail-header">

                <h3>E-Wallet</h3>

                <p>
                    Pilih e-wallet yang ingin digunakan:
                </p>

            </div>


            @if ($ewalletMethods->count())

                <div class="bank-list">

                    @foreach ($ewalletMethods as $method)

                        <button
                            type="button"
                            class="bank-item {{ $loop->first ? 'active' : '' }}"

                            data-method-id="{{ $method->id }}"
                            data-code="{{ $method->code }}"
                            data-name="{{ $method->name }}"

                            data-account-number="{{ $method->account_number ?? '' }}"
                            data-account-name="{{ $method->account_name ?? '' }}"
                        >

                            <span>
                                {{ $method->name }}
                            </span>

                        </button>

                    @endforeach

                </div>


                <div class="account-information">

                    <div class="account-box">

                        <div
                            class="account-label"
                            id="ewalletLabel"
                        >
                            {{ $ewalletMethods->first()->name }}
                        </div>


                        <div
                            class="account-number"
                            id="ewalletNumber"
                        >
                            {{ $ewalletMethods->first()->account_number ?? '-' }}
                        </div>


                        <div
                            class="account-owner"
                            id="ewalletOwner"
                        >
                            {{ $ewalletMethods->first()->account_name ?? '-' }}
                        </div>

                    </div>


                    <div class="transfer-note">

                        <p>

                            <strong>
                                Total pembayaran
                            </strong>

                            <span>
                                Rp {{ number_format($bill->amount, 0, ',', '.') }}
                            </span>

                        </p>

                    </div>

                </div>

            @else

                <div class="payment-empty">

                    <p>
                        Metode E-Wallet belum tersedia.
                    </p>

                </div>

            @endif

        </div>

        <!-- UPLOAD BUKTI -->

        <div class="upload-card">

            <div class="upload-header">
                <h3>Upload Bukti Pembayaran</h3>
            </div>

            <div class="upload-area" id="uploadArea">

                <img
                    src="{{ asset('assets/img/import-pr.svg') }}"
                    alt="Upload"
                    class="upload-icon"
                >

                <!-- TAMPILAN AWAL -->

                <div class="upload-content" id="uploadContent">

                    <p class="upload-title">
                        Drag &amp; Drop atau klik untuk upload
                    </p>

                    <p class="upload-description">
                        PNG, JPG, PDF, hingga 5MB
                    </p>

                </div>


                <!-- NAMA FILE -->

                <div
                    class="selected-file"
                    id="selectedFile"
                >
                    <span id="selectedFileName"></span>
                </div>


                <!-- INPUT FILE -->

                <input
                    type="file"
                    id="proofOfPayment"
                    name="proof_of_payment"
                    accept=".png,.jpg,.jpeg,.pdf"
                    hidden
                >

                <button
                    type="button"
                    class="upload-button"
                    id="uploadButton"
                >
                    Pilih File
                </button>

            </div>

            <p class="upload-note">
                Upload screenshot atau foto bukti transfer.
                Pembayaran akan diverifikasi oleh admin
                dalam 1x24 jam.
            </p>

        </div>


    </div>



    {{-- ==========================================
         RIGHT COLUMN
    =========================================== --}}
    <div class="payment-right-column">

        <div class="summary-card">

            <div class="summary-header">

                <h3>
                    Ringkasan Pembayaran
                </h3>

            </div>


            <div class="summary-content">

                <div class="summary-list">


                    {{-- NAMA SISWA --}}
                    <div class="summary-row">

                        <span>
                            Nama Siswa
                        </span>

                        <span>
                            {{ $student->name }}
                        </span>

                    </div>


                    {{-- JENIS TAGIHAN --}}
                    <div class="summary-row">

                        <span>
                            Jenis Tagihan
                        </span>

                        <span>
                            {{ $bill->name }}
                        </span>

                    </div>


                    {{-- PERIODE --}}
                    <div class="summary-row">

                        <span>
                            Periode
                        </span>

                        <span>
                            {{ $bill->description ?? '-' }}
                        </span>

                    </div>


                    {{-- METODE --}}
                    <div class="summary-row">

                        <span>
                            Metode
                        </span>

                        <span id="selectedPaymentMethod">
                            {{ $defaultMethodName }}
                        </span>

                    </div>

                </div>



                {{-- TOTAL --}}
                <div class="summary-total">

                    <div>

                        <span>
                            Total
                        </span>

                        <strong>
                            Rp {{ number_format($bill->amount, 0, ',', '.') }}
                        </strong>

                    </div>

                </div>



                {{-- CONFIRMATION --}}
                <div class="confirmation">

                    <button
                        type="button"
                        class="payment-confirm-button"
                        id="openConfirmModal"
                    >
                        Konfirmasi Pembayaran
                    </button>


                    <p>

                        Dengan mengklik tombol di atas,
                        Anda menyatakan telah melakukan pembayaran.

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


</section>

{{-- ==========================================
MODAL KONFIRMASI
=========================================== --}}

<div
    class="modal-overlay"
    id="confirmModal"
>

<div class="modal-box">

    <div class="modal-header">

        <h3>
            Konfirmasi Pembayaran
        </h3>


        <button
            type="button"
            class="modal-close"
            id="closeConfirmModal"
        >
            ✕
        </button>

    </div>


    <div class="modal-body">

        <div class="modal-icon warning">

            <img
                src="{{ asset('assets/img/konfirmasi pembayaran.svg') }}"
                alt="Warning"
            >

        </div>


        <h2>
            Konfirmasi Pembayaran {{ $bill->name }}?
        </h2>


        <p>

            Pastikan Anda telah melakukan pembayaran
            sebesar

            <strong>
                Rp {{ number_format($bill->amount, 0, ',', '.') }}
            </strong>

            melalui

            <strong id="confirmPaymentMethod">
                {{ $defaultMethodName }}
            </strong>.

        </p>


        {{-- MENYIMPAN PAYMENT METHOD TERPILIH --}}
        <input
            type="hidden"
            id="selectedPaymentMethodId"
            value="{{ $bankTransferMethods->first()->id ?? $vaMethods->first()->id ?? $qrisMethod->id ?? $ewalletMethods->first()->id ?? '' }}"
        >


        <div class="modal-actions">

            <button
                type="button"
                class="btn-secondary"
                id="cancelConfirm"
            >
                Batal
            </button>


            <button
                type="button"
                class="btn-primary"
                id="submitConfirm"
                data-confirm-url="{{ route('student.payment.confirm', $bill->id) }}"
            >
                Ya, Konfirmasi
            </button>

        </div>

    </div>

</div>


</div>

{{-- ==========================================
MODAL SUCCESS
=========================================== --}}

<div
    class="modal-overlay"
    id="successModal"
>


<div class="modal-box">

    <div class="modal-header">

        <h3>
            Konfirmasi Pembayaran
        </h3>


        <button
            type="button"
            class="modal-close"
            id="closeSuccessModal"
        >
            ✕
        </button>

    </div>


    <div class="modal-body">

        <div class="modal-icon success">

            <img
                src="{{ asset('assets/img/success.svg') }}"
                alt="success"
            >

        </div>


        <h2>
            Pembayaran Dikonfirmasi!
        </h2>


        <p>

            Konfirmasi pembayaran Anda telah diterima.
            Admin akan memverifikasi dalam 1×24 jam kerja.

        </p>


        <div class="payment-reference">

            <div class="reference-row">

                <span>
                    No. Referensi
                </span>

                <strong id="paymentReference">
                    -
                </strong>

            </div>


            <div class="reference-row">

                <span>
                    Tanggal
                </span>

                <strong id="paymentDate">
                    -
                </strong>

            </div>

        </div>


        <div class="modal-actions right">

            <button
                type="button"
                class="btn-primary"
                id="viewHistoryBtn"
                data-history-url="{{ route('student.payment-history') }}"
            >
                Lihat Riwayat
            </button>

        </div>

    </div>

</div>


</div>

@push('scripts')


@vite('resources/js/pages/student/payment.js')


@endpush

@endsection
