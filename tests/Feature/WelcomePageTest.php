<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can view the welcome page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('canRegister', false)
        );
});
