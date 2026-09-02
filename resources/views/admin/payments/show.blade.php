@extends('layouts.sikolapayapp')

@section('title', 'Detail Pembayaran - SikolaPay')

@section('page-title', 'Detail Pembayaran')

@section('content')

@php
    $latestVerification = $payment->latestVerification;

    $isRejected =
        $latestVerification?->status === 'rejected';

    $isResubmitted =
        $isRejected
        && $payment->proof_uploaded_at
        && $latestVerification?->processed_at
        && $payment->proof_uploaded_at->gt(
            $latestVerification->processed_at
        );

    $isWaitingVerification =
        $payment->status === 'pending'
        && (!$isRejected || $isResubmitted);

    $student = $payment->bill?->student;

    $proofExtension = $payment->proof_of_payment
        ? strtolower(
            pathinfo(
                $payment->proof_of_payment,
                PATHINFO_EXTENSION
            )
        )
        : null;

    $isImageProof = in_array(
        $proofExtension,
        ['jpg', 'jpeg', 'png']
    );
@endphp

<section class="payment-detail-page">

    {{-- Header --}}
    <div class="payment-detail-header">

        <div class="payment-detail-header-left">

            <a
                href="{{ route('admin.dashboard') }}"
                class="payment-back"
            >
                ← Kembali ke Dashboard
            </a>

            <h2>
                Detail Pembayaran
            </h2>

            <p>
                Informasi lengkap transaksi pembayaran siswa
            </p>

        </div>

        <div class="payment-header-status">

            @if($payment->status === 'paid')

                <span class="status success">
                    Lunas
                </span>

            @elseif($isRejected && !$isResubmitted)

                <span class="status rejected">
                    Ditolak
                </span>

            @elseif($isResubmitted)

                <span class="status pending">
                    Menunggu Verifikasi Ulang
                </span>

            @elseif($payment->status === 'pending')

                <span class="status pending">
                    Menunggu Verifikasi
                </span>

            @else

                <span class="status">
                    {{ ucfirst($payment->status) }}
                </span>

            @endif

            <span class="payment-number">
                #{{ $payment->payment_number }}
            </span>

        </div>

    </div>

    {{-- Informasi siswa dan pembayaran --}}
    <div class="payment-detail-grid">

        {{-- Informasi siswa --}}
        <section class="detail-card">

            <div class="detail-card-header">

                <h3>
                    Informasi Siswa
                </h3>

            </div>

            <div class="detail-info-list">

                <div class="detail-info-item">

                    <span>
                        Nama Siswa
                    </span>

                    <strong>
                        {{ $student?->name ?? '-' }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        NIS
                    </span>

                    <strong>
                        {{ $student?->nis ?? '-' }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        Kelas
                    </span>

                    <strong>
                        {{ $student?->classRoom?->name ?? '-' }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        Tahun Ajaran
                    </span>

                    <strong>
                        {{ $student?->classRoom?->academicYear?->name ?? '-' }}
                    </strong>

                </div>

            </div>

        </section>

        {{-- Informasi pembayaran --}}
        <section class="detail-card">

            <div class="detail-card-header">

                <h3>
                    Informasi Pembayaran
                </h3>

            </div>

            <div class="detail-info-list">

                <div class="detail-info-item">

                    <span>
                        Nomor Pembayaran
                    </span>

                    <strong>
                        #{{ $payment->payment_number }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        Jenis Tagihan
                    </span>

                    <strong>
                        {{ $payment->bill?->name ?? '-' }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        Nominal
                    </span>

                    <strong class="payment-detail-amount">
                        Rp {{ number_format(
                            $payment->amount,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        Metode Pembayaran
                    </span>

                    <strong>
                        {{ $payment->paymentMethod?->name ?? '-' }}
                    </strong>

                </div>

                <div class="detail-info-item">

                    <span>
                        Tanggal Pembayaran
                    </span>

                    <strong>
                        {{ $payment->created_at
                            ->translatedFormat('d F Y, H:i') }}
                    </strong>

                </div>

                @if($payment->proof_uploaded_at)

                    <div class="detail-info-item">

                        <span>
                            Bukti Terakhir Diunggah
                        </span>

                        <strong>
                            {{ $payment->proof_uploaded_at
                                ->translatedFormat('d F Y, H:i') }}
                        </strong>

                    </div>

                @endif

            </div>

        </section>

    </div>

    {{-- Riwayat pembayaran --}}
    <section class="detail-card payment-history-card">

        <div class="detail-card-header">

            <div>

                <h3>
                    Riwayat Pembayaran
                </h3>

                <p>
                    Riwayat aktivitas pembayaran
                </p>

            </div>

        </div>

        <div class="payment-history">

            {{-- Pembayaran pertama kali dibuat --}}
            <div class="history-item">

                <div class="history-indicator">
                    •
                </div>

                <div class="history-content">

                    <strong>
                        Pembayaran dilakukan
                    </strong>

                    <span>
                        {{ $payment->created_at
                            ->translatedFormat('d F Y, H:i') }}
                    </span>

                    <small>
                        Siswa mengirim pembayaran menggunakan
                        {{ $payment->paymentMethod?->name ?? 'metode pembayaran' }}
                    </small>

                </div>

            </div>

            {{-- Riwayat verifikasi --}}
            @foreach(
                $payment->verifications->sortBy('processed_at')
                as $verification
            )

                @if($verification->status === 'verified')

                    <div class="history-item">

                        <div class="history-indicator success">
                            ✓
                        </div>

                        <div class="history-content">

                            <strong>
                                Pembayaran berhasil diverifikasi
                            </strong>

                            <span>
                                @if($verification->processed_at)

                                    {{ $verification->processed_at
                                        ->translatedFormat('d F Y, H:i') }}

                                @else

                                    -

                                @endif
                            </span>

                            <small>
                                Diverifikasi oleh Admin
                                #{{ $verification->admin_id }}
                            </small>

                        </div>

                    </div>

                @elseif($verification->status === 'rejected')

                    <div class="history-item">

                        <div class="history-indicator rejected">
                            ×
                        </div>

                        <div class="history-content">

                            <strong>
                                Bukti pembayaran ditolak
                            </strong>

                            <span>
                                @if($verification->processed_at)

                                    {{ $verification->processed_at
                                        ->translatedFormat('d F Y, H:i') }}

                                @else

                                    -

                                @endif
                            </span>

                            @if($verification->note)

                                <small>
                                    {{ $verification->note }}
                                </small>

                            @endif

                        </div>

                    </div>

                @endif

            @endforeach

            {{-- Upload ulang --}}
            @if($isResubmitted)

                <div class="history-item">

                    <div class="history-indicator">
                        •
                    </div>

                    <div class="history-content">

                        <strong>
                            Bukti pembayaran diunggah ulang
                        </strong>

                        <span>
                            {{ $payment->proof_uploaded_at
                                ->translatedFormat('d F Y, H:i') }}
                        </span>

                        <small>
                            Siswa mengirim bukti pembayaran baru
                            setelah penolakan sebelumnya.
                        </small>

                    </div>

                </div>

            @endif

        </div>

    </section>

    {{-- Bukti pembayaran --}}
    <section class="detail-card payment-proof-card">

        <div class="detail-card-header">

            <div>

                <h3>
                    Bukti Pembayaran
                </h3>

                <p>
                    Bukti pembayaran terbaru yang diunggah oleh siswa
                </p>

            </div>

        </div>

        <div class="payment-proof-container">

            @if($payment->proof_of_payment)

                @if($isImageProof)

                    <div class="payment-proof-image">

                        <a
                            href="{{ asset(
                                'storage/' .
                                $payment->proof_of_payment
                            ) }}"
                            target="_blank"
                        >

                            <img
                                src="{{ asset(
                                    'storage/' .
                                    $payment->proof_of_payment
                                ) }}"
                                alt="Bukti Pembayaran"
                            >

                        </a>

                    </div>

                @elseif($proofExtension === 'pdf')

                    <div class="payment-proof-file">

                        <strong>
                            Bukti pembayaran berupa file PDF
                        </strong>

                        <a
                            href="{{ asset(
                                'storage/' .
                                $payment->proof_of_payment
                            ) }}"
                            target="_blank"
                            class="payment-proof-link"
                        >
                            Buka Bukti Pembayaran
                        </a>

                    </div>

                @else

                    <div class="payment-proof-file">

                        <strong>
                            File bukti pembayaran tersedia
                        </strong>

                        <a
                            href="{{ asset(
                                'storage/' .
                                $payment->proof_of_payment
                            ) }}"
                            target="_blank"
                            class="payment-proof-link"
                        >
                            Buka Bukti Pembayaran
                        </a>

                    </div>

                @endif

            @else

                <div class="payment-proof-empty">
                    Bukti pembayaran belum tersedia.
                </div>

            @endif

        </div>

    </section>

    {{-- Informasi verifikasi --}}
    <section class="detail-card verification-info-card">

        <div class="detail-card-header">

            <h3>
                Informasi Verifikasi
            </h3>

        </div>

        <div class="detail-info-list">

            <div class="detail-info-item">

                <span>
                    Status
                </span>

                @if($payment->status === 'paid')

                    <strong class="text-success">
                        Lunas
                    </strong>

                @elseif($isRejected && !$isResubmitted)

                    <strong class="text-rejected">
                        Ditolak
                    </strong>

                @elseif($isResubmitted)

                    <strong class="text-pending">
                        Menunggu Verifikasi Ulang
                    </strong>

                @elseif($payment->status === 'pending')

                    <strong class="text-pending">
                        Menunggu Verifikasi
                    </strong>

                @else

                    <strong>
                        {{ ucfirst($payment->status) }}
                    </strong>

                @endif

            </div>

            <div class="detail-info-item">

                <span>
                    Diproses Oleh
                </span>

                <strong>

                    @if($latestVerification)

                        Admin #{{ $latestVerification->admin_id }}

                    @else

                        -

                    @endif

                </strong>

            </div>

            <div class="detail-info-item">

                <span>
                    Tanggal Verifikasi
                </span>

                <strong>

                    @if($latestVerification?->processed_at)

                        {{ $latestVerification->processed_at
                            ->translatedFormat('d F Y, H:i') }}

                    @else

                        -

                    @endif

                </strong>

            </div>

        </div>

    </section>

    {{-- Alasan penolakan --}}
    @if($isRejected)

        <section class="detail-card rejection-card">

            <div class="detail-card-header">

                <h3>
                    Alasan Penolakan
                </h3>

            </div>

            <div class="rejection-reason">

                {{ $latestVerification?->note
                    ?? 'Tidak ada catatan penolakan.' }}

            </div>

            @if($isResubmitted)

                <div class="rejection-resubmitted">

                    Siswa telah mengunggah bukti pembayaran baru
                    setelah penolakan sebelumnya.

                    Silakan tinjau bukti terbaru sebelum melakukan
                    verifikasi ulang.

                </div>

            @endif

        </section>

    @endif

    {{-- Aksi verifikasi --}}
    @if($isWaitingVerification)

        <section class="detail-card payment-verification-action-card">

            <div class="detail-card-header">

                <div>

                    <h3>
                        Verifikasi Pembayaran
                    </h3>

                    <p>
                        Periksa bukti pembayaran sebelum memberikan keputusan
                    </p>

                </div>

            </div>

            <div class="payment-verification-actions">

                <button
                    type="button"
                    class="detail-reject-button"
                    id="openRejectDetailModal"
                >
                    Tolak Pembayaran
                </button>

                <form
                    method="POST"
                    action="{{ route(
                        'admin.payments.verify',
                        $payment->id
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="detail-approve-button"
                    >
                        @if($isResubmitted)

                            Terima Bukti Terbaru

                        @else

                            Terima Pembayaran

                        @endif
                    </button>

                </form>

            </div>

        </section>

        {{-- Modal penolakan --}}
        <div
            class="detail-reject-overlay"
            id="detailRejectModal"
        >

            <div class="detail-reject-modal">

                <div class="detail-reject-header">

                    <h3>
                        Tolak Pembayaran
                    </h3>

                    <button
                        type="button"
                        id="closeRejectDetailModal"
                    >
                        ×
                    </button>

                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'admin.payments.reject',
                        $payment->id
                    ) }}"
                >
                    @csrf

                    <div class="detail-reject-content">

                        <label for="detailRejectReason">
                            Alasan Penolakan
                        </label>

                        <textarea
                            name="note"
                            id="detailRejectReason"
                            placeholder="Contoh: Bukti pembayaran kurang jelas. Silakan upload ulang bukti yang lebih jelas."
                            required
                        ></textarea>

                        <small>
                            Jelaskan alasan penolakan agar siswa mengetahui
                            tindakan yang perlu dilakukan.
                        </small>

                    </div>

                    <div class="detail-reject-footer">

                        <button
                            type="button"
                            class="detail-reject-cancel"
                            id="cancelRejectDetailModal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="detail-reject-confirm"
                        >
                            Tolak Pembayaran
                        </button>

                    </div>

                </form>

            </div>

        </div>

    @endif

</section>

@if($isWaitingVerification)

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const modal =
                document.getElementById('detailRejectModal');

            const openButton =
                document.getElementById('openRejectDetailModal');

            const closeButton =
                document.getElementById('closeRejectDetailModal');

            const cancelButton =
                document.getElementById('cancelRejectDetailModal');

            function openModal() {
                if (!modal) {
                    return;
                }

                modal.classList.add('active');
            }

            function closeModal() {
                if (!modal) {
                    return;
                }

                modal.classList.remove('active');
            }

            openButton?.addEventListener(
                'click',
                openModal
            );

            closeButton?.addEventListener(
                'click',
                closeModal
            );

            cancelButton?.addEventListener(
                'click',
                closeModal
            );

            modal?.addEventListener(
                'click',
                function (event) {

                    if (event.target === modal) {
                        closeModal();
                    }

                }
            );

            document.addEventListener(
                'keydown',
                function (event) {

                    if (event.key === 'Escape') {
                        closeModal();
                    }

                }
            );

        });
    </script>

@endif

@endsection