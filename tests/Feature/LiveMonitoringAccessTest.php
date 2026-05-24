<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('live monitoring users are redirected to attendance display after login', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Live_Monitoring->value);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('attendance-display', absolute: false));
});

test('live monitoring users can access the attendance display page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Live_Monitoring->value);

    $this->actingAs($user)
        ->get(route('attendance-display'))
        ->assertOk();
});

test('live monitoring users cannot access dashboard or settings pages', function (string $routeName): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Live_Monitoring->value);

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'dashboard' => 'dashboard',
    'profile settings' => 'profile.edit',
]);
