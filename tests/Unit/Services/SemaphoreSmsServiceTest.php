<?php

use App\Services\SemaphoreSmsService;

it('converts philippine mobile numbers to semaphore format', function (): void {
    $sms = new SemaphoreSmsService('key', 'SNCS', 'https://example.test');

    expect($sms->convertToSemaphoreFormat('09171234567'))->toBe('639171234567')
        ->and($sms->convertToSemaphoreFormat('639171234567'))->toBe('639171234567')
        ->and($sms->convertToSemaphoreFormat('9171234567'))->toBe('639171234567');
});
