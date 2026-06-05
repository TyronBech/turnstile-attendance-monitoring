<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RfidScanLoggerService
{
    public function __construct(private TurnstileMappingService $turnstileMappingService) {}

    /**
     * Normalize, map, and log an RFID scan without saving it to the database.
     *
     * @param array{
     *     source_type?: string,
     *     reader_ip?: string|null,
     *     reader_code?: string|null,
     *     card_uid?: string|null,
     *     raw_payload?: mixed
     * } $data
     * @return array{
     *     success: bool,
     *     message: string,
     *     source_type: string,
     *     reader_ip: string|null,
     *     reader_code: string|null,
     *     card_uid: string|null,
     *     mapped_device: array<string, mixed>|null
     * }
     */
    public function handle(array $data): array
    {
        $sourceType = strtoupper(trim((string) ($data['source_type'] ?? 'UNKNOWN')));
        $readerIp = $this->normalizeNullableString($data['reader_ip'] ?? null);
        $readerCode = $this->normalizeNullableString($data['reader_code'] ?? null);
        $rawPayload = $data['raw_payload'] ?? null;
        $cardUid = $this->extractCardUid($data['card_uid'] ?? null, $rawPayload);
        $mappedDevice = $this->turnstileMappingService->findDeviceByIp($readerIp);
        $receivedAt = now()->toIso8601String();

        $logContext = [
            'source_type' => $sourceType,
            'reader_ip' => $readerIp,
            'reader_code' => $readerCode,
            'card_uid' => $cardUid,
            'mapped' => $mappedDevice !== null,
            'gate_code' => $mappedDevice['gate_code'] ?? null,
            'gate_type' => $mappedDevice['gate_type'] ?? null,
            'group_code' => $mappedDevice['group_code'] ?? null,
            'device_type' => $mappedDevice['device_type'] ?? null,
            'direction' => $mappedDevice['direction'] ?? null,
            'raw_payload' => $rawPayload,
            'received_at' => $receivedAt,
        ];

        if ($mappedDevice !== null) {
            Log::info('RFID scan logged', $logContext);
        } else {
            Log::warning('Unmapped RFID reader', $logContext);
        }

        return [
            'success' => $mappedDevice !== null,
            'message' => $mappedDevice !== null ? 'RFID scan logged' : 'Unmapped RFID reader',
            'source_type' => $sourceType,
            'reader_ip' => $readerIp,
            'reader_code' => $readerCode,
            'card_uid' => $cardUid,
            'mapped_device' => $mappedDevice,
        ];
    }

    private function extractCardUid(mixed $cardUid, mixed $rawPayload): ?string
    {
        $normalizedCardUid = $this->normalizeNullableString($cardUid);

        if ($normalizedCardUid !== null) {
            return $normalizedCardUid;
        }

        if (is_array($rawPayload)) {
            foreach (['card_uid', 'CardNo', 'card', 'uid', 'rfid', 'tag'] as $key) {
                $normalizedValue = $this->normalizeNullableString(Arr::get($rawPayload, $key));

                if ($normalizedValue !== null) {
                    return $normalizedValue;
                }
            }

            return null;
        }

        $rawText = $this->normalizeNullableString($rawPayload);

        if ($rawText === null) {
            return null;
        }

        if (Str::contains($rawText, '|')) {
            $segments = array_map('trim', explode('|', $rawText));

            return $this->normalizeNullableString($segments[1] ?? null);
        }

        return preg_match('/^[A-Za-z0-9]+$/', $rawText) === 1 ? $rawText : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
