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

test('welcome page uses the vite-managed popquery logo asset', function (): void {
    $welcomePage = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect($welcomePage)
        ->toContain("import popQueryLogo from '../../../public/PopQuery-Logo.svg';")
        ->toContain('<img src={popQueryLogo} alt="PopQuery" className="h-11 w-auto" />')
        ->not->toContain('src="/PopQuery-Logo.svg"');
});
