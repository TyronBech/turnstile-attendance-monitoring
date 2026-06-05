<?php

namespace App\Console\Commands;

use App\Services\RfidScanLoggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListenForRfidScans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rfid:listen {--host=} {--port=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for temporary RFID scanner TCP input and log mapped gate metadata';

    /**
     * Execute the console command.
     */
    public function handle(RfidScanLoggerService $rfidScanLoggerService): int
    {
        if (! config('turnstiles.tcp.enabled')) {
            $this->warn('RFID TCP input is disabled.');

            return self::SUCCESS;
        }

        $host = (string) ($this->option('host') ?: config('turnstiles.tcp.host', '0.0.0.0'));
        $port = (int) ($this->option('port') ?: config('turnstiles.tcp.port', 9000));
        $address = "tcp://{$host}:{$port}";
        $server = @stream_socket_server($address, $errorCode, $errorMessage);

        if ($server === false) {
            $this->error("Unable to start RFID TCP listener on {$address}: {$errorMessage} ({$errorCode})");

            return self::FAILURE;
        }

        $this->info("RFID TCP listener started on {$address}");
        $this->line('Press Ctrl+C to stop.');

        while (true) {
            $connection = @stream_socket_accept($server, -1);

            if ($connection === false) {
                continue;
            }

            $this->handleConnection($connection, $rfidScanLoggerService);
        }
    }

    /**
     * @param  resource  $connection
     */
    private function handleConnection(mixed $connection, RfidScanLoggerService $rfidScanLoggerService): void
    {
        $peerName = stream_socket_get_name($connection, true) ?: '';
        $readerIp = $this->extractIpFromPeerName($peerName);

        try {
            stream_set_timeout($connection, 2);

            $rawData = fread($connection, 4096);
            $rawText = trim((string) $rawData);
            $hexPayload = bin2hex((string) $rawData);
            $parsedPayload = $this->parsePayload($rawText);

            Log::info('Raw RFID TCP payload received', [
                'reader_ip' => $readerIp,
                'peer_name' => $peerName,
                'raw_text' => $rawText,
                'raw_hex' => $hexPayload,
            ]);

            if ($rawText === '') {
                Log::warning('Empty RFID TCP payload received', [
                    'reader_ip' => $readerIp,
                    'peer_name' => $peerName,
                    'raw_hex' => $hexPayload,
                ]);
            }

            $result = $rfidScanLoggerService->handle([
                'source_type' => 'TCP',
                'reader_ip' => $readerIp,
                'reader_code' => $parsedPayload['reader_code'],
                'card_uid' => $parsedPayload['card_uid'],
                'raw_payload' => [
                    'peer_name' => $peerName,
                    'raw_text' => $rawText,
                    'raw_hex' => $hexPayload,
                ],
            ]);

            fwrite($connection, ($result['success'] ? 'OK' : 'UNMAPPED').PHP_EOL);
        } catch (Throwable $throwable) {
            Log::error('RFID TCP listener failed to process connection', [
                'reader_ip' => $readerIp,
                'peer_name' => $peerName,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            fwrite($connection, 'ERROR'.PHP_EOL);
        } finally {
            fclose($connection);
        }
    }

    /**
     * @return array{reader_code: string|null, card_uid: string|null}
     */
    private function parsePayload(string $rawText): array
    {
        if ($rawText === '') {
            return [
                'reader_code' => null,
                'card_uid' => null,
            ];
        }

        if (str_contains($rawText, '|')) {
            $segments = array_map('trim', explode('|', $rawText));

            return [
                'reader_code' => $segments[0] !== '' ? $segments[0] : null,
                'card_uid' => ($segments[1] ?? '') !== '' ? $segments[1] : null,
            ];
        }

        return [
            'reader_code' => null,
            'card_uid' => preg_match('/^[A-Za-z0-9]+$/', $rawText) === 1 ? $rawText : null,
        ];
    }

    private function extractIpFromPeerName(string $peerName): ?string
    {
        if ($peerName === '') {
            return null;
        }

        $lastColonPosition = strrpos($peerName, ':');

        if ($lastColonPosition === false) {
            return $peerName;
        }

        return substr($peerName, 0, $lastColonPosition);
    }
}
