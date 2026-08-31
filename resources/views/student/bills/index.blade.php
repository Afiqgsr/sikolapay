@extends('layouts.sikolapayapp')

@section('title', 'Tagihan Saya - SikolaPay')

@section('page-title', 'Tagihan Saya')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/student/bills.css') }}">
@endpush

@section('content')

<!-- BILLING CONTENT -->
<section class="billing-content">

    <!-- HEADER -->
    <div class="page-header">

        <h2>Daftar Tagihan</h2>

        <p>
            Kelola semua tagihan sekolah Anda
        </p>

    </div>


    <!-- SUMMARY -->
    <div class="billing-summary">

        <div class="summary-info">

            <span>Total Tagihan Belum Dibayar</span>

            <h3>
                Rp {{ number_format($unpaidTotal, 0, ',', '.') }}
            </h3>

        </div>

        @if($unpaidBills->count() > 0)

            <a
                href="{{ route('student.payment.all') }}"
                class="pay-all-btn"
            >
                Bayar Semua
            </a>

        @endif

    </div>


    <!-- BILLING CARD -->
    <div class="billing-card">


        <!-- FILTER -->
        <div class="billing-filter">

            <a
                href="{{ route('student.bills.index') }}"
                class="filter-btn {{ !$status ? 'active' : '' }}"
            >
                Semua
            </a>


            <a
                href="{{ route('student.bills.index', ['status' => 'unpaid']) }}"
                class="filter-btn {{ $status === 'unpaid' ? 'active' : '' }}"
            >
                Belum Bayar
            </a>


            <a
                href="{{ route('student.bills.index', ['status' => 'pending']) }}"
                class="filter-btn {{ $status === 'pending' ? 'active' : '' }}"
            >
                Menunggu
            </a>


            <a
                href="{{ route('student.bills.index', ['status' => 'paid']) }}"
                class="filter-btn {{ $status === 'paid' ? 'active' : '' }}"
            >
                Lunas
            </a>

        </div>


        <!-- TABLE -->
        <div class="billing-table-wrapper">

            <table class="billing-table">

                <thead>

                    <tr>

                        <th>Jenis Tagihan</th>

                        <th>Keterangan</th>

                        <th>Nominal</th>

                        <th>Jatuh Tempo</th>

                        <th>Status</th>

                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($bills as $bill)

                        <tr>

                            <!-- JENIS TAGIHAN -->
                            <td>
                                {{ $bill->name }}
                            </td>


                            <!-- KETERANGAN -->
                            <td>
                                {{ $bill->description ?? '-' }}
                            </td>


                            <!-- NOMINAL -->
                            <td>
                                Rp {{ number_format($bill->amount, 0, ',', '.') }}
                            </td>


                            <!-- JATUH TEMPO -->
                            <td>

                                @if($bill->due_date)

                                    {{ \Carbon\Carbon::parse($bill->due_date)->translatedFormat('d F Y') }}

                                @else

                                    -

                                @endif

                            </td>


                            <!-- STATUS -->
                            <td>
                                @if($bill->status === 'paid')

                                    <span class="badge success">
                                        Lunas
                                    </span>

                                @elseif($bill->payments->where('status', 'pending')->count() > 0)

                                    <span class="badge pending">
                                        Menunggu
                                    </span>

                                @else

                                    <span class="badge warning">
                                        Belum Bayar
                                    </span>

                                @endif

                            </td>


                           <!-- AKSI -->
                            <td>

                                <div class="billing-actions">

                                    <a
                                        href="{{ route('student.bills.show', $bill->id) }}"
                                        class="btn-detail"
                                    >
                                        Detail
                                    </a>


                                    @if($bill->status === 'paid' && $bill->latestPayment)

                                        <a
                                            href="{{ route('student.payment.receipt', $bill->latestPayment->id) }}"
                                            class="btn-nota"
                                        >
                                            Nota
                                        </a>

                                    @elseif($bill->latestPayment?->status === 'pending')

                                        <span class="btn-pending">
                                            Menunggu
                                        </span>

                                    @else

                                        <a
                                            href="{{ route('student.payment', $bill->id) }}"
                                            class="btn-pay"
                                        >
                                            Bayar
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="empty-state"
                            >
                                Tidak ada tagihan.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection