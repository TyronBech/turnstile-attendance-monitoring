<?php

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

test('user name uses the middle name initial', function (): void {
    $user = User::factory()->make([
        'first_name' => 'Tyron',
        'middle_name' => 'Panti',
        'last_name' => 'Bechayda',
    ]);

    expect($user->name)->toBe('Tyron P. Bechayda');
});

test('user name uses the first word when middle name has multiple words', function (): void {
    $user = User::factory()->make([
        'first_name' => 'Jhon Carl',
        'middle_name' => 'De Torres',
        'last_name' => 'Ormita',
    ]);

    expect($user->name)->toBe('Jhon Carl D. Ormita');
});
