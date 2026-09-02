<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user, 401);

        /** @var Guardian|null $guardian */
        $guardian = $user->guardian;

        abort_unless($guardian, 404);

        $guardian->load([
            'students.classRoom',
        ]);

        return view('guardian.profile', [
            'user' => $user,
            'guardian' => $guardian,
        ]);
    }


    public function edit()
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user, 401);

        /** @var Guardian|null $guardian */
        $guardian = $user->guardian;

        abort_unless($guardian, 404);

        return view('guardian.profile-edit', [
            'user' => $user,
            'guardian' => $guardian,
        ]);
    }


    public function update(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user, 401);

        /** @var Guardian|null $guardian */
        $guardian = $user->guardian;

        abort_unless($guardian, 404);


        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);


        $user->update([
            'name' => $validated['name'],
        ]);


        $guardian->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);


        return redirect()
            ->route('guardian.profile')
            ->with(
                'success',
                'Profil berhasil diperbarui.'
            );
    }

    public function editPassword()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user, 401);

        return view('guardian.profile-password', [
            'user' => $user,
        ]);
    }


    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user, 401);


        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);


        if (!\Illuminate\Support\Facades\Hash::check(
            $validated['current_password'],
            $user->password
        )) {

            return back()
                ->withErrors([
                    'current_password' => 'Password saat ini tidak sesuai.',
                ])
                ->onlyInput();
        }


        $user->update([
            'password' => $validated['password'],
        ]);


        return redirect()
            ->route('guardian.profile')
            ->with(
                'success',
                'Password berhasil diperbarui.'
            );
    }
}