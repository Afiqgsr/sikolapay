<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Bills\CreateBillBatchAction;
use App\Actions\Admin\Bills\UpdateBillBatchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Bills\StoreBillRequest;
use App\Http\Requests\Admin\Bills\UpdateBillRequest;
use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillBatch;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function __construct(
        private CreateBillBatchAction $createBillBatchAction,
        private UpdateBillBatchAction $updateBillBatchAction,
    ) {}

    public function index(Request $request)
    {
        $query = BillBatch::query()
            ->with([
                'bills.student.classRoom',
                'bills.latestPayment',
            ])
            ->withCount('bills');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('semester', 'like', "%{$search}%")
                    ->orWhereHas(
                        'bills.student',
                        function (Builder $studentQuery) use ($search) {
                            $studentQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('nis', 'like', "%{$search}%")
                                ->orWhere('nisn', 'like', "%{$search}%");
                        }
                    );
            });
        }

        if ($request->filled('target_type')) {
            $query->where(
                'target_type',
                $request->target_type
            );
        }

        if ($request->filled('semester')) {
            $query->where(
                'semester',
                $request->semester
            );
        }

        $batches = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $students = Student::query()
            ->with('classRoom')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $classRooms = ClassRoom::query()
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $cohorts = Student::query()
            ->whereNotNull('entry_year')
            ->distinct()
            ->orderByDesc('entry_year')
            ->pluck('entry_year');

        // Mengambil daftar opsi Semester resmi berdasarkan Tahun Ajaran (AcademicYear)
        // Prioritaskan AcademicYear yang aktif jika ada, dengan fallback data AcademicYear yang ada
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        if (! $activeAcademicYear) {
            $activeAcademicYear = AcademicYear::latest()->first();
        }

        $academicYearSemesters = collect();
        if ($activeAcademicYear) {
            $academicYearSemesters = collect([
                'Ganjil '.$activeAcademicYear->name,
                'Genap '.$activeAcademicYear->name,
            ]);
        }

        // Daftar semester untuk filter tabel (menggabungkan semester dari Tahun Ajaran dan data batch existing)
        $semesters = BillBatch::query()
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->distinct()
            ->orderByDesc('semester')
            ->pluck('semester');

        $semesters = $academicYearSemesters->merge($semesters)->unique()->values();

        return view('admin.billing-data', [
            'batches' => $batches,
            'students' => $students,
            'classRooms' => $classRooms,
            'cohorts' => $cohorts,
            'semesters' => $semesters,
            'academicYearSemesters' => $academicYearSemesters,
            'billTypes' => Bill::TYPES,
        ]);
    }

    public function store(StoreBillRequest $request)
    {
        $validated = $request->validated();

        if (
            $validated['target_type'] !== 'school'
            && empty($validated['target_value'])
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'target_value' => 'Target tagihan harus dipilih.',
                ]);
        }

        $students = $this->resolveTargetStudents(
            $validated['target_type'],
            $validated['target_value'] ?? null
        );

        if ($students->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'target_value' => 'Tidak ada siswa aktif pada target tersebut.',
                ]);
        }

        $this->createBillBatchAction->execute(
            $validated,
            $students
        );

        return redirect()
            ->route('admin.bills.index')
            ->with(
                'success',
                'Tagihan berhasil dibuat untuk '
                    .$students->count()
                    .' siswa.'
            );
    }

    public function update(
        UpdateBillRequest $request,
        BillBatch $bill
    ) {
        $validated = $request->validated();

        if (
            $validated['target_type'] !== 'school'
            && empty($validated['target_value'])
        ) {
            return back()->withErrors([
                'target_value' => 'Target tagihan harus dipilih.',
            ]);
        }

        $hasPayment = $bill
            ->bills()
            ->whereHas('payments')
            ->exists();

        if ($hasPayment) {
            return redirect()
                ->route('admin.bills.index')
                ->with(
                    'error',
                    'Tagihan tidak dapat diedit karena sudah memiliki data pembayaran.'
                );
        }

        $students = $this->resolveTargetStudents(
            $validated['target_type'],
            $validated['target_value'] ?? null
        );

        if ($students->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'target_value' => 'Tidak ada siswa aktif pada target tersebut.',
                ]);
        }

        $this->updateBillBatchAction->execute(
            $bill,
            $validated,
            $students
        );

        return redirect()
            ->route('admin.bills.index')
            ->with(
                'success',
                'Tagihan berhasil diperbarui.'
            );
    }

    public function destroy(BillBatch $bill)
    {
        $hasPayment = $bill
            ->bills()
            ->whereHas('payments')
            ->exists();

        if ($hasPayment) {
            return redirect()
                ->route('admin.bills.index')
                ->with(
                    'error',
                    'Tagihan tidak dapat dihapus karena sudah memiliki pembayaran.'
                );
        }

        DB::transaction(function () use ($bill) {
            $bill->bills()->delete();
            $bill->delete();
        });

        return redirect()
            ->route('admin.bills.index')
            ->with(
                'success',
                'Tagihan berhasil dihapus.'
            );
    }

    /**
     * Mengambil kumpulan siswa aktif berdasarkan target_type dan target_value.
     * Mendukung target: student, class, grade (X/XI/XII), school, dan cohort (legacy).
     *
     * @return Collection<int, Student>
     */
    private function resolveTargetStudents(
        string $targetType,
        string|int|null $targetValue
    ): Collection {
        $query = Student::query()
            ->where('status', 'active');

        switch ($targetType) {
            case 'student':
                $query->where(
                    'id',
                    $targetValue
                );
                break;

            case 'class':
                $query->where(
                    'class_room_id',
                    $targetValue
                );
                break;

            case 'grade':
                // Normalisasi dan cari varian nilai grade (contoh: ['X', '10', 'x'])
                $gradeVariants = match (strtoupper(trim((string) $targetValue))) {
                    'X', '10' => ['X', '10', 'x'],
                    'XI', '11' => ['XI', '11', 'xi'],
                    'XII', '12' => ['XII', '12', 'xii'],
                    default => [(string) $targetValue],
                };

                $query->whereHas('classRoom', function (Builder $q) use ($gradeVariants) {
                    $q->whereIn('grade', $gradeVariants);
                });
                break;

            case 'cohort':
                $query->where(
                    'entry_year',
                    $targetValue
                );
                break;

            case 'school':
                break;
        }

        return $query->get();
    }
}
