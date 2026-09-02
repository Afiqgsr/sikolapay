<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->whereIn('role', [
                'admin',
                'super_admin',
            ]);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $admins = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('super_admin.manage-admin', [
            'admins' => $admins,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'super_admin',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'password' => $validated['password'],
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return redirect()
            ->route('superadmin.admins.index')
            ->with(
                'success',
                'Akun admin berhasil ditambahkan.'
            );
    }

    public function update(
        Request $request,
        User $admin
    ) {
        $this->ensureManagedRole($admin);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique('users', 'email')
                    ->ignore($admin->id),
            ],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'super_admin',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        if (
            Auth::id() === $admin->id
            && (
                $validated['role'] !== 'super_admin'
                || $validated['status'] !== 'active'
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'admin' =>
                        'Akun Super Admin yang sedang digunakan tidak dapat dinonaktifkan atau diubah menjadi Admin.',
                ]);
        }

        if (
            $admin->role === 'super_admin'
            && $validated['role'] !== 'super_admin'
            && $this->isLastSuperAdmin($admin)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'admin' =>
                        'Super Admin terakhir tidak dapat diubah menjadi Admin.',
                ]);
        }

        if (
            $admin->role === 'super_admin'
            && $validated['status'] === 'inactive'
            && $this->isLastActiveSuperAdmin($admin)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'admin' =>
                        'Super Admin aktif terakhir tidak dapat dinonaktifkan.',
                ]);
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $admin->update($data);

        return redirect()
            ->route('superadmin.admins.index')
            ->with(
                'success',
                'Data admin berhasil diperbarui.'
            );
    }

    public function destroy(User $admin)
    {
        $this->ensureManagedRole($admin);

        if (Auth::id() === $admin->id) {
            return back()
                ->withErrors([
                    'admin' =>
                        'Anda tidak dapat menghapus akun yang sedang digunakan.',
                ]);
        }

        if (
            $admin->role === 'super_admin'
            && $this->isLastSuperAdmin($admin)
        ) {
            return back()
                ->withErrors([
                    'admin' =>
                        'Super Admin terakhir tidak dapat dihapus.',
                ]);
        }

        $admin->delete();

        return redirect()
            ->route('superadmin.admins.index')
            ->with(
                'success',
                'Akun admin berhasil dihapus.'
            );
    }

    private function ensureManagedRole(User $admin): void
    {
        abort_unless(
            in_array(
                $admin->role,
                [
                    'admin',
                    'super_admin',
                ],
                true
            ),
            404
        );
    }

    private function isLastSuperAdmin(User $admin): bool
    {
        if ($admin->role !== 'super_admin') {
            return false;
        }

        return User::query()
            ->where('role', 'super_admin')
            ->count() <= 1;
    }

    private function isLastActiveSuperAdmin(User $admin): bool
    {
        if ($admin->role !== 'super_admin') {
            return false;
        }

        return User::query()
            ->where('role', 'super_admin')
            ->where('status', 'active')
            ->count() <= 1;
    }
}