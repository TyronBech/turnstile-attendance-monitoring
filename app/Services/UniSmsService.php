<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UniSmsService
{
    public function __construct(
        protected string $apiKey,
        protected string $senderId,
        protected string $apiUrl,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.unisms.api_key', ''),
            (string) config('services.unisms.sender_id', ''),
            (string) config('services.unisms.api_url', 'https://unismsapi.com/api/sms'),
        );
    }

    public function send(string $toNumber, string $message): bool
    {
        if ($toNumber === '') {
            Log::warning('SMS: No phone number provided');

            return false;
        }

        $payload = [
            'recipient' => $this->convertToE164Format($toNumber),
            'content' => $message,
        ];

        if ($this->senderId !== '') {
            $payload['sender_id'] = $this->senderId;
        }

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($this->apiKey, '')
                ->post($this->apiUrl, $payload);

            $responseData = $response->json();
            $messagePayload = is_array($responseData) ? data_get($responseData, 'message') : null;
            $status = is_array($messagePayload) ? (string) data_get($messagePayload, 'status', '') : '';
            $referenceId = is_array($messagePayload) ? (string) data_get($messagePayload, 'reference_id', '') : '';

            if ($response->successful() && $referenceId !== '' && in_array($status, ['pending', 'retrying', 'sent'], true)) {
                Log::info("UniSMS accepted SMS for {$payload['recipient']}", [
                    'response' => $responseData,
                ]);

                return true;
            }

            Log::error("UniSMS rejected SMS to {$payload['recipient']}. HTTP Status: {$response->status()}. Raw Body: {$response->body()}");

            return false;
        } catch (\Throwable $e) {
            Log::error("Failed to send UniSMS to {$payload['recipient']}: {$e->getMessage()}");

            return false;
        }
    }

    public function convertToE164Format(string $phoneNumber): string
    {
        $cleaned = preg_replace('/\D/', '', $phoneNumber) ?? '';

        if (str_starts_with($cleaned, '63')) {
            return '+'.$cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            return '+63'.substr($cleaned, 1);
        }

        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '9')) {
            return '+63'.$cleaned;
        }

        if (str_starts_with($cleaned, '9')) {
            return '+63'.substr($cleaned, 1);
        }

        if (str_starts_with($phoneNumber, '+')) {
            return $phoneNumber;
        }

        return '+'.$cleaned;
    }
}
