<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Laporan Rekapitulasi - {{ $recap['period']['formatted_period'] }} - {{ $recap['period']['bill_name'] }} - {{ $recap['period']['grade_label'] }}</title>
    
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
            margin-bottom: 6px;
        }

        .report-meta-grid {
            display: inline-grid;
            grid-template-columns: auto auto;
            gap: 4px 16px;
            font-size: 12px;
            text-align: left;
            margin-top: 4px;
        }

        .report-meta-grid span.label {
            color: #4b5563;
        }

        .report-meta-grid span.val {
            font-weight: 600;
            color: #111827;
        }

        /* Section Grade */
        .grade-section {
            margin-bottom: 24px;
            page-break-inside: avoid;
        }

        .grade-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #2b3a8f;
            margin-bottom: 6px;
            padding-left: 2px;
        }

        /* Tabel Rekap */
        table.recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 6px;
        }

        table.recap-table th,
        table.recap-table td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
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

        table.recap-table tr.total-row td {
            background-color: #f9fafb;
            font-weight: 700;
            color: #111827;
        }

        /* Grand Total Section */
        .grand-total-section {
            margin-top: 16px;
            margin-bottom: 32px;
            page-break-inside: avoid;
        }

        .grand-total-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .grand-total-table th,
        .grand-total-table td {
            border: 2px solid #2b3a8f;
            padding: 8px 10px;
            font-weight: 700;
        }

        .grand-total-table thead th {
            background-color: #e0e2f4;
            color: #1e2968;
            text-align: center;
        }

        .grand-total-table tbody td {
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

            .grand-total-table th,
            .grand-total-table td {
                border-color: #000000 !important;
            }

            .grade-title {
                color: #000000 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Aksi Layar (Hidden Saat Print) -->
    <div class="no-print-bar">
        <a href="{{ route('admin.reports.index', ['tab' => 'recap', 'month' => $recap['period']['month'], 'year' => $recap['period']['year'], 'bill_name' => $recap['period']['bill_name'], 'grade' => $recap['period']['grade']]) }}" class="btn btn-back">
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
            <h2>Laporan Rekapitulasi Pembayaran Siswa</h2>
            <div class="report-meta-grid">
                <span class="label">Periode Tagihan:</span>
                <span class="val">{{ $recap['period']['formatted_period'] }}</span>

                <span class="label">Jenis Tagihan:</span>
                <span class="val">{{ $recap['period']['bill_name'] }}</span>

                <span class="label">Tingkat Kelas:</span>
                <span class="val">{{ $recap['period']['grade_label'] }}</span>

                <span class="label">Tanggal Cetak:</span>
                <span class="val">{{ $recap['period']['generated_at'] }}</span>
            </div>
        </div>

        @if(!$recap['has_data'])
            <div class="empty-alert">
                Tidak ada data tagihan untuk periode, jenis tagihan, dan tingkat kelas yang dipilih.
            </div>
        @else
            <!-- PER SECTION TINGKAT / GRADE -->
            @foreach($recap['grades'] as $gradeSection)
                <div class="grade-section">
                    <div class="grade-title">
                        REKAP {{ $gradeSection['grade_title'] }}
                    </div>

                    <table class="recap-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 25%;">Kelas</th>
                                <th style="width: 15%;">Lunas</th>
                                <th style="width: 20%;">Total Pembayaran</th>
                                <th style="width: 15%;">Belum Lunas</th>
                                <th style="width: 20%;">Total Tagihan Belum Lunas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gradeSection['rows'] as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $row['class_name'] }}</td>
                                    <td class="text-center">{{ $row['paid_count'] }} siswa</td>
                                    <td class="text-right">Rp {{ number_format($row['paid_total'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $row['unpaid_count'] }} siswa</td>
                                    <td class="text-right">Rp {{ number_format($row['unpaid_total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <!-- TOTAL PER GRADE -->
                            <tr class="total-row">
                                <td colspan="2" class="text-center">TOTAL {{ $gradeSection['grade_title'] }}</td>
                                <td class="text-center">{{ $gradeSection['totals']['paid_count'] }} siswa</td>
                                <td class="text-right">Rp {{ number_format($gradeSection['totals']['paid_total'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ $gradeSection['totals']['unpaid_count'] }} siswa</td>
                                <td class="text-right">Rp {{ number_format($gradeSection['totals']['unpaid_total'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <!-- GRAND TOTAL KESELURUHAN -->
            <div class="grand-total-section">
                <table class="grand-total-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">GRAND TOTAL</th>
                            <th style="width: 15%;">Lunas</th>
                            <th style="width: 20%;">Total Pembayaran</th>
                            <th style="width: 15%;">Belum Lunas</th>
                            <th style="width: 20%;">Total Tagihan Belum Lunas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">{{ $recap['period']['grade_label'] }}</td>
                            <td style="text-align: center;">{{ $recap['grand_totals']['paid_count'] }} siswa</td>
                            <td style="text-align: right;">Rp {{ number_format($recap['grand_totals']['paid_total'], 0, ',', '.') }}</td>
                            <td style="text-align: center;">{{ $recap['grand_totals']['unpaid_count'] }} siswa</td>
                            <td style="text-align: right;">Rp {{ number_format($recap['grand_totals']['unpaid_total'], 0, ',', '.') }}</td>
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
