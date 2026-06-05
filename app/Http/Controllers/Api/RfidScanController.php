<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RfidScanLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfidScanController extends Controller
{
    public function store(Request $request, RfidScanLoggerService $rfidScanLoggerService): JsonResponse
    {
        if (! config('turnstiles.http.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'RFID HTTP input is disabled.',
            ], 403);
        }

        $rawBody = trim($request->getContent());

        $result = $rfidScanLoggerService->handle([
            'source_type' => 'HTTP',
            'reader_ip' => $request->ip(),
            'card_uid' => $this->extractCardUid($request, $rawBody),
            'raw_payload' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'query' => $request->query(),
                'input' => $request->all(),
                'raw_body' => $rawBody,
                'content_type' => $request->header('content-type'),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        return response()->json($result);
    }

    private function extractCardUid(Request $request, string $rawBody): ?string
    {
        foreach (['card_uid', 'CardNo', 'card', 'uid', 'rfid', 'tag'] as $key) {
            $value = $request->input($key);

            if (filled($value) && ! is_array($value)) {
                return trim((string) $value);
            }
        }

        return $rawBody === '' ? null : $rawBody;
    }
}
