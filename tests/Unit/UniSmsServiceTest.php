<?php

use App\Services\UniSmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('converts philippine mobile numbers to e164 format', function (): void {
    $sms = new UniSmsService('key', 'SNCS', 'https://example.test/api/sms');

    expect($sms->convertToE164Format('09171234567'))->toBe('+639171234567')
        ->and($sms->convertToE164Format('639171234567'))->toBe('+639171234567')
        ->and($sms->convertToE164Format('+639171234567'))->toBe('+639171234567')
        ->and($sms->convertToE164Format('9171234567'))->toBe('+639171234567');
});

it('sends sms using basic auth and unisms payload', function (): void {
    Http::fake([
        'https://example.test/api/sms' => Http::response([
            'message' => [
                'status' => 'pending',
                'reference_id' => 'msg_123',
            ],
        ], 201),
    ]);

    $sms = new UniSmsService('secret-key', 'SNCS', 'https://example.test/api/sms');

    expect($sms->send('09171234567', 'Hello from UniSMS'))->toBeTrue();

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://example.test/api/sms'
            && $request['recipient'] === '+639171234567'
            && $request['content'] === 'Hello from UniSMS'
            && $request['sender_id'] === 'SNCS'
            && $request->hasHeader('Authorization');
    });
});
