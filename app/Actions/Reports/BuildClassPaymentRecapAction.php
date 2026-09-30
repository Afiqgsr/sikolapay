<?php

namespace App\Actions\Reports;

use App\Models\Bill;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildClassPaymentRecapAction
{
    /**
     * Membangun data rekapitulasi pembayaran per kelas dan per tingkat (grade)
     * berdasarkan periode tagihan (Bulan & Tahun), Jenis Tagihan (bills.name),
     * dan opsi filter Tingkat Kelas ($grade: X, XI, XII, atau null untuk semua tingkat).
     *
     * @param  int  $month  Bulan periode tagihan (1-12)
     * @param  int  $year  Tahun periode tagihan (misal: 2026)
     * @param  string  $billName  Nama jenis tagihan (bills.name)
     * @param  string|null  $grade  Opsi filter tingkat kelas ('X', 'XI', 'XII', atau null untuk semua tingkat)
     * @return array<string, mixed>
     */
    public function execute(int $month, int $year, string $billName, ?string $grade = null): array
    {
        // Normalisasi parameter grade input jika diberikan (contoh: '10' -> 'X', '11' -> 'XI', '12' -> 'XII')
        $normalizedFilterGrade = $this->normalizeGrade($grade);

        // Ambil data sekolah untuk KOP laporan
        $school = School::first();

        // 1. Query data tagihan yang sesuai dengan filter jenis tagihan, periode jatuh tempo, dan tingkat kelas.
        // Periode tagihan ditentukan dengan prioritas:
        // - Utama: bills.due_date
        // - Fallback: bill_batches.due_date jika bills.due_date bernilai NULL
        // - Tagihan dengan due_date NULL dan tanpa batch due_date tidak disertakan dalam periode bulan/tahun spesifik.
        $query = Bill::query()
            ->where('bills.name', $billName)
            ->where(function (Builder $query) use ($month, $year) {
                // Kondisi 1: bills.due_date terisi dan sesuai bulan & tahun
                $query->where(function (Builder $q) use ($month, $year) {
                    $q->whereNotNull('bills.due_date')
                        ->whereYear('bills.due_date', $year)
                        ->whereMonth('bills.due_date', $month);
                })
                // Kondisi 2: bills.due_date NULL, gunakan fallback bill_batches.due_date
                    ->orWhere(function (Builder $q) use ($month, $year) {
                        $q->whereNull('bills.due_date')
                            ->whereHas('batch', function (Builder $batchQuery) use ($month, $year) {
                                $batchQuery->whereNotNull('due_date')
                                    ->whereYear('due_date', $year)
                                    ->whereMonth('due_date', $month);
                            });
                    });
            });

        // Jika tingkat kelas spesifik dipilih (misal 'X', 'XI', atau 'XII'):
        // Filter sedini mungkin di level database menggunakan whereHas pada relasi student.classRoom
        if ($normalizedFilterGrade !== null) {
            $matchingDbGrades = $this->getDbGradeVariants($normalizedFilterGrade);

            $query->whereHas('student.classRoom', function (Builder $classQuery) use ($matchingDbGrades) {
                $classQuery->whereIn('grade', $matchingDbGrades);
            });
        }

        $bills = $query->with([
            'student.classRoom',
            'batch',
        ])->get();

        // 2. Kelompokkan tagihan berdasarkan Tingkat / Grade dari Kelas Siswa
        // Normalisasi grade kelas (misal: '10' -> 'X', '11' -> 'XI', '12' -> 'XII', 'TANPA_KELAS' -> 'TANPA_KELAS')
        $groupedByGrade = $bills->groupBy(function (Bill $bill) {
            $classRoom = $bill->student?->classRoom;

            if (! $classRoom || $classRoom->grade === 'TANPA_KELAS' || empty($classRoom->grade)) {
                return 'TANPA_KELAS';
            }

            return $this->normalizeGrade($classRoom->grade) ?? 'TANPA_KELAS';
        });

        $gradeSections = [];
        $grandTotals = [
            'paid_count' => 0,
            'paid_total' => 0.0,
            'unpaid_count' => 0,
            'unpaid_total' => 0.0,
        ];

        // Daftar ID siswa unik untuk akumulasi grand total siswa keseluruhan sekolah / grade terpilih
        $grandPaidStudentIds = [];
        $grandUnpaidStudentIds = [];

        // Urutkan key grade (prioritas: X, XI, XII, lalu TANPA_KELAS)
        $gradePriorityOrder = ['X' => 1, 'XI' => 2, 'XII' => 3, 'TANPA_KELAS' => 99];
        $sortedGradeKeys = $groupedByGrade->keys()->sort(function ($a, $b) use ($gradePriorityOrder) {
            $orderA = $gradePriorityOrder[$a] ?? 50;
            $orderB = $gradePriorityOrder[$b] ?? 50;

            if ($orderA === $orderB) {
                return strnatcasecmp((string) $a, (string) $b);
            }

            return $orderA <=> $orderB;
        });

        foreach ($sortedGradeKeys as $gradeKey) {
            /** @var Collection<int, Bill> $gradeBills */
            $gradeBills = $groupedByGrade->get($gradeKey);

            // Kelompokkan tagihan di dalam satu grade berdasarkan ClassRoom sebenarnya (rombel)
            $groupedByClass = $gradeBills->groupBy(function (Bill $bill) {
                $classRoom = $bill->student?->classRoom;

                return $classRoom ? $classRoom->id : 'NO_CLASS';
            });

            $classRows = [];
            $gradePaidStudentIds = [];
            $gradeUnpaidStudentIds = [];

            $gradeTotals = [
                'paid_count' => 0,
                'paid_total' => 0.0,
                'unpaid_count' => 0,
                'unpaid_total' => 0.0,
            ];

            // Urutkan kelas berdasarkan nama kelas / rombel
            $sortedClassKeys = $groupedByClass->keys()->sort(function ($a, $b) use ($groupedByClass) {
                $classA = $groupedByClass->get($a)?->first()?->student?->classRoom?->name ?? 'Tanpa Kelas';
                $classB = $groupedByClass->get($b)?->first()?->student?->classRoom?->name ?? 'Tanpa Kelas';

                return strnatcasecmp($classA, $classB);
            });

            foreach ($sortedClassKeys as $classKey) {
                /** @var Collection<int, Bill> $classBills */
                $classBills = $groupedByClass->get($classKey);
                $firstBill = $classBills->first();
                $className = $firstBill?->student?->classRoom?->name ?? 'Tanpa Kelas';

                // Pisahkan tagihan lunas dan belum lunas
                // Definisi Lunas: bills.status === 'paid'
                // Definisi Belum Lunas: bills.status !== 'paid'
                $paidBills = $classBills->filter(fn (Bill $b) => $b->status === 'paid');
                $unpaidBills = $classBills->filter(fn (Bill $b) => $b->status !== 'paid');

                // Jumlah siswa dihitung berdasarkan student_id unik
                $paidStudentIdsInClass = $paidBills->pluck('student_id')->filter()->unique()->all();
                $unpaidStudentIdsInClass = $unpaidBills->pluck('student_id')->filter()->unique()->all();

                $paidCount = count($paidStudentIdsInClass);
                $unpaidCount = count($unpaidStudentIdsInClass);

                // Nominal dihitung dari penjumlahan nilai faktual bills.amount
                $paidTotal = (float) $paidBills->sum('amount');
                $unpaidTotal = (float) $unpaidBills->sum('amount');

                $classRows[] = [
                    'class_id' => $classKey === 'NO_CLASS' ? null : (int) $classKey,
                    'class_name' => $className,
                    'paid_count' => $paidCount,
                    'paid_total' => $paidTotal,
                    'unpaid_count' => $unpaidCount,
                    'unpaid_total' => $unpaidTotal,
                ];

                // Akumulasi nominal ke tingkat grade
                $gradeTotals['paid_total'] += $paidTotal;
                $gradeTotals['unpaid_total'] += $unpaidTotal;

                // Kumpulkan student ID unik di level grade
                foreach ($paidStudentIdsInClass as $sId) {
                    $gradePaidStudentIds[$sId] = true;
                    $grandPaidStudentIds[$sId] = true;
                }
                foreach ($unpaidStudentIdsInClass as $sId) {
                    $gradeUnpaidStudentIds[$sId] = true;
                    $grandUnpaidStudentIds[$sId] = true;
                }
            }

            $gradeTotals['paid_count'] = count($gradePaidStudentIds);
            $gradeTotals['unpaid_count'] = count($gradeUnpaidStudentIds);

            // Label section grade (misal: "KELAS X", "KELAS XI", "KELAS XII", atau "TANPA KELAS")
            $gradeTitle = $gradeKey === 'TANPA_KELAS'
                ? 'TANPA KELAS'
                : 'KELAS '.strtoupper((string) $gradeKey);

            $gradeSections[] = [
                'grade_key' => $gradeKey,
                'grade_title' => $gradeTitle,
                'is_without_class' => $gradeKey === 'TANPA_KELAS',
                'rows' => $classRows,
                'totals' => $gradeTotals,
            ];

            // Akumulasi nominal ke grand total
            $grandTotals['paid_total'] += $gradeTotals['paid_total'];
            $grandTotals['unpaid_total'] += $gradeTotals['unpaid_total'];
        }

        $grandTotals['paid_count'] = count($grandPaidStudentIds);
        $grandTotals['unpaid_count'] = count($grandUnpaidStudentIds);

        // Nama bulan terlokalisasi dalam Bahasa Indonesia
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F');

        // Label Tingkat Kelas untuk tampilan UI & Dokumen Cetak
        $gradeLabel = $normalizedFilterGrade !== null
            ? 'Kelas '.$normalizedFilterGrade
            : 'Semua Tingkat';

        return [
            'school' => $school,
            'period' => [
                'month' => $month,
                'month_name' => $monthName,
                'year' => $year,
                'bill_name' => $billName,
                'grade' => $normalizedFilterGrade,
                'grade_label' => $gradeLabel,
                'formatted_period' => $monthName.' '.$year,
                'generated_at' => Carbon::now()->translatedFormat('d F Y'),
            ],
            'grades' => $gradeSections,
            'grand_totals' => $grandTotals,
            'has_data' => $bills->isNotEmpty(),
            'total_bills_count' => $bills->count(),
        ];
    }

    /**
     * Menormalisasi format grade menjadi string standar: 'X', 'XI', atau 'XII'.
     * Menangani input numerik ('10' -> 'X', '11' -> 'XI', '12' -> 'XII').
     */
    private function normalizeGrade(?string $grade): ?string
    {
        if ($grade === null || trim($grade) === '' || strtoupper(trim($grade)) === 'ALL') {
            return null;
        }

        $clean = strtoupper(trim($grade));

        return match ($clean) {
            '10', 'X' => 'X',
            '11', 'XI' => 'XI',
            '12', 'XII' => 'XII',
            'TANPA_KELAS' => 'TANPA_KELAS',
            default => $clean,
        };
    }

    /**
     * Mengembalikan variasi nilai grade yang mungkin ada di database (misal: ['X', '10'] untuk Kelas X)
     * guna memastikan filter database mencakup format Romawi maupun numerik.
     *
     * @return array<int, string>
     */
    private function getDbGradeVariants(string $normalizedGrade): array
    {
        return match ($normalizedGrade) {
            'X' => ['X', '10', 'x'],
            'XI' => ['XI', '11', 'xi'],
            'XII' => ['XII', '12', 'xii'],
            default => [$normalizedGrade],
        };
    }
}
