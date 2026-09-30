<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected to their role dashboard', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create(['role' => $role]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route($dashboardRoute));
})->with([
    'admin' => ['admin', 'admin.dashboard'],
    'guardian' => ['guardian', 'guardian.dashboard'],
    'student' => ['student', 'student.dashboard'],
    'super admin' => ['super_admin', 'superadmin.dashboard'],
]);

test('users with unsupported roles are forbidden', function (?string $role) {
    $user = User::factory()->create(['role' => 'guardian']);
    $user->role = $role;

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertForbidden();
})->with([
    'invalid role' => 'invalid',
    'null role' => null,
]);
