<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

test('logging channels are correctly configured', function () {
    $general = config('logging.channels.general');
    $errors = config('logging.channels.errors');

    expect($general)->not->toBeNull()
        ->and($general['driver'])->toBe('daily')
        ->and($general['path'])->toBe(storage_path('logs/general.log'))
        ->and($general['days'])->toBe(60);

    expect($errors)->not->toBeNull()
        ->and($errors['driver'])->toBe('daily')
        ->and($errors['path'])->toBe(storage_path('logs/errors.log'))
        ->and($errors['level'])->toBe('error')
        ->and($errors['days'])->toBe(90);
});

test('default logging stack includes general and errors channels', function () {
    $stack = config('logging.channels.stack');

    expect($stack)->not->toBeNull()
        ->and($stack['driver'])->toBe('stack')
        ->and($stack['channels'])->toContain('general', 'errors');
});

test('logging writes to general and errors daily files based on level', function () {
    $date = now()->format('Y-m-d');
    $generalFile = storage_path("logs/general-{$date}.log");
    $errorsFile = storage_path("logs/errors-{$date}.log");

    if (File::exists($generalFile)) {
        File::delete($generalFile);
    }
    if (File::exists($errorsFile)) {
        File::delete($errorsFile);
    }

    Log::info('TEST_GENERAL_LOG_ONLY_MESSAGE');

    expect(File::exists($generalFile))->toBeTrue();
    expect(File::exists($errorsFile))->toBeFalse();

    $generalContent = File::get($generalFile);
    expect($generalContent)->toContain('TEST_GENERAL_LOG_ONLY_MESSAGE');

    File::delete($generalFile);

    Log::error('TEST_ERROR_LOG_MESSAGE');

    expect(File::exists($generalFile))->toBeTrue();
    expect(File::exists($errorsFile))->toBeTrue();

    $generalContent = File::get($generalFile);
    $errorsContent = File::get($errorsFile);

    expect($generalContent)->toContain('TEST_ERROR_LOG_MESSAGE');
    expect($errorsContent)->toContain('TEST_ERROR_LOG_MESSAGE');

    File::delete($generalFile);
    File::delete($errorsFile);
});
