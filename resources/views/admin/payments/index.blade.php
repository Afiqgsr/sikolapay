@extends('layouts.sikolapayapp')

@section('title', 'Verifikasi Pembayaran Admin - SikolaPay')

@section('page-title', 'Verifikasi Pembayaran')

@section('content')

<section class="verification-page">

    {{-- Header --}}
    <div class="verification-header">

        <div class="verification-header-info">

            <h2>Verifikasi Pembayaran</h2>

            <p>
                Tinjau dan verifikasi bukti pembayaran dari siswa
            </p>

        </div>

    </div>

    {{-- Info --}}
    <div class="verification-info">

        <img
            src="{{ asset('assets/img/info_light-admin.svg') }}"
            alt=""
        >

        <span>
            Terdapat
            <strong>{{ $payments->count() }}</strong>
            pembayaran yang menunggu verifikasi Anda.
        </span>

    </div>

    {{-- Table --}}
    <div class="verification-card">

        <div class="verification-table-wrapper">

            <table class="verification-table">

                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-name">Nama Siswa</th>
                        <th class="col-class">Kelas</th>
                        <th class="col-bill">Jenis Tagihan</th>
                        <th class="col-nominal">Nominal</th>
                        <th class="col-date">Tanggal Bayar</th>
                        <th class="col-proof">Bukti</th>
                        <th class="col-action">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($payments as $payment)

                        <tr>

                            <td class="col-no">
                                {{ $loop->iteration }}
                            </td>

                            <td class="col-name">
                                {{ $payment->bill->student->name }}
                            </td>

                            <td class="col-class">
                                {{ $payment->bill->student->classRoom?->name ?? '-' }}
                            </td>

                            <td class="col-bill">
                                {{ $payment->bill->name }}
                            </td>

                            <td class="col-nominal">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>

                            <td class="col-date">

                                {{ optional(
                                    $payment->proof_uploaded_at
                                        ?? $payment->created_at
                                )->format('d M Y') }}

                            </td>

                            <td class="col-proof">

                                <button
                                    type="button"
                                    class="btn-view-proof"
                                    data-payment-id="{{ $payment->id }}"
                                    data-student="{{ $payment->bill->student->name }}"
                                    data-bill="{{ $payment->bill->name }}"
                                    data-amount="Rp {{ number_format($payment->amount, 0, ',', '.') }}"
                                    data-date="{{ optional(
                                        $payment->proof_uploaded_at
                                            ?? $payment->created_at
                                    )->format('d M Y') }}"
                                    data-proof="{{ asset('storage/' . $payment->proof_of_payment) }}"
                                >

                                    <img
                                        src="{{ asset('assets/img/eye-admin.svg') }}"
                                        alt="lihat bukti"
                                    >

                                    <span>
                                        Lihat Bukti
                                    </span>

                                </button>

                            </td>

                            <td class="col-action">

                                <div class="verification-actions">

                                    <button
                                        type="button"
                                        class="btn-approve"
                                        data-payment-id="{{ $payment->id }}"
                                        data-student="{{ $payment->bill->student->name }}"
                                        data-bill="{{ $payment->bill->name }}"
                                    >
                                        Terima
                                    </button>

                                    <button
                                        type="button"
                                        class="btn-reject"
                                        data-payment-id="{{ $payment->id }}"
                                        data-student="{{ $payment->bill->student->name }}"
                                        data-bill="{{ $payment->bill->name }}"
                                    >
                                        Tolak
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="verification-empty"
                            >
                                Tidak ada pembayaran yang menunggu verifikasi.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

{{-- Modal bukti pembayaran --}}

<div
    class="payment-proof-overlay"
    id="paymentProofModal"
