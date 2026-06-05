<?php

use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config()->set('turnstiles.gates.G01.devices.in_reader.ip', '192.168.10.11');
});

it('accepts http rfid scans when input mode is both', function (): void {
    config([
        'turnstiles.input_mode' => 'both',
        'turnstiles.http.enabled' => true,
        'turnstiles.tcp.enabled' => true,
    ]);

    Log::spy();

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.11'])
        ->postJson('/api/rfid/scan', ['card_uid' => 'BOTH123']);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('card_uid', 'BOTH123')
        ->assertJsonPath('mapped_device.gate_code', 'G01');
});

it('accepts http rfid scans when input mode is http', function (): void {
    config([
        'turnstiles.input_mode' => 'http',
        'turnstiles.http.enabled' => true,
        'turnstiles.tcp.enabled' => false,
    ]);

    Log::spy();

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.11'])
        ->postJson('/api/rfid/scan', ['card_uid' => 'HTTP123']);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('card_uid', 'HTTP123')
        ->assertJsonPath('mapped_device.gate_code', 'G01');
});

it('rejects http rfid scans when input mode is tcp', function (): void {
    config([
        'turnstiles.input_mode' => 'tcp',
        'turnstiles.http.enabled' => false,
        'turnstiles.tcp.enabled' => true,
    ]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.11'])
        ->postJson('/api/rfid/scan', ['card_uid' => 'TCP123']);

    $response->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'RFID HTTP input is disabled.');
});

it('rejects http rfid scans when input mode is disabled', function (): void {
    config([
        'turnstiles.input_mode' => 'disabled',
        'turnstiles.http.enabled' => false,
        'turnstiles.tcp.enabled' => false,
    ]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.11'])
        ->postJson('/api/rfid/scan', ['card_uid' => 'DISABLED123']);

    $response->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'RFID HTTP input is disabled.');
});

it('logs a mapped http rfid scan without authentication', function (): void {
    Log::spy();

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.11'])
        ->postJson('/api/rfid/scan', ['card_uid' => 'ABC123456']);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'RFID scan logged')
        ->assertJsonPath('source_type', 'HTTP')
        ->assertJsonPath('reader_ip', '192.168.10.11')
        ->assertJsonPath('card_uid', 'ABC123456')
        ->assertJsonPath('mapped_device.gate_code', 'G01')
        ->assertJsonPath('mapped_device.group_code', 'MON-01')
        ->assertJsonPath('mapped_device.device_type', 'in_reader')
        ->assertJsonPath('mapped_device.direction', 'IN');

    Log::shouldHaveReceived('info')
        ->once()
        ->with('RFID scan logged', Mockery::on(fn (array $context): bool => $context['mapped'] === true
            && $context['reader_ip'] === '192.168.10.11'
            && $context['card_uid'] === 'ABC123456'
            && $context['gate_code'] === 'G01'));
});

it('logs an unmapped http rfid scan', function (): void {
    Log::spy();

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.99.99'])
        ->postJson('/api/rfid/scan', ['uid' => 'UNKNOWN123']);

    $response->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unmapped RFID reader')
        ->assertJsonPath('reader_ip', '192.168.99.99')
        ->assertJsonPath('card_uid', 'UNKNOWN123')
        ->assertJsonPath('mapped_device', null);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Unmapped RFID reader', Mockery::on(fn (array $context): bool => $context['mapped'] === false
            && $context['reader_ip'] === '192.168.99.99'
            && $context['card_uid'] === 'UNKNOWN123'));
});

it('uses the raw http body as fallback card uid', function (): void {
    Log::spy();

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.11'])
        ->call('POST', '/api/rfid/scan', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'RAWCARD789');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('card_uid', 'RAWCARD789')
        ->assertJsonPath('mapped_device.gate_code', 'G01');
});
