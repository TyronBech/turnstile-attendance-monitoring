<?php

namespace App\Services;

class TurnstileMappingService
{
    /**
     * Find the configured gate/device metadata for a scanner IP address.
     *
     * @return array{
     *     gate_code: string,
     *     gate_type: string,
     *     group_code: string,
     *     device_type: string,
     *     direction: string|null,
     *     ip: string
     * }|null
     */
    public function findDeviceByIp(?string $ip): ?array
    {
        $normalizedIp = $this->normalizeIp($ip);

        if ($normalizedIp === null) {
            return null;
        }

        /** @var array<string, array{type?: string, group?: string, devices?: array<string, array{ip?: string|null, direction?: string|null}>}> $gates */
        $gates = config('turnstiles.gates', []);

        foreach ($gates as $gateCode => $gate) {
            foreach ($gate['devices'] ?? [] as $deviceType => $device) {
                $deviceIp = $this->normalizeIp($device['ip'] ?? null);

                if ($deviceIp === null || $deviceIp !== $normalizedIp) {
                    continue;
                }

                return [
                    'gate_code' => $gateCode,
                    'gate_type' => (string) ($gate['type'] ?? ''),
                    'group_code' => (string) ($gate['group'] ?? ''),
                    'device_type' => $deviceType,
                    'direction' => $device['direction'] ?? null,
                    'ip' => $deviceIp,
                ];
            }
        }

        return null;
    }

    private function normalizeIp(?string $ip): ?string
    {
        $ip = trim((string) $ip);

        return $ip === '' ? null : $ip;
    }
}
