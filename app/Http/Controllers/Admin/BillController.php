<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillBatch;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BillController extends Controller
{
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

        $semesters = BillBatch::query()
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->distinct()
            ->orderByDesc('semester')
            ->pluck('semester');

        return view('admin.billing-data', [
            'batches' => $batches,
            'students' => $students,
            'classRooms' => $classRooms,
            'cohorts' => $cohorts,
            'semesters' => $semesters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_type' => [
                'required',
                Rule::in([
                    'student',
                    'class',
                    'cohort',
                    'school',
                ]),
            ],

            'target_value' => [
                'nullable',
                'integer',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'semester' => [
                'required',
                'string',
                'max:100',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

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
                    'target_value' =>
                        'Tidak ada siswa aktif pada target tersebut.',
                ]);
        }

        DB::transaction(function () use (
            $validated,
            $students
        ) {
            $batch = BillBatch::create([
                'name' => $validated['name'],

                'description' =>
                    $validated['description'] ?? null,

                'semester' =>
                    $validated['semester'],

                'amount' =>
                    $validated['amount'],

                'due_date' =>
                    $validated['due_date'] ?? null,

                'target_type' =>
                    $validated['target_type'],

                'target_value' =>
                    $validated['target_type'] === 'school'
                        ? null
                        : $validated['target_value'],
            ]);

            foreach ($students as $student) {
                Bill::create([
                    'bill_batch_id' => $batch->id,

                    'student_id' =>
                        $student->id,

                    'name' =>
                        $validated['name'],

                    'description' =>
                        $validated['description'] ?? null,

                    'semester' =>
                        $validated['semester'],

                    'amount' =>
                        $validated['amount'],

                    'due_date' =>
                        $validated['due_date'] ?? null,

                    'status' =>
                        'unpaid',
                ]);
            }
        });

        return redirect()
            ->route('admin.bills.index')
            ->with(
                'success',
                'Tagihan berhasil dibuat untuk '
                    . $students->count()
                    . ' siswa.'
            );
    }

    public function update(
        Request $request,
        BillBatch $bill
    ) {
        $validated = $request->validate([
            'target_type' => [
                'required',
                Rule::in([
                    'student',
                    'class',
                    'cohort',
                    'school',
                ]),
            ],

            'target_value' => [
                'nullable',
                'integer',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'semester' => [
                'required',
                'string',
                'max:100',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        if (
            $validated['target_type'] !== 'school'
            && empty($validated['target_value'])
        ) {
            return back()->withErrors([
                'target_value' =>
                    'Target tagihan harus dipilih.',
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
                    'target_value' =>
                        'Tidak ada siswa aktif pada target tersebut.',
                ]);
        }

        DB::transaction(function () use (
            $bill,
            $validated,
            $students
        ) {
            $bill->update([
                'name' =>
                    $validated['name'],

                'description' =>
                    $validated['description'] ?? null,

                'semester' =>
                    $validated['semester'],

                'amount' =>
                    $validated['amount'],

                'due_date' =>
                    $validated['due_date'] ?? null,

                'target_type' =>
                    $validated['target_type'],

                'target_value' =>
                    $validated['target_type'] === 'school'
                        ? null
                        : $validated['target_value'],
            ]);

            $bill->bills()->delete();

            foreach ($students as $student) {
                Bill::create([
                    'bill_batch_id' =>
                        $bill->id,

                    'student_id' =>
                        $student->id,

                    'name' =>
                        $validated['name'],

                    'description' =>
                        $validated['description'] ?? null,

                    'semester' =>
                        $validated['semester'],

                    'amount' =>
                        $validated['amount'],

                    'due_date' =>
                        $validated['due_date'] ?? null,

                    'status' =>
                        'unpaid',
                ]);
            }
        });

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

    private function resolveTargetStudents(
        string $targetType,
        ?int $targetValue
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