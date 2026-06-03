<?php

use App\Models\StudentDetail;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'student_id' => '1234567890',
        'rfid' => 'ABCDEF123456',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'test@example.com',
        'guardian_name' => 'Jane Doe',
        'guardian_contact_number' => '09123456789',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = auth()->user();

    expect($user->student_id)->toBe('1234567890');

    $this->assertDatabaseHas((new StudentDetail)->getTable(), [
        'user_id' => $user->id,
        'id_number' => '1234567890',
        'guardian_name' => 'Jane Doe',
        'guardian_contact_number' => '09123456789',
    ]);
});
