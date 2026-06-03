<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Route::get('/_test-permission-middleware', function () {
        return 'passed';
    })->middleware(['web', 'permission:view_dashboard']);
});

test('guest is denied with 403', function () {
    $response = $this->get('/_test-permission-middleware');
    $response->assertStatus(403);
});

test('user without permission is denied with 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/_test-permission-middleware');
    $response->assertStatus(403);
});

test('user with permission is allowed', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_dashboard');

    $this->actingAs($user);

    $response = $this->get('/_test-permission-middleware');
    $response->assertOk()->assertSee('passed');
});
