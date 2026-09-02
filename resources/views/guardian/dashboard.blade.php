@extends('layouts.sikolapayapp')

@section('title', 'Dashboard Orang Tua - SikolaPay')

@section('page-title', 'Dashboard')

@push('styles')
    @vite('resources/css/pages/guardian/dashboard.css')
@endpush

@section('content')

<section class="guardian-dashboard">

    {{-- Header --}}
    <div class="guardian-dashboard-header">

        <h2>
            Dashboard Orang Tua
        </h2>

        <p>
            Pantau tagihan dan pembayaran sekolah anak Anda
        </p>

    </div>


    {{-- Data Anak --}}
    <div class="guardian-students">

        @forelse($students as $student)

            <div class="guardian-student-card">

                <div class="guardian-student-avatar">
                    {{ strtoupper(substr($student->name ?? 'AN', 0, 2)) }}
                </div>


                <div class="guardian-student-info">

                    <span class="guardian-student-label">
                        Data Anak
                    </span>

                    <h3>
                        {{ $student->name }}
                    </h3>

                    <p>
                        {{ $student->classRoom?->name ?? '-' }}
                    </p>

                    <span class="guardian-student-nis">
                        NIS: {{ $student->nis ?? '-' }}
                    </span>

                </div>

            </div>

        @empty

            <div class="guardian-student-empty">

                <p>
                    Belum ada data anak yang terhubung ke akun ini.
                </p>

            </div>

        @endforelse

    </div>


    {{-- Statistik --}}
    <div class="guardian-dashboard-stats">

        <div class="guardian-stat-card">

            <div class="guardian-stat-info">

                <span>
                    Total Tagihan
                </span>

                <strong>
                    {{ $totalBills }}
                </strong>

            </div>

        </div>


        <div class="guardian-stat-card">

            <div class="guardian-stat-info">

                <span>
                    Belum Bayar
                </span>

                <strong>
                    {{ $unpaidBills }}
                </strong>

            </div>

        </div>


        <div class="guardian-stat-card">

            <div class="guardian-stat-info">

                <span>
                    Menunggu Verifikasi
                </span>

                <strong>
                    {{ $pendingBills }}
                </strong>

            </div>

        </div>


        <div class="guardian-stat-card">

            <div class="guardian-stat-info">

                <span>
                    Lunas
                </span>

                <strong>
                    {{ $paidBills }}
                </strong>

            </div>

        </div>

    </div>


    {{-- Tagihan Terbaru --}}
    <div class="guardian-dashboard-card">

        <div class="guardian-dashboard-card-header">

            <div>

                <h3>
                    Tagihan Terbaru
                </h3>

                <p>
                    Daftar tagihan sekolah terbaru anak Anda
                </p>

            </div>


            <a
                href="{{ route('guardian.bills.index') }}"
                class="guardian-see-all"
            >
                Lihat Semua
            </a>

        </div>


        <div class="guardian-dashboard-table-wrapper">

            <table class="guardian-dashboard-table">

                <thead>

                    <tr>

                        <th>
                            Jenis Tagihan
                        </th>

                        <th>
                            Nominal
                        </th>

                        <th>
                            Jatuh Tempo
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($latestBills as $bill)

                        @php

                            $latestPayment = $bill->latestPayment;

                            if ($bill->status === 'paid') {

                                $displayStatus = 'paid';
                                $displayStatusLabel = 'Lunas';

                            } elseif ($latestPayment?->status === 'pending') {

                                $displayStatus = 'pending';
                                $displayStatusLabel = 'Menunggu Verifikasi';

                            } elseif ($latestPayment?->status === 'rejected') {

                                $displayStatus = 'rejected';
                                $displayStatusLabel = 'Ditolak';

                            } else {

                                $displayStatus = 'unpaid';
                                $displayStatusLabel = 'Belum Bayar';

                            }

                        @endphp


                        <tr>

                            {{-- Jenis Tagihan --}}
                            <td>

                                <div class="guardian-latest-bill">

                                    <strong>
                                        {{ $bill->name ?? '-' }}
                                    </strong>

                                    @if(!empty($bill->description))

                                        <span>
                                            {{ $bill->description }}
                                        </span>

                                    @endif

                                </div>

                            </td>


                            {{-- Nominal --}}
                            <td>

                                <strong class="guardian-latest-amount">

                                    Rp {{ number_format(
                                        $bill->amount ?? 0,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </strong>

                            </td>


                            {{-- Jatuh Tempo --}}
                            <td>

                                {{ $bill->due_date
                                    ? \Illuminate\Support\Carbon::parse(
                                        $bill->due_date
                                    )->translatedFormat('d M Y')
                                    : '-'
                                }}

                            </td>


                            {{-- Status --}}
                            <td>

                                <span
                                    class="guardian-latest-status {{ $displayStatus }}"
                                >
                                    {{ $displayStatusLabel }}
                                </span>

                            </td>


                            {{-- Aksi --}}
                            <td>

                                <div class="guardian-latest-actions">


                                    @if($displayStatus === 'paid')

                                        <a
                                            href="{{ route(
                                                'guardian.bills.show',
                                                $bill->id
                                            ) }}"
                                            class="guardian-latest-action"
                                        >
                                            Detail
                                        </a>


                                    @elseif(
                                        $displayStatus === 'pending'
                                        && $latestPayment
                                    )

                                        <a
                                            href="{{ route(
                                                'guardian.payments.show',
                                                $latestPayment->id
                                            ) }}"
                                            class="guardian-latest-action"
                                        >
                                            Lihat Pembayaran
                                        </a>


                                    @elseif($displayStatus === 'rejected')

                                        <a
                                            href="{{ route(
                                                'guardian.payments.create',
                                                $bill->id
                                            ) }}"
                                            class="guardian-latest-action retry"
                                        >
                                            Bayar Lagi
                                        </a>


                                    @else

                                        <a
                                            href="{{ route(
                                                'guardian.bills.show',
                                                $bill->id
                                            ) }}"
                                            class="guardian-latest-action"
                                        >
                                            Detail
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="guardian-dashboard-empty"
                            >

                                <div class="guardian-dashboard-empty-state">

                                    <strong>
                                        Belum ada tagihan
                                    </strong>

                                    <p>
                                        Tagihan sekolah anak akan muncul di sini.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection