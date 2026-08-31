@extends('layouts.sikolapayapp')

@section('title', 'Pembayaran Semua Tagihan - SikolaPay')

@section('page-title', 'Pembayaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/student/payment.css') }}">
@endpush

@section('content')

<section class="payment-page payment-all-page">

    <button
        type="button"
        class="back-button"
        onclick="history.back()"
    >
        <img src="{{ asset('assets/img/back.svg') }}" alt="">
        <span>Kembali</span>
    </button>


    <div class="payment-header">

        <h2>Bayar Semua Tagihan</h2>

        <p>
            Periksa seluruh tagihan sebelum melakukan pembayaran.
        </p>

    </div>


    <div class="payment-main-grid">

        <!-- LEFT -->
        <div class="payment-left-column">

            <div class="summary-card">

                <div class="summary-header">
                    <h3>Daftar Tagihan</h3>
                </div>

                <div class="summary-content">

                    @foreach($unpaidBills as $bill)

                        <div class="summary-row">

                            <span>
                                {{ $bill->name }}
                            </span>

                            <span>
                                Rp {{ number_format($bill->amount, 0, ',', '.') }}
                            </span>

                        </div>

                    @endforeach

                </div>

            </div>

        </div>


        <!-- RIGHT -->
        <div class="payment-right-column">

            <div class="summary-card">

                <div class="summary-header">

                    <h3>Total Pembayaran</h3>

                </div>

                <div class="summary-content">

                    <div class="summary-total">

                        <div>

                            <span>Total</span>

                            <strong>
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </strong>

                        </div>

                    </div>

                    <div class="confirmation">

                        <a
                            href="{{ route('student.payment.all.confirm') }}"
                            class="payment-confirm-button"
                        >
                            Lanjutkan Pembayaran
                        </a>

                        <p>
                            Pastikan seluruh data tagihan sudah benar
                            sebelum melanjutkan pembayaran.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection