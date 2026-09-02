@extends('layouts.sikolapayapp')

@section('title', 'Tagihan Anak - SikolaPay')

@section('page-title', 'Tagihan Anak')

@push('styles')
    @vite('resources/css/pages/guardian/bills.css')
@endpush

@section('content')

<section class="guardian-bills-page">

    {{-- HEADER --}}
    <div class="guardian-bills-header">

        <div class="guardian-bills-header-info">

            <h2>
                Tagihan Anak
            </h2>

            <p>
                Lihat dan kelola tagihan sekolah anak Anda
            </p>

        </div>

    </div>


    {{-- FILTER --}}
    <div class="guardian-bills-filter-card">

        <form
            method="GET"
            action="{{ route('guardian.bills.index') }}"
            class="guardian-bills-filter"
        >

            {{-- PILIH ANAK --}}
            <div class="guardian-filter-group">

                <label for="student">
                    Anak
                </label>

                <select
                    name="student"
                    id="student"
                    class="guardian-filter-select"
                >

                    <option value="">
                        Semua Anak
                    </option>

                    @foreach($students as $student)

                        <option
                            value="{{ $student->id }}"
                            {{ request('student') == $student->id ? 'selected' : '' }}
                        >
                            {{ $student->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- STATUS --}}
            <div class="guardian-filter-group">

                <label for="status">
                    Status
                </label>

                <select
                    name="status"
                    id="status"
                    class="guardian-filter-select"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="unpaid"
                        {{ request('status') === 'unpaid' ? 'selected' : '' }}
                    >
                        Belum Bayar
                    </option>

                    <option
                        value="pending"
                        {{ request('status') === 'pending' ? 'selected' : '' }}
                    >
                        Menunggu Verifikasi
                    </option>

                    <option
                        value="paid"
                        {{ request('status') === 'paid' ? 'selected' : '' }}
                    >
                        Lunas
                    </option>

                    <option
                        value="rejected"
                        {{ request('status') === 'rejected' ? 'selected' : '' }}
                    >
                        Ditolak
                    </option>

                </select>

            </div>


            {{-- SEARCH --}}
            <div class="guardian-filter-group guardian-filter-search">

                <label for="search">
                    Cari
                </label>

                <div class="guardian-search-wrapper">

                    <img
                        src="{{ asset('assets/img/search-black-superadmin.svg') }}"
                        alt=""
                    >

                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Cari tagihan, nama anak, NIS..."
                    >

                </div>

            </div>


            {{-- BUTTON --}}
            <div class="guardian-filter-actions">

                <button
                    type="submit"
                    class="guardian-filter-button"
                >
                    Terapkan
                </button>


                @if(
                    request()->filled('student')
                    || request()->filled('status')
                    || request()->filled('search')
                )

                    <a
                        href="{{ route('guardian.bills.index') }}"
                        class="guardian-reset-button"
                    >
                        Reset
                    </a>

                @endif

            </div>

        </form>

    </div>


    {{-- TABLE CARD --}}
    <div class="guardian-bills-card">

        <div class="guardian-bills-card-header">

            <div>

                <h3>
                    Daftar Tagihan
                </h3>

                <p>
                    Daftar seluruh tagihan sekolah anak Anda
                </p>

            </div>

        </div>


        <div class="guardian-bills-table-wrapper">

            <table class="guardian-bills-table">

                <thead>

                    <tr>

                        <th>
                            Anak
                        </th>

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

                    @forelse($bills as $bill)

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

                            {{-- ANAK --}}
                            <td>

                                <div class="guardian-student-cell">

                                    <div class="guardian-student-name">

                                        {{ $bill->student?->name ?? '-' }}

                                    </div>


                                    <div class="guardian-student-class">

                                        {{ $bill->student?->classRoom?->name ?? '-' }}

                                    </div>

                                </div>

                            </td>


                            {{-- TAGIHAN --}}
                            <td>

                                <div class="guardian-bill-name">

                                    {{ $bill->name ?? '-' }}

                                </div>

                                @if(!empty($bill->description))

                                    <div class="guardian-bill-description">

                                        {{ $bill->description }}

                                    </div>

                                @endif

                            </td>


                            {{-- NOMINAL --}}
                            <td>

                                <strong class="guardian-bill-amount">

                                    Rp {{ number_format(
                                        $bill->amount ?? 0,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </strong>

                            </td>


                            {{-- JATUH TEMPO --}}
                            <td>

                                {{ $bill->due_date
                                    ? \Illuminate\Support\Carbon::parse(
                                        $bill->due_date
                                    )->translatedFormat('d M Y')
                                    : '-'
                                }}

                            </td>


                            {{-- STATUS --}}
                            <td>

                                <span
                                    class="guardian-bill-status {{ $displayStatus }}"
                                >
                                    {{ $displayStatusLabel }}
                                </span>

                            </td>


                            {{-- AKSI --}}
                            <td>

                                <div class="guardian-bill-actions">


                                    {{-- LUNAS --}}
                                    @if($displayStatus === 'paid')

                                        <a
                                            href="{{ route(
                                                'guardian.bills.show',
                                                $bill->id
                                            ) }}"
                                            class="guardian-bill-action"
                                        >
                                            Detail
                                        </a>


                                    {{-- PENDING --}}
                                    @elseif(
                                        $displayStatus === 'pending'
                                        && $latestPayment
                                    )

                                        <a
                                            href="{{ route(
                                                'guardian.payments.show',
                                                $latestPayment->id
                                            ) }}"
                                            class="guardian-bill-action"
                                        >
                                            Lihat Pembayaran
                                        </a>


                                    {{-- DITOLAK --}}
                                    @elseif($displayStatus === 'rejected')

                                        <a
                                            href="{{ route(
                                                'guardian.payments.create',
                                                $bill->id
                                            ) }}"
                                            class="guardian-bill-action retry"
                                        >
                                            Bayar Lagi
                                        </a>


                                    {{-- BELUM BAYAR --}}
                                    @else

                                        <a
                                            href="{{ route(
                                                'guardian.bills.show',
                                                $bill->id
                                            ) }}"
                                            class="guardian-bill-action"
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
                                colspan="6"
                                class="guardian-bills-empty"
                            >

                                <div class="guardian-empty-state">

                                    <strong>
                                        Belum ada tagihan
                                    </strong>

                                    <p>
                                        Tidak ada tagihan yang sesuai dengan
                                        filter atau pencarian Anda.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if(
            method_exists($bills, 'links')
            && $bills->hasPages()
        )

            <div class="guardian-bills-pagination">

                {{ $bills->links() }}

            </div>

        @endif

    </div>

</section>

@endsection