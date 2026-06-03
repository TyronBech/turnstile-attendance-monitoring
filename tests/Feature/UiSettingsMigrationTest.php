<?php

use Illuminate\Support\Facades\Schema;

it('creates the ui settings table with the expected columns', function (): void {
    expect(Schema::hasTable('ui_settings'))->toBeTrue();

    expect(Schema::hasColumns('ui_settings', [
        'id',
        'org_name',
        'org_initial',
        'org_address',
        'org_logo',
        'org_logo_full',
        'email',
        'contact_number',
        'social_links',
        'theme_colors',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();
});
