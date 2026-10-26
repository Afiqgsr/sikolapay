<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Reports\BuildClassPaymentRecapAction;
use App\Actions\Admin\Reports\BuildStudentBillRecapAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\FilterPaymentReportRequest;
use App\Http\Requests\Admin\Reports\FilterStudentBillReportRequest;
use App\Models\Bill;
use App\Models\ClassRoom;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman Laporan Pembayaran dengan 3 Tab:
     * - Tab 1: Rincian Transaksi (Fitur existing detail tagihan siswa)
     * - Tab 2: Rekap per Kelas (Fitur rekapitulasi tingkat/kelas per periode, jenis tagihan, dan opsi tingkat kelas)
     * - Tab 3: Rekap per Siswa (Fitur rekapitulasi tagihan per siswa dengan filter periode, jenis tagihan, dan pencarian)
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
        $startBillingPeriod = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfMonth()->toDateString()
            : null;
        $endBillingPeriod = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->startOfMonth()->toDateString()
            : null;

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

        // Filter periode tagihan awal
        if ($startBillingPeriod !== null) {
            $query->whereDate(
                'billing_period',
                '>=',
                $startBillingPeriod
            );
        }

        // Filter periode tagihan akhir
        if ($endBillingPeriod !== null) {
            $query->whereDate(
                'billing_period',
                '<=',
                $endBillingPeriod
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

        if ($startBillingPeriod !== null) {
            $paymentSummaryQuery->whereHas('bill', function (Builder $billQuery) use ($startBillingPeriod) {
                $billQuery->whereDate('billing_period', '>=', $startBillingPeriod);
            });
        }

        if ($endBillingPeriod !== null) {
            $paymentSummaryQuery->whereHas('bill', function (Builder $billQuery) use ($endBillingPeriod) {
                $billQuery->whereDate('billing_period', '<=', $endBillingPeriod);
            });
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
        // 2. DATA UNTUK TAB 2: REKAP PER KELAS (EXISTING)
        // ==========================================
        // Ambil daftar Jenis Tagihan dari satu sumber resmi (Bill::TYPES)
        // agar data legacy seperti "SPP Juli 2026" tidak lagi muncul di dropdown
        $availableBillTypes = collect(Bill::TYPES);

        // Daftar tahun yang tersedia
        $currentYear = (int) Carbon::now()->year;
        $availableYears = collect(range($currentYear - 3, $currentYear + 1))->reverse()->values();

        // Rekap data per kelas jika parameter rekap diberikan
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

        // ==========================================
        // 3. DATA UNTUK TAB 3: REKAP PER SISWA (BARU)
        // ==========================================
        $studentRecapStudents = null;
        $studentRecapFiltered = false;

        if ($activeTab === 'student_recap' && $request->filled('month') && $request->filled('year')) {
            $validatedStudentRecap = $request->validate(
                (new FilterStudentBillReportRequest)->rules(),
                (new FilterStudentBillReportRequest)->messages()
            );

            $studentRecapFiltered = true;
            $sMonth = (int) $validatedStudentRecap['month'];
            $sYear = (int) $validatedStudentRecap['year'];
            $sBillName = ! empty($validatedStudentRecap['bill_name']) ? (string) $validatedStudentRecap['bill_name'] : null;
            $sSearch = ! empty($validatedStudentRecap['search']) ? trim((string) $validatedStudentRecap['search']) : null;

            $sPeriodStart = Carbon::create($sYear, $sMonth, 1)->startOfMonth();
            $sPeriodEnd = $sPeriodStart->copy()->addMonth();

            $studentQuery = Student::query()
                ->with('classRoom')
                ->whereHas('bills', function (Builder $billQuery) use ($sPeriodStart, $sPeriodEnd, $sBillName) {
                    $billQuery->where('billing_period', '>=', $sPeriodStart)
                        ->where('billing_period', '<', $sPeriodEnd);

                    if (! empty($sBillName) && $sBillName !== 'all') {
                        $billQuery->where('name', $sBillName);
                    }
                });

            if (! empty($sSearch)) {
                $studentQuery->where(function (Builder $q) use ($sSearch) {
                    $q->where('name', 'like', "%{$sSearch}%")
                        ->orWhere('nis', 'like', "%{$sSearch}%");
                });
            }

            $studentRecapStudents = $studentQuery
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
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

                // Data Tab 2 (Rekap Kelas)
                'availableBillTypes' => $availableBillTypes,
                'availableYears' => $availableYears,
                'recapData' => $recapData,
                'recapFiltered' => $recapFiltered,
                'selectedMonth' => (int) $request->query('month', Carbon::now()->month),
                'selectedYear' => (int) $request->query('year', $currentYear),
                'selectedBillName' => (string) $request->query('bill_name', ''),
                'selectedGrade' => (string) $request->query('grade', ''),

                // Data Tab 3 (Rekap per Siswa)
                'studentRecapStudents' => $studentRecapStudents,
                'studentRecapFiltered' => $studentRecapFiltered,
                'selectedStudentMonth' => (int) $request->query('month', Carbon::now()->month),
                'selectedStudentYear' => (int) $request->query('year', $currentYear),
                'selectedStudentBillName' => (string) $request->query('bill_name', ''),
                'selectedStudentSearch' => (string) $request->query('search', ''),
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
     * Menampilkan halaman Pratinjau (Preview) Cetak Laporan Detail Transaksi Pembayaran.
     * Mengambil seluruh data transaksi berdasarkan filter aktif (tanpa pagination) dengan KOP Sekolah resmi.
     */
    public function previewTransaction(Request $request): View
    {
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

        $startBillingPeriod = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfMonth()->toDateString()
            : null;
        $endBillingPeriod = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->startOfMonth()->toDateString()
            : null;

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

        if ($startBillingPeriod !== null) {
            $query->whereDate(
                'billing_period',
                '>=',
                $startBillingPeriod
            );
        }

        if ($endBillingPeriod !== null) {
            $query->whereDate(
                'billing_period',
                '<=',
                $endBillingPeriod
            );
        }

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

        $reports = $query->latest()->get();

        $selectedClass = null;
        if ($request->filled('class_room_id')) {
            $selectedClass = ClassRoom::find($request->class_room_id);
        }

        $school = School::first();

        // Hitung total nominal transaksi terfilter
        $totalNominal = $reports->sum('amount');
        $totalCount = $reports->count();

        return view('admin.reports.transaction-preview', [
            'reports' => $reports,
            'school' => $school,
            'selectedClass' => $selectedClass,
            'startDate' => $request->input('start_date'),
            'endDate' => $request->input('end_date'),
            'status' => $request->input('status'),
            'totalNominal' => $totalNominal,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Menampilkan halaman Pratinjau (Preview) Cetak Laporan Rekapitulasi Tagihan per Siswa.
     */
    public function previewStudent(
        Student $student,
        FilterStudentBillReportRequest $request,
        BuildStudentBillRecapAction $action
    ): View {
        $validated = $request->validated();
        $billName = ! empty($validated['bill_name']) ? (string) $validated['bill_name'] : null;

        $recap = $action->execute(
            $student,
            (int) $validated['month'],
            (int) $validated['year'],
            $billName
        );

        return view('admin.reports.student-preview', compact('recap'));
    }

    /**
     * Mengekspor Rekapitulasi Tagihan Siswa ke format CSV (Excel).
     */
    public function exportStudentExcel(
        Student $student,
        FilterStudentBillReportRequest $request,
        BuildStudentBillRecapAction $action
    ): StreamedResponse {
        $validated = $request->validated();
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $billName = ! empty($validated['bill_name']) ? (string) $validated['bill_name'] : null;

        $recap = $action->execute($student, $month, $year, $billName);

        $studentSlug = Str::slug($student->name);
        $monthSlug = Str::slug($recap['period']['month_name']);
        $billSlug = $billName ? '-'.Str::slug($billName) : '';
        $fileName = "rekap-tagihan-{$studentSlug}-{$monthSlug}-{$year}{$billSlug}.csv";

        return response()->streamDownload(function () use ($recap) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }

            // Tulis UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Dokumen
            $schoolName = $recap['school']?->name ?? 'SEKOLAH SIKOLAPAY';
            fputcsv($handle, [$schoolName]);
            fputcsv($handle, ['LAPORAN REKAPITULASI TAGIHAN SISWA']);
            fputcsv($handle, ['Nama Siswa', $recap['student']->name]);
            fputcsv($handle, ['NIS', $recap['student']->nis ?? '-']);
            fputcsv($handle, ['Kelas', $recap['student']->classRoom?->name ?? '-']);
            fputcsv($handle, ['Periode Tagihan', $recap['period']['formatted_period']]);
            fputcsv($handle, ['Jenis Tagihan', $recap['period']['bill_name_label']]);
            fputcsv($handle, ['Tanggal Cetak', $recap['period']['generated_at']]);
            fputcsv($handle, []); // Baris kosong

            // Header Tabel Tagihan
            fputcsv($handle, [
                'No',
                'Nama Tagihan',
                'Deskripsi',
                'Semester',
                'Jatuh Tempo',
                'Nominal (Rp)',
                'Status',
                'Tanggal Bayar',
                'Metode Pembayaran',
            ]);

            $no = 1;
            foreach ($recap['bills'] as $bill) {
                fputcsv($handle, [
                    $no++,
                    $bill['name'],
                    $bill['description'] ?? '-',
                    $bill['semester'] ?? '-',
                    $bill['due_date'],
                    $bill['amount'],
                    $bill['status_label'],
                    $bill['paid_date'],
                    $bill['payment_method'],
                ]);
            }

            fputcsv($handle, []); // Baris kosong

            // Summary Total
            fputcsv($handle, ['', 'RINGKASAN REKAPITULASI']);
            fputcsv($handle, ['', 'Total Tagihan (Rp)', $recap['summary']['total_amount']]);
            fputcsv($handle, ['', 'Total Lunas (Rp)', $recap['summary']['total_paid']]);
            fputcsv($handle, ['', 'Sisa Belum Lunas (Rp)', $recap['summary']['total_unpaid']]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
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

            if ($handle === false) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }

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