>

    <div class="payment-proof-modal">

        <div class="payment-proof-header">

            <h2 id="paymentProofTitle">
                Bukti Pembayaran
            </h2>

            <button
                type="button"
                class="payment-proof-close"
                id="paymentProofClose"
            >
                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >
            </button>

        </div>

        <div class="payment-proof-content">

            <div class="payment-proof-info">

                <div class="payment-proof-item">

                    <span class="payment-proof-label">
                        Jenis Tagihan
                    </span>

                    <span
                        class="payment-proof-value"
                        id="paymentProofBill"
                    >
                        -
                    </span>

                </div>

                <div class="payment-proof-item">

                    <span class="payment-proof-label">
                        Nominal
                    </span>

                    <span
                        class="payment-proof-value"
                        id="paymentProofAmount"
                    >
                        -
                    </span>

                </div>

                <div class="payment-proof-item">

                    <span class="payment-proof-label">
                        Tanggal Bayar
                    </span>

                    <span
                        class="payment-proof-value"
                        id="paymentProofDate"
                    >
                        -
                    </span>

                </div>

            </div>

            <div class="payment-proof-image-section">

                <div class="payment-proof-image-title">
                    Foto Bukti Transfer
                </div>

                <div class="payment-proof-image-wrapper">

                    <img
                        src=""
                        alt="Bukti Pembayaran"
                        class="payment-proof-image"
                        id="paymentProofImage"
                    >

                </div>

            </div>

        </div>

        <div class="payment-proof-footer">

            <button
                type="button"
                class="payment-proof-btn payment-proof-btn-close"
                id="paymentProofBtnClose"
            >
                Tutup
            </button>

            <button
                type="button"
                class="payment-proof-btn payment-proof-btn-reject"
                id="paymentProofReject"
            >
                Tolak
            </button>

            <button
                type="button"
                class="payment-proof-btn payment-proof-btn-approve"
                id="paymentProofApprove"
            >
                Terima
            </button>

        </div>

    </div>

</div>

{{-- Modal terima pembayaran --}}

<div
    class="payment-approve-overlay"
    id="paymentApproveModal"
>

    <div class="payment-approve-modal">

        <div class="payment-approve-header">

            <h2>
                Terima Pembayaran
            </h2>

            <button
                type="button"
                class="payment-approve-close"
                id="paymentApproveClose"
            >
                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >
            </button>

        </div>

        <div class="payment-approve-content">

            <div class="payment-approve-warning">

                <img
                    src="{{ asset('assets/img/caution-green-admin.svg') }}"
                    alt=""
                >

                <p>
                    Status tagihan akan berubah menjadi Lunas.
                </p>

            </div>

            <div class="payment-approve-question">

                <p>
                    Terima pembayaran

                    <strong id="approveBillName">
                        -
                    </strong>

                    dari siswa

                    <strong id="approveStudentName">
                        -
                    </strong>

                    ?
                </p>

            </div>

        </div>

        <div class="payment-approve-footer">

            <button
                type="button"
                class="payment-approve-btn payment-approve-btn-cancel"
                id="paymentApproveCancel"
            >
                Batal
            </button>

            <form
                method="POST"
                id="paymentApproveForm"
            >
                @csrf

                <button
                    type="submit"
                    class="payment-approve-btn payment-approve-btn-confirm"
                >
                    Konfirmasi Terima
                </button>

            </form>

        </div>

    </div>

</div>

{{-- Modal tolak pembayaran --}}

<div
    class="payment-reject-overlay"
    id="paymentRejectModal"
>

    <div class="payment-reject-modal">

        <div class="payment-reject-header">

            <h2>
                Tolak Pembayaran
            </h2>

            <button
                type="button"
                class="payment-reject-close"
                id="paymentRejectClose"
            >
                <img
                    src="{{ asset('assets/img/close-admin.svg') }}"
                    alt="Tutup"
                >
            </button>

        </div>

        <div class="payment-reject-content">

            <div class="payment-reject-warning">

                <img
                    src="{{ asset('assets/img/caution-admin.svg') }}"
                    alt=""
                >

                <p>
                    Siswa akan diminta mengulang pembayaran.
                </p>

            </div>

            <div class="payment-reject-question">

                <p>
                    Tolak pembayaran

                    <strong id="rejectBillName">
                        -
                    </strong>

                    dari

                    <strong id="rejectStudentName">
                        -
                    </strong>

                    ?
                </p>

            </div>

            <form
                method="POST"
                id="paymentRejectForm"
                class="payment-reject-form"
            >
                @csrf

                <div class="payment-reject-input-wrapper">

                    <label
                        for="paymentRejectReason"
                        class="payment-reject-label"
                    >
                        Alasan Penolakan
                    </label>

                    <textarea
                        name="note"
                        id="paymentRejectReason"
                        class="payment-reject-input"
                        placeholder="Masukkan alasan penolakan..."
                        required
                    ></textarea>

                </div>

            </form>

        </div>

        <div class="payment-reject-footer">

            <button
                type="button"
                class="payment-reject-btn payment-reject-btn-cancel"
                id="paymentRejectCancel"
            >
                Batal
            </button>

            <button
                type="submit"
                form="paymentRejectForm"
                class="payment-reject-btn payment-reject-btn-confirm"
            >
                Tolak Pembayaran
            </button>

        </div>

    </div>

</div>

@push('scripts')

    @vite('resources/js/pages/admin/verifikasi-pembayaran-admin.js')

@endpush

@endsection