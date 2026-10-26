<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Laporan Tagihan Siswa - {{ $recap['student']->name }} - {{ $recap['period']['formatted_period'] }}</title>
    
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
            max-width: 850px;
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
            max-width: 850px;
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
            margin-bottom: 12px;
        }

        .report-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 24px;
            font-size: 12px;
            text-align: left;
            background-color: #f9fafb;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .report-meta-item {
            display: flex;
            gap: 8px;
        }

        .report-meta-item span.label {
            color: #4b5563;
            min-width: 110px;
        }

        .report-meta-item span.val {
            font-weight: 600;
            color: #111827;
        }

        /* Tabel Tagihan */
        table.recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        table.recap-table th,
        table.recap-table td {
            border: 1px solid #9ca3af;
            padding: 7px 10px;
            vertical-align: middle;
        }

        table.recap-table thead th {
            background-color: #f3f4f6;
            font-weight: 700;
            text-align: center;
            color: #111827;
        }

        table.recap-table td.text-center {
            text-align: center;
        }

        table.recap-table td.text-right {
            text-align: right;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
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

        /* Summary Table */
        .summary-section {
            margin-top: 16px;
            margin-bottom: 32px;
            page-break-inside: avoid;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
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
                font-size: 11pt;
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

            table.recap-table th,
            table.recap-table td {
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
        <a href="{{ route('admin.reports.index', ['tab' => 'student_recap', 'month' => $recap['period']['month'], 'year' => $recap['period']['year'], 'bill_name' => $recap['period']['bill_name']]) }}" class="btn btn-back">
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
                <h1>{{ $recap['school']?->name ?? 'SEKOLAH SIKOLAPAY' }}</h1>
                @if($recap['school']?->address)
                    <p>{{ $recap['school']->address }}</p>
                @endif
                <p>
                    @if($recap['school']?->phone) Telp: {{ $recap['school']->phone }} @endif
                    @if($recap['school']?->email) | Email: {{ $recap['school']->email }} @endif
                    @if($recap['school']?->npsn) | NPSN: {{ $recap['school']->npsn }} @endif
                </p>
            </div>
        </div>

        <!-- JUDUL LAPORAN & META -->
        <div class="report-title-section">
            <h2>Laporan Rekapitulasi Tagihan Siswa</h2>

            <div class="report-meta-grid">
                <div class="report-meta-item">
                    <span class="label">Nama Siswa:</span>
                    <span class="val">{{ $recap['student']->name }}</span>
                </div>
                <div class="report-meta-item">
                    <span class="label">Periode Tagihan:</span>
                    <span class="val">{{ $recap['period']['formatted_period'] }}</span>
                </div>
                <div class="report-meta-item">
                    <span class="label">NIS:</span>
                    <span class="val">{{ $recap['student']->nis ?? '-' }}</span>
                </div>
                <div class="report-meta-item">
                    <span class="label">Jenis Tagihan:</span>
                    <span class="val">{{ $recap['period']['bill_name_label'] }}</span>
                </div>
                <div class="report-meta-item">
                    <span class="label">Kelas:</span>
                    <span class="val">{{ $recap['student']->classRoom?->name ?? '-' }}</span>
                </div>
                <div class="report-meta-item">
                    <span class="label">Tanggal Cetak:</span>
                    <span class="val">{{ $recap['period']['generated_at'] }}</span>
                </div>
            </div>
        </div>

        @if(!$recap['has_data'])
            <div class="empty-alert">
                Tidak ada data tagihan untuk siswa ini pada periode dan jenis tagihan yang dipilih.
            </div>
        @else
            <!-- TABEL TAGIHAN SISWA -->
            <table class="recap-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 27%;">Jenis Tagihan</th>
                        <th style="width: 18%; text-align: right;">Nominal</th>
                        <th style="width: 18%;">Jatuh Tempo</th>
                        <th style="width: 18%;">Tanggal Pembayaran</th>
                        <th style="width: 14%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recap['bills'] as $index => $bill)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $bill['name'] }}</strong>
                            </td>
                            <td class="text-right">{{ $bill['formatted_amount'] }}</td>
                            <td class="text-center">{{ $bill['due_date'] }}</td>
                            <td class="text-center">{{ $bill['paid_date'] }}</td>
                            <td class="text-center">
                                @if($bill['status'] === 'paid')
                                    <span class="status-badge status-paid">{{ $bill['status_label'] }}</span>
                                @elseif($bill['status'] === 'pending')
                                    <span class="status-badge status-pending">{{ $bill['status_label'] }}</span>
                                @else
                                    <span class="status-badge status-unpaid">{{ $bill['status_label'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- RINGKASAN REKAPITULASI -->
            <div class="summary-section">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Total Tagihan</th>
                            <th style="width: 33%;">Total Lunas</th>
                            <th style="width: 33%;">Sisa Belum Lunas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center; font-size: 13px;">
                                Rp {{ number_format($recap['summary']['total_amount'], 0, ',', '.') }}
                            </td>
                            <td style="text-align: center; font-size: 13px; color: #00871b;">
                                Rp {{ number_format($recap['summary']['total_paid'], 0, ',', '.') }}
                            </td>
                            <td style="text-align: center; font-size: 13px; color: #c53030;">
                                Rp {{ number_format($recap['summary']['total_unpaid'], 0, ',', '.') }}
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
