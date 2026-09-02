@extends('layouts.sikolapayapp')

@section('title', 'Detail Tagihan - SikolaPay')

@section('page-title', 'Detail Tagihan')

@section('content')

<section class="guardian-bill-detail-page">

    <div class="guardian-detail-header">

        <button
            type="button"
            class="guardian-back-button"
            onclick="history.back()"
        >
            ← Kembali
        </button>

        <div>
            <h2>Detail Tagihan</h2>

            <p>
                Informasi lengkap tagihan sekolah anak Anda
            </p>
        </div>

    </div>


    <div class="guardian-detail-layout">

        <div class="guardian-detail-card">

            <div class="guardian-detail-card-header">

                <div>
                    <span class="guardian-detail-label">
                        Jenis Tagihan
                    </span>

                    <h3>
                        {{ $bill->name ?? '-' }}
                    </h3>
                </div>


                @if($bill->status === 'paid')

                    <span class="guardian-detail-status paid">
                        Lunas
                    </span>

                @elseif($bill->status === 'pending')

                    <span class="guardian-detail-status pending">
                        Menunggu Verifikasi
                    </span>

                @else

                    <span class="guardian-detail-status unpaid">
                        Belum Bayar
                    </span>

                @endif

            </div>


            <div class="guardian-detail-section">

                <h4>Data Siswa</h4>

                <div class="guardian-detail-row">

                    <span>Nama Siswa</span>

                    <strong>
                        {{ $bill->student?->name ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>NIS</span>

                    <strong>
                        {{ $bill->student?->nis ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>NISN</span>

                    <strong>
                        {{ $bill->student?->nisn ?? '-' }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>Kelas</span>

                    <strong>
                        {{ $bill->student?->classRoom?->name ?? '-' }}
                    </strong>

                </div>

            </div>


            <div class="guardian-detail-section">

                <h4>Informasi Tagihan</h4>

                <div class="guardian-detail-row">

                    <span>Nominal</span>

                    <strong>
                        Rp {{ number_format($bill->amount ?? 0, 0, ',', '.') }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>Jatuh Tempo</span>

                    <strong>
                        {{ $bill->due_date
                            ? \Illuminate\Support\Carbon::parse($bill->due_date)->format('d M Y')
                            : '-'
                        }}
                    </strong>

                </div>


                <div class="guardian-detail-row">

                    <span>Status</span>

                    <strong>
                        @if($bill->status === 'paid')
                            Lunas
                        @elseif($bill->status === 'pending')
                            Menunggu Verifikasi
                        @else
                            Belum Bayar
                        @endif
                    </strong>

                </div>

            </div>

        </div>


        <aside class="guardian-detail-summary">

            <h3>Ringkasan Tagihan</h3>

            <div class="guardian-summary-row">

                <span>Total Tagihan</span>

                <strong>
                    Rp {{ number_format($bill->amount ?? 0, 0, ',', '.') }}
                </strong>

            </div>


            @if($bill->status === 'unpaid')

                <a
                    href="{{ route('guardian.payments.create', $bill->id) }}"
                    class="guardian-pay-button"
                >
                    Bayar Sekarang
                </a>

            @elseif($bill->status === 'pending')

                <div class="guardian-waiting-box">
                    Pembayaran sedang menunggu verifikasi admin.
                </div>

            @else

                <div class="guardian-paid-box">
                    Tagihan ini sudah lunas.
                </div>

            @endif

        </aside>

    </div>

</section>

@endsection