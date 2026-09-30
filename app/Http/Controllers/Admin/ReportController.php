<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Reports\BuildClassPaymentRecapAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\FilterPaymentReportRequest;
use App\Models\Bill;
use App\Models\ClassRoom;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman Laporan Pembayaran dengan 2 Tab:
     * - Tab 1: Rincian Transaksi (Fitur existing detail tagihan siswa)
     * - Tab 2: Rekap per Kelas (Fitur rekapitulasi tingkat/kelas per periode, jenis tagihan, dan opsi tingkat kelas)
     */
    public function index(Request $request, BuildClassPaymentRecapAction $recapAction): View
    {
        // Tentukan tab aktif, default adalah 'detail' agar behavior existing 100% terjaga
        $activeTab = $request->query('tab', 'detail');

        // ==========================================
        // 1. DATA UNTUK TAB 1: RINCIAN TRANSAKSI (EXISTING)
        // ==========================================
        $request->validate([
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'class_room_id' => [
                'nullable',
                'integer',
                'exists:class_rooms,id',
            ],
            'status' => [
                'nullable',
                'in:paid,pending,unpaid',
            ],
        ]);

        $query = Bill::query()
            ->with([
                'student.classRoom',
                'latestPayment.latestVerification',
            ]);

        // Filter kelas (existing)
        if ($request->filled('class_room_id')) {
            $query->whereHas(
                'student',
                function (Builder $studentQuery) use ($request) {
                    $studentQuery->where(
                        'class_room_id',
                        $request->class_room_id
                    );
                }
            );
        }

        // Filter tanggal awal (existing)
        if ($request->filled('start_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->start_date
            );
        }

        // Filter tanggal akhir (existing)
        if ($request->filled('end_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->end_date
            );
        }

        // Filter status (existing)
        if ($request->filled('status')) {
            if ($request->status === 'paid') {
                $query->where(function (Builder $statusQuery) {
                    $statusQuery
                        ->where('status', 'paid')
                        ->orWhereHas(
                            'latestPayment',
                            function (Builder $paymentQuery) {
                                $paymentQuery->where(
                                    'status',
                                    'paid'
                                );
                            }
                        );
                });
            }

            if ($request->status === 'pending') {
                $query->whereHas(
                    'latestPayment',
                    function (Builder $paymentQuery) {
                        $paymentQuery
                            ->where('status', 'pending')
                            ->whereNotNull(
                                'proof_of_payment'
                            );
                    }
                );
            }

            if ($request->status === 'unpaid') {
                $query
                    ->where('status', 'unpaid')
                    ->whereDoesntHave(
                        'latestPayment',
                        function (Builder $paymentQuery) {
                            $paymentQuery->whereIn(
                                'status',
                                [
                                    'paid',
                                    'pending',
                                ]
                            );
                        }
                    );
            }
        }

        $reports = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Summary pembayaran berhasil (existing)
        $paymentSummaryQuery = Payment::query()
            ->where('status', 'paid');

        if ($request->filled('start_date')) {
            $paymentSummaryQuery->whereDate(
                'paid_at',
                '>=',
                $request->start_date
            );
        }

        if ($request->filled('end_date')) {
            $paymentSummaryQuery->whereDate(
                'paid_at',
                '<=',
                $request->end_date
            );
        }

        if ($request->filled('class_room_id')) {
            $paymentSummaryQuery->whereHas(
                'bill.student',
                function (Builder $studentQuery) use ($request) {
                    $studentQuery->where(
                        'class_room_id',
                        $request->class_room_id
                    );
                }
            );
        }

        $totalIncome = (clone $paymentSummaryQuery)->sum('amount');
        $totalSuccessfulTransactions = (clone $paymentSummaryQuery)->count();

        $classRooms = ClassRoom::query()
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        // ==========================================
        // 2. DATA UNTUK TAB 2: REKAP PER KELAS (BARU)
        // ==========================================
        // Ambil daftar Jenis Tagihan dari satu sumber resmi (Bill::TYPES)
        // agar data legacy seperti "SPP Juli 2026" tidak lagi muncul di dropdown
        $availableBillTypes = collect(Bill::TYPES);

        // Daftar tahun yang tersedia
        $currentYear = (int) Carbon::now()->year;
        $availableYears = collect(range($currentYear - 3, $currentYear + 1))->reverse()->values();

        // Rekap data jika parameter rekap diberikan
        $recapData = null;
        $recapFiltered = false;

        if ($activeTab === 'recap' && $request->filled('month') && $request->filled('year') && $request->filled('bill_name')) {
            // Validasi parameter rekap menggunakan aturan FilterPaymentReportRequest yang konsisten
            $validatedRecap = $request->validate((new FilterPaymentReportRequest)->rules());

            $recapFiltered = true;
            $month = (int) $validatedRecap['month'];
            $year = (int) $validatedRecap['year'];
            $billName = (string) $validatedRecap['bill_name'];
            $grade = ! empty($validatedRecap['grade']) ? (string) $validatedRecap['grade'] : null;

            $recapData = $recapAction->execute($month, $year, $billName, $grade);
        }

        return view(
            'admin.payment-report',
            [
                // Data Tab 1 (Detail)
                'activeTab' => $activeTab,
                'reports' => $reports,
                'classRooms' => $classRooms,
                'totalIncome' => $totalIncome,
                'totalSuccessfulTransactions' => $totalSuccessfulTransactions,

                // Data Tab 2 (Rekap)
                'availableBillTypes' => $availableBillTypes,
                'availableYears' => $availableYears,
                'recapData' => $recapData,
                'recapFiltered' => $recapFiltered,
                'selectedMonth' => (int) $request->query('month', Carbon::now()->month),
                'selectedYear' => (int) $request->query('year', $currentYear),
                'selectedBillName' => (string) $request->query('bill_name', ''),
                'selectedGrade' => (string) $request->query('grade', ''),
            ]
        );
    }

    /**
     * Menampilkan halaman Pratinjau (Preview) Cetak Laporan Rekapitulasi per Kelas
     * Lengkap dengan KOP Sekolah, Metadata Periode & Tingkat, Tabel Rekapitulasi, dan Lembar Pengesahan Tanda Tangan.
     */
    public function preview(
        FilterPaymentReportRequest $request,
        BuildClassPaymentRecapAction $action
    ): View {
        $validated = $request->validated();
        $grade = ! empty($validated['grade']) ? (string) $validated['grade'] : null;

        $recap = $action->execute(
            (int) $validated['month'],
            (int) $validated['year'],
            (string) $validated['bill_name'],
            $grade
        );

        return view('admin.reports.preview', compact('recap'));
    }

    /**
     * Mengekspor Rekapitulasi Laporan Pembayaran ke format CSV (dapat langsung dibuka di Microsoft Excel).
     */
    public function exportExcel(
        FilterPaymentReportRequest $request,
        BuildClassPaymentRecapAction $action
    ): StreamedResponse {
        $validated = $request->validated();
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $billName = (string) $validated['bill_name'];
        $grade = ! empty($validated['grade']) ? (string) $validated['grade'] : null;

        $recap = $action->execute($month, $year, $billName, $grade);

        $monthSlug = Str::slug($recap['period']['month_name']);
        $billSlug = Str::slug($billName);
        $gradeSlug = $grade ? '-kelas-'.Str::slug($grade) : '';
        $fileName = "rekap-pembayaran-{$monthSlug}-{$year}-{$billSlug}{$gradeSlug}.csv";

        return response()->streamDownload(function () use ($recap) {
            $handle = fopen('php://output', 'w');

            // Tulis UTF-8 BOM agar Microsoft Excel membaca karakter aksen/bahasa Indonesia dengan benar
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Dokumen
            $schoolName = $recap['school']?->name ?? 'SEKOLAH';
            fputcsv($handle, [$schoolName]);
            fputcsv($handle, ['LAPORAN REKAPITULASI PEMBAYARAN SISWA']);
            fputcsv($handle, ['Periode', $recap['period']['formatted_period']]);
            fputcsv($handle, ['Jenis Tagihan', $recap['period']['bill_name']]);
            fputcsv($handle, ['Tingkat Kelas', $recap['period']['grade_label']]);
            fputcsv($handle, ['Tanggal Cetak', $recap['period']['generated_at']]);
            fputcsv($handle, []); // Baris kosong

            // Per Tingkat / Grade
            foreach ($recap['grades'] as $gradeSection) {
                fputcsv($handle, ['REKAP '.$gradeSection['grade_title']]);
                fputcsv($handle, [
                    'No',
                    'Kelas',
                    'Lunas (Siswa)',
                    'Total Pembayaran (Rp)',
                    'Belum Lunas (Siswa)',
                    'Total Tagihan Belum Lunas (Rp)',
                ]);

                $no = 1;
                foreach ($gradeSection['rows'] as $row) {
                    fputcsv($handle, [
                        $no++,
                        $row['class_name'],
                        $row['paid_count'],
                        $row['paid_total'],
                        $row['unpaid_count'],
                        $row['unpaid_total'],
                    ]);
                }

                // Total Per Grade
                fputcsv($handle, [
                    '',
                    'TOTAL '.$gradeSection['grade_title'],
                    $gradeSection['totals']['paid_count'],
                    $gradeSection['totals']['paid_total'],
                    $gradeSection['totals']['unpaid_count'],
                    $gradeSection['totals']['unpaid_total'],
                ]);

                fputcsv($handle, []); // Baris kosong pemisah section
            }

            // Grand Total
            fputcsv($handle, [
                '',
                'GRAND TOTAL KESELURUHAN',
                $recap['grand_totals']['paid_count'],
                $recap['grand_totals']['paid_total'],
                $recap['grand_totals']['unpaid_count'],
                $recap['grand_totals']['unpaid_total'],
            ]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
