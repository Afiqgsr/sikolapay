<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $guardians = Guardian::query()
            ->with('user')
            ->orderBy('name')
            ->get();

        $totalStudents = Student::count();

        return view('admin.student-data', [
            'students' => $students,
            'classRooms' => $classRooms,
            'guardians' => $guardians,
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

            'gender' => [
                'required',
                Rule::in([
                    'L',
                    'P',
                ]),
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

            'guardian_mode' => [
                'required',
                Rule::in([
                    'new',
                    'existing',
                ]),
            ],

            'guardian_id' => [
                'nullable',
                'required_if:guardian_mode,existing',
                'exists:guardians,id',
            ],

            'guardian_name' => [
                'nullable',
                'required_if:guardian_mode,new',
                'string',
                'max:255',
            ],

            'guardian_phone' => [
                'nullable',
                'required_if:guardian_mode,new',
                'string',
                'max:20',
            ],

            'guardian_email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'guardian_relationship' => [
                'nullable',
                'string',
                'max:50',
            ],

            'guardian_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use ($validated) {

            /* Akun siswa */

            $studentUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],

                // Password awal siswa = NIS.
                'password' => $validated['nis'],

                'role' => 'student',
                'status' => $validated['status'],
            ]);

            // Akun dibuat oleh admin.
            $studentUser->forceFill([
                'email_verified_at' => now(),
            ])->save();

            /* Tentukan wali */

            if ($validated['guardian_mode'] === 'existing') {

                /*
                 * Gunakan wali yang sudah terdaftar.
                 *
                 * Tidak membuat User guardian baru dan
                 * tidak membuat record Guardian baru.
                 */
                $guardian = Guardian::findOrFail(
                    $validated['guardian_id']
                );

            } else {

                /*
                 * Tambah wali baru.
                 */

                $guardianUser = null;

                /*
                 * Akun login wali hanya dibuat
                 * jika email wali diisi.
                 */
                if (! empty($validated['guardian_email'])) {

                    $guardianUser = User::create([
                        'name' => $validated['guardian_name'],
                        'email' => $validated['guardian_email'],
                        'password' => 'password123',
                        'role' => 'guardian',
                        'status' => 'active',
                    ]);

                    $guardianUser->forceFill([
                        'email_verified_at' => now(),
                    ])->save();
                }

                /*
                 * Data wali tetap dibuat walaupun
                 * tidak mempunyai akun login.
                 */
                $guardian = Guardian::create([
                    'user_id' => $guardianUser?->id,
                    'name' => $validated['guardian_name'],
                    'phone' => $validated['guardian_phone'],
                    'email' => $validated['guardian_email'] ?? null,
                    'relationship' => $validated['guardian_relationship'] ?? null,
                    'address' => $validated['guardian_address'] ?? null,
                ]);
            }

            /* Data siswa */

            Student::create([
                'user_id' => $studentUser->id,
                'guardian_id' => $guardian->id,
                'class_room_id' => $validated['class_room_id'],

                'entry_year' => $validated['entry_year'],
                'status' => $validated['status'],

                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?: null,

                'name' => $validated['name'],
                'gender' => $validated['gender'],

                'birth_date' => null,
                'birth_place' => null,
                'address' => null,
            ]);
        });

        return redirect()
            ->route('admin.students.index')
            ->with(
                'success',
                'Data siswa berhasil ditambahkan.'
            );
    }

    public function update(
        Request $request,
        Student $student
    ) {
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

            'gender' => [
                'required',
                Rule::in([
                    'L',
                    'P',
                ]),
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

            'guardian_phone' => [
                'required',
                'string',
                'max:20',
            ],

            'guardian_email' => [
                'nullable',
                'email',
                'max:255',

                Rule::unique('users', 'email')
                    ->ignore(
                        $student->guardian?->user_id
                    ),
            ],

            'guardian_relationship' => [
                'nullable',
                'string',
                'max:50',
            ],

            'guardian_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use (
            $student,
            $validated
        ) {

            /* Update akun siswa */

            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'status' => $validated['status'],
            ]);

            /* Ambil data wali */

            $guardian = $student->guardian;

            /*
             * Jika email wali diisi.
             */
            if (! empty($validated['guardian_email'])) {

                /*
                 * Wali sudah mempunyai akun.
                 */
                if ($guardian->user) {

                    $guardian->user->update([
                        'name' => $validated['guardian_name'],
                        'email' => $validated['guardian_email'],
                        'status' => 'active',
                    ]);

                } else {

                    /*
                     * Wali belum mempunyai akun.
                     * Buat akun guardian baru.
                     */
                    $guardianUser = User::create([
                        'name' => $validated['guardian_name'],
                        'email' => $validated['guardian_email'],
                        'password' => 'password123',
                        'role' => 'guardian',
                        'status' => 'active',
                    ]);

                    $guardianUser->forceFill([
                        'email_verified_at' => now(),
                    ])->save();

                    $guardian->update([
                        'user_id' => $guardianUser->id,
                    ]);
                }

            } else {

                /*
                 * Email wali dikosongkan.
                 *
                 * Jika sebelumnya punya akun login,
                 * akun tersebut dilepas dan dihapus.
                 */
                if ($guardian->user) {

                    $guardianUser = $guardian->user;

                    $guardian->update([
                        'user_id' => null,
                    ]);

                    $guardianUser->delete();
                }
            }

            /* Update data wali */

            $guardian->update([
                'name' => $validated['guardian_name'],
                'phone' => $validated['guardian_phone'],
                'email' => $validated['guardian_email'] ?? null,
                'relationship' => $validated['guardian_relationship'] ?? null,
                'address' => $validated['guardian_address'] ?? null,
            ]);

            /* Update data siswa */

            $student->update([
                'class_room_id' => $validated['class_room_id'],
                'entry_year' => $validated['entry_year'],
                'status' => $validated['status'],

                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?: null,

                'name' => $validated['name'],
                'gender' => $validated['gender'],
            ]);
        });

        return redirect()
            ->route('admin.students.index')
            ->with(
                'success',
                'Data siswa berhasil diperbarui.'
            );
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {

            $student->load([
                'guardian.user',
                'user',
            ]);

            $studentUser = $student->user;

            $guardian = $student->guardian;

            $guardianUser = $guardian?->user;

            /* Hapus siswa */

            $student->delete();

            /* Hapus akun siswa */

            if ($studentUser) {
                $studentUser->delete();
            }

            /*
             * Guardian hanya dihapus apabila
             * sudah tidak mempunyai siswa lain.
             */
            if (
                $guardian &&
                $guardian->students()->count() === 0
            ) {

                $guardian->delete();

                if ($guardianUser) {
                    $guardianUser->delete();
                }
            }
        });

        return redirect()
            ->route('admin.students.index')
            ->with(
                'success',
                'Data siswa berhasil dihapus.'
            );
    }
}
