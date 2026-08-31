<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $student = Student::with([
            'user',
            'guardian',
            'classRoom.academicYear',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('student.profile', [
            'student' => $student,
        ]);
    }

    public function edit()
    {
        $student = Student::with([
            'user',
            'guardian',
            'classRoom.academicYear',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('student.profile-edit', [
            'student' => $student,
        ]);
    }

    public function update(Request $request)
    {
        $student = Student::with([
            'user',
            'guardian',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $student->user_id,
            ],

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'guardian_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'guardian_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'current_password' => [
                'nullable',
                'required_with:password',
            ],

            'password' => [
                'nullable',
                'required_with:current_password',
                'confirmed',
                'min:8',
            ],
        ]);

        $student->update([
            'address' => $request->address,
        ]);

        $student->user->update([
            'email' => $request->email,
        ]);

        if ($student->guardian) {
            $student->guardian->update([
                'phone' => $request->guardian_phone,
                'email' => $request->guardian_email,
            ]);
        }

        if ($request->filled('password')) {

            if (! Hash::check(
                $request->current_password,
                $student->user->password
            )) {
                return back()
                    ->withErrors([
                        'current_password' => 'Password saat ini tidak sesuai.',
                    ])
                    ->withInput();
            }

            $student->user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        $message = $request->filled('password')
            ? 'Profil dan password berhasil diperbarui.'
            : 'Profil berhasil diperbarui.';

        return redirect()
            ->route('student.profile')
            ->with('success', $message);
    }
}