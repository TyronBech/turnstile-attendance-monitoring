<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreSmsService
{
    public function __construct(
        protected string $apiKey,
        protected string $senderName,
        protected string $apiUrl,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.semaphore.api_key', ''),
            (string) config('services.semaphore.sender_name', 'SNCS'),
            (string) config('services.semaphore.api_url', 'https://api.semaphore.co/v1/messages'),
        );
    }

    /**
     * Send one SMS via Semaphore (same payload shape as BPS_LIBRARY SmsService).
     */
    public function send(string $toNumber, string $message): bool
    {
        if ($toNumber === '') {
            Log::warning('SMS: No phone number provided');

            return false;
        }

        $toNumber = $this->convertToSemaphoreFormat($toNumber);

        try {
            $payload = [
                'apikey' => $this->apiKey,
                'number' => $toNumber,
                'message' => $message,
            ];

            if ($this->senderName !== '') {
                $payload['sendername'] = $this->senderName;
            }

            $response = Http::timeout(15)->post($this->apiUrl, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData[0]['status']) && (string) $responseData[0]['status'] === '1') {
                Log::info("SMS sent successfully to {$toNumber}", [
                    'response' => $responseData,
                ]);

                return true;
            }

            $errorMsg = is_array($responseData) && isset($responseData[0]['message'])
                ? (string) $responseData[0]['message']
                : 'Unknown error';
            Log::error("Failed to send SMS to {$toNumber}: {$errorMsg}", [
                'response' => $responseData,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error("Failed to send SMS to {$toNumber}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Convert Philippine local numbers to Semaphore format (09… → 639…).
     */
    public function convertToSemaphoreFormat(string $phoneNumber): string
    {
        $cleaned = preg_replace('/\D/', '', $phoneNumber) ?? '';

        if (str_starts_with($cleaned, '63')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            return '63'.substr($cleaned, 1);
        }

        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '9')) {
            return '63'.$cleaned;
        }

        if (str_starts_with($cleaned, '9')) {
            return '63'.substr($cleaned, 1);
        }

        return $cleaned;
    }
}
