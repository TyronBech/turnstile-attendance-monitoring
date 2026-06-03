<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shares computed theme settings from ui settings', function (): void {
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Z0MsAAAAASUVORK5CYII=',
        true,
    );

    DB::table('ui_settings')->insert([
        'org_name' => 'Sto. Nino Catholic School, Inc.',
        'org_initial' => 'SNCS',
        'org_address' => 'Signal Village, Taguig City',
        'org_logo' => $png,
        'org_logo_full' => 'logo-full',
        'email' => 'sncslib@sncstaguig.com',
        'contact_number' => '(02) 8252-9613-000',
        'social_links' => json_encode(['facebook' => 'https://facebook.com/sncs'], JSON_THROW_ON_ERROR),
        'theme_colors' => json_encode([
            'primary' => '#e01a1c',
            'secondary' => '#f7f7f7',
            'tertiary' => '#ffcf01',
        ], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('uiSettings.orgName', 'Sto. Nino Catholic School, Inc.')
            ->where('uiSettings.orgInitial', 'SNCS')
            ->where('uiSettings.email', 'sncslib@sncstaguig.com')
            ->where('uiSettings.themeColors.primary', '#e01a1c')
            ->where('uiSettings.themeColors.secondary', '#f7f7f7')
            ->where('uiSettings.themePalette.primary.500', '224 26 28')
            ->where('uiSettings.themePalette.secondary.500', '247 247 247')
            ->where('uiSettings.logoUrl', fn (string $logoUrl): bool => str_starts_with($logoUrl, 'data:image/png;base64,'))
        );
});

test('dashboard falls back to default theme colors when ui settings are missing or invalid', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('uiSettings.themeColors.primary', '#20246b')
            ->where('uiSettings.themeColors.secondary', '#ebf5ff')
            ->where('uiSettings.themeColors.tertiary', '#ffcf01')
            ->where('uiSettings.themePalette.primary.500', '32 36 107')
            ->where('uiSettings.logoUrl', null)
        );
});
