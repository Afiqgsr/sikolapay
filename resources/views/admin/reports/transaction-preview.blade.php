<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Laporan Rincian Transaksi - SikolaPay</title>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background-color: #f4f5f7;
            padding: 24px;
        }

        .no-print-bar {
            max-width: 900px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-back {
            background-color: #ffffff;
            color: #4b5563;
            border-color: #d1d5db;
        }

        .btn-back:hover {
            background-color: #f3f4f6;
        }

        .btn-print {
            background-color: #2b3a8f;
            color: #ffffff;
        }

        .btn-print:hover {
            background-color: #1e2968;
        }

        .sheet {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 48px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Kop Sekolah */
        .kop-header {
            display: flex;
            align-items: center;
            border-bottom: 3px double #1a1a1a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .kop-text {
            flex: 1;
        }

        .kop-text h1 {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
            margin-bottom: 2px;
        }

        .kop-text p {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.4;
        }

        /* Judul Laporan */
        .report-title-section {
            text-align: center;
            margin-bottom: 24px;
        }

        .report-title-section h2 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
            margin-bottom: 6px;
        }

        .report-meta-grid {
            display: inline-grid;
            grid-template-columns: auto auto auto auto;
            gap: 6px 16px;
            font-size: 11px;
            text-align: left;
            margin-top: 6px;
            background-color: #f9fafb;
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .report-meta-grid span.label {
            color: #4b5563;
        }

        .report-meta-grid span.val {
            font-weight: 600;
            color: #111827;
        }

        /* Tabel Transaksi */
        table.transaction-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 16px;
        }

        table.transaction-table th,
        table.transaction-table td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            vertical-align: middle;
        }

        table.transaction-table thead th {
            background-color: #f3f4f6;
            font-weight: 700;
            text-align: center;
            color: #111827;
        }

        table.transaction-table td.text-center {
            text-align: center;
        }

        table.transaction-table td.text-right {
            text-align: right;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: #d6ffde;
            color: #00871b;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #925c00;
        }

        .status-unpaid {
            background-color: #f9e0db;
            color: #c53030;
        }

        /* Summary Section */
        .summary-section {
            margin-top: 16px;
            margin-bottom: 32px;
            page-break-inside: avoid;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .summary-table th,
        .summary-table td {
            border: 2px solid #2b3a8f;
            padding: 8px 12px;
            font-weight: 700;
        }

        .summary-table thead th {
            background-color: #e0e2f4;
            color: #1e2968;
            text-align: center;
        }

        .summary-table tbody td {
            background-color: #ffffff;
            color: #111827;
        }

        /* Tanda Tangan */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 250px;
            text-align: center;
            font-size: 11px;
            line-height: 1.5;
        }

        .signature-role {
            font-weight: 600;
            color: #111827;
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
            color: #111827;
        }

        .signature-nip {
            color: #4b5563;
        }

        .empty-alert {
            padding: 32px;
            text-align: center;
            color: #6b7280;
            font-style: italic;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                font-size: 10pt;
            }

            .no-print-bar {
                display: none !important;
            }

            .sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                max-width: 100%;
            }

            table.transaction-table th,
            table.transaction-table td {
                border-color: #333333 !important;
            }

            .summary-table th,
            .summary-table td {
                border-color: #000000 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Aksi Layar (Hidden Saat Print) -->
    <div class="no-print-bar">
        <a href="{{ route('admin.reports.index', array_filter(['tab' => 'detail', 'start_date' => $startDate, 'end_date' => $endDate, 'class_room_id' => $selectedClass?->id, 'status' => $status])) }}" class="btn btn-back">
            ← Kembali ke Laporan
        </a>

        <button type="button" class="btn btn-print" onclick="window.print()">
            Cetak / Simpan PDF
        </button>
    </div>

    <!-- Lembar Cetak Dokumen -->
    <div class="sheet">

        <!-- KOP SEKOLAH -->
        <div class="kop-header">
            <div class="kop-text">
                <h1>{{ $school?->name ?? 'SEKOLAH SIKOLAPAY' }}</h1>
                @if($school?->address)
                    <p>{{ $school->address }}</p>
                @endif
                <p>
                    @if($school?->phone) Telp: {{ $school->phone }} @endif
                    @if($school?->email) | Email: {{ $school->email }} @endif
                    @if($school?->npsn) | NPSN: {{ $school->npsn }} @endif
                </p>
            </div>
        </div>

        <!-- JUDUL LAPORAN & META -->
        <div class="report-title-section">
            <h2>Laporan Rincian Transaksi Pembayaran</h2>

            <div class="report-meta-grid">
                <span class="label">Periode Mulai:</span>
                <span class="val">{{ $startDate ? \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') : 'Semua Periode' }}</span>

                <span class="label">Kelas:</span>
                <span class="val">{{ $selectedClass?->name ?? 'Semua Kelas' }}</span>

                <span class="label">Periode Akhir:</span>
                <span class="val">{{ $endDate ? \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') : 'Semua Periode' }}</span>

                <span class="label">Status:</span>
                <span class="val">
                    @if($status === 'paid')
                        Lunas
                    @elseif($status === 'pending')
                        Menunggu Verifikasi
                    @elseif($status === 'unpaid')
                        Belum Lunas
                    @else
                        Semua Status
                    @endif
                </span>

                <span class="label">Tanggal Cetak:</span>
                <span class="val">{{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB</span>

                <span class="label">Total Data:</span>
                <span class="val">{{ $totalCount }} transaksi</span>
            </div>
        </div>

        @if($reports->isEmpty())
            <div class="empty-alert">
                Tidak ada data laporan transaksi yang ditemukan sesuai filter yang dipilih.
            </div>
        @else
            <!-- TABEL TRANSAKSI -->
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 13%;">Tanggal</th>
                        <th style="width: 22%;">Nama Siswa</th>
                        <th style="width: 13%;">Kelas</th>
                        <th style="width: 22%;">Jenis Tagihan</th>
                        <th style="width: 14%; text-align: right;">Nominal</th>
                        <th style="width: 12%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $index => $bill)
                        @php
                            $payment = $bill->latestPayment;
                            $latestVerification = $payment?->latestVerification;
                            $hasRejectedVerification = $latestVerification?->status === 'rejected';
                            $isResubmitted = $hasRejectedVerification
                                && $payment?->proof_uploaded_at
                                && $latestVerification?->processed_at
                                && $payment->proof_uploaded_at->gt($latestVerification->processed_at);

                            if ($bill->status === 'paid' || $payment?->status === 'paid') {
                                $displayStatus = 'paid';
                                $statusLabel = 'Lunas';
                            } elseif (
                                $payment?->status === 'pending'
                                && $payment?->proof_of_payment
                                && (!$hasRejectedVerification || $isResubmitted)
                            ) {
                                $displayStatus = 'pending';
                                $statusLabel = 'Menunggu';
                            } else {
                                $displayStatus = 'unpaid';
                                $statusLabel = 'Belum Lunas';
                            }

                            $reportDate = $payment?->paid_at ?? $payment?->created_at ?? $bill->created_at;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center">{{ $reportDate ? $reportDate->translatedFormat('d M Y') : '-' }}</td>
                            <td>{{ $bill->student?->name ?? '-' }}</td>
                            <td class="text-center">{{ $bill->student?->classRoom?->name ?? '-' }}</td>
                            <td>{{ $bill->name }}</td>
                            <td class="text-right">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($displayStatus === 'paid')
                                    <span class="status-badge status-paid">{{ $statusLabel }}</span>
                                @elseif($displayStatus === 'pending')
                                    <span class="status-badge status-pending">{{ $statusLabel }}</span>
                                @else
                                    <span class="status-badge status-unpaid">{{ $statusLabel }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- RINGKASAN TOTAL -->
            <div class="summary-section">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Total Transaksi</th>
                            <th style="width: 50%;">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center; font-size: 13px;">
                                {{ $totalCount }} transaksi
                            </td>
                            <td style="text-align: center; font-size: 13px; color: #2b3a8f;">
                                Rp {{ number_format($totalNominal, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- LEMBAR PENGESAHAN TANDA TANGAN -->
            <div class="signature-section">
                <div class="signature-box">
                    <p>Mengetahui,</p>
                    <p class="signature-role">Kepala Sekolah</p>
                    <p class="signature-name">( .................................................... )</p>
                    <p class="signature-nip">NIP. ....................................................</p>
                </div>

                <div class="signature-box">
                    <p>Dibuat oleh,</p>
                    <p class="signature-role">Petugas Keuangan</p>
                    <p class="signature-name">( {{ Auth::user()->name }} )</p>
                    <p class="signature-nip">NIP. ....................................................</p>
                </div>
            </div>
        @endif

    </div>

</body>
</html>
