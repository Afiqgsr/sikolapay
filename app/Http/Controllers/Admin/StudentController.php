<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()
            ->with([
                'user',
                'guardian.user',
                'classRoom.academicYear',
            ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($studentQuery) use ($search) {
                $studentQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_room_id')) {
            $query->where(
                'class_room_id',
                $request->class_room_id
            );
        }

        $students = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $classRooms = ClassRoom::with('academicYear')
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $totalStudents = Student::count();

        return view('admin.student-data', [
            'students' => $students,
            'classRooms' => $classRooms,
            'totalStudents' => $totalStudents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => [
                'required',
                'string',
                'max:50',
                'unique:students,nis',
            ],

            'nisn' => [
                'nullable',
                'string',
                'max:50',
                'unique:students,nisn',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'class_room_id' => [
                'required',
                'exists:class_rooms,id',
            ],

            'entry_year' => [
                'required',
                'integer',
                'digits:4',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'guardian_name' => [
                'required',
                'string',
                'max:255',
            ],

            'guardian_email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'guardian_phone' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        DB::transaction(function () use ($validated) {

            $studentUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $guardianUser = User::create([
                'name' => $validated['guardian_name'],
                'email' => $validated['guardian_email'],
                'password' => Hash::make('password123'),
                'role' => 'guardian',
            ]);

            $guardian = Guardian::create([
                'user_id' => $guardianUser->id,
                'name' => $validated['guardian_name'],
                'phone' => $validated['guardian_phone'],
                'address' => null,
            ]);

            Student::create([
                'user_id' => $studentUser->id,
                'guardian_id' => $guardian->id,
                'class_room_id' => $validated['class_room_id'],
                'entry_year' => $validated['entry_year'],
                'status' => $validated['status'],
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?: null,
                'name' => $validated['name'],

                // sementara default karena form Data Siswa
                // belum menyediakan gender
                'gender' => 'L',

                'birth_date' => null,
                'birth_place' => null,
                'address' => null,
            ]);
        });

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student)
    {
        $student->load([
            'user',
            'guardian.user',
        ]);

        $validated = $request->validate([
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nis')
                    ->ignore($student->id),
            ],

            'nisn' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'nisn')
                    ->ignore($student->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'class_room_id' => [
                'required',
                'exists:class_rooms,id',
            ],

            'entry_year' => [
                'required',
                'integer',
                'digits:4',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($student->user_id),
            ],

            'guardian_name' => [
                'required',
                'string',
                'max:255',
            ],

            'guardian_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($student->guardian?->user_id),
            ],

            'guardian_phone' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        DB::transaction(function () use ($student, $validated) {

            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $student->guardian->user->update([
                'name' => $validated['guardian_name'],
                'email' => $validated['guardian_email'],
            ]);

            $student->guardian->update([
                'name' => $validated['guardian_name'],
                'phone' => $validated['guardian_phone'],
            ]);

            $student->update([
                'class_room_id' => $validated['class_room_id'],
                'entry_year' => $validated['entry_year'],
                'status' => $validated['status'],
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?: null,
                'name' => $validated['name'],
            ]);
        });

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {

            $student->load([
                'guardian',
                'user',
            ]);

            $studentUser = $student->user;

            $guardian = $student->guardian;

            $guardianUser = $guardian?->user;

            $student->delete();

            if ($studentUser) {
                $studentUser->delete();
            }

            if ($guardian && $guardian->students()->count() === 0) {
                $guardian->delete();

                if ($guardianUser) {
                    $guardianUser->delete();
                }
            }
        });

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Data siswa berhasil dihapus.');
    }
}