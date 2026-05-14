# BPS_LIBRARY — Demo tapping integration (SNCS attendance API)

This document is for the **BPS_LIBRARY** team. The **turnstile-attendance-monitoring** app (SNCS backend) now records RFID scans over HTTP and can send **guardian SMS** via **Semaphore** using a **queued job** (batch-friendly: run multiple `php artisan queue:work` workers).

Your library app remains the **demo UI** where patrons tap RFID. SNCS is the **system of record** for the prototype attendance trail and optional SMS.

---

## What you implement on the BPS side

1. **After a successful local RFID lookup** (you already resolve the user in `RFIDController` / library flow), **call the SNCS HTTP API** with the same RFID value stored on the student in SNCS (`users.rfid`).
2. **Use one SNCS “device” token** (Laravel Sanctum personal access token for a `Turnstile` record) — treat it like a shared library kiosk reader until each gate has its own token.
3. **Handle SNCS HTTP errors** without breaking the library UX: if SNCS is down, you can still log locally only, or show a soft warning (product decision).

You do **not** need to port Semaphore code to BPS for this prototype if SNCS sends the guardian SMS.

---

## Prerequisites (data alignment)

| Item | Owner | Notes |
|------|--------|--------|
| RFID string on SNCS | SNCS / admin | `users.rfid` must match what BPS sends (same encoding, case, no extra spaces). |
| Guardian SMS number on SNCS | SNCS / admin | `users.guardian_contact_number` — SMS is sent here when Semaphore is enabled. |
| Device token | SNCS | Create a `turnstiles` row and issue a Sanctum token (see SNCS `TurnstileSeeder` / admin UI when available). |

---

## SNCS endpoint (prototype)

| Item | Value |
|------|--------|
| Method | `POST` |
| Path | `/api/v1/attendance/scan` |
| Auth | `Authorization: Bearer {turnstile_sanctum_token}` |
| Headers | `Accept: application/json`, `Content-Type: application/json` |

**JSON body:**

```json
{
  "rfid": "TESTRF01"
}
```

Use the **student’s RFID** as stored in SNCS (not the primary key).

---

## Success response (201)

Example shape:

```json
{
  "data": {
    "log_id": 12,
    "student_id": "20240001",
    "student_name": "Juan Dela Cruz",
    "action": "IN",
    "scanned_at": "2026-05-14T08:15:00+00:00",
    "turnstile": "Library Demo Gate"
  },
  "meta": { "timestamp": "..." },
  "errors": []
}
```

- **`action`**: `IN` then `OUT` then `IN` … for the same calendar day (SNCS alternates per student).
- **`turnstile`**: name of the SNCS turnstile tied to the token (good for dashboards).

---

## Error responses (typical)

| HTTP | `errors[0].code` | Meaning |
|------|------------------|---------|
| 401 | — | Missing/invalid Bearer token. |
| 403 | `USER_INACTIVE` | Turnstile disabled or student `status` false on SNCS. |
| 404 | `RFID_NOT_FOUND` | RFID not in SNCS `users.rfid`. |
| 406 | `INVALID_ACCEPT_HEADER` | Send `Accept: application/json`. |
| 422 | validation | Missing/invalid `rfid` field. |

---

## Optional: health check

`GET /api/v1/turnstile/ping` with the same Bearer token — confirms token and turnstile row are valid.

---

## SMS behavior (SNCS server)

- SNCS dispatches **`SendAttendanceSmsJob`** after a successful scan when:
  - `SEMAPHORE_ENABLED=true`
  - `SEMAPHORE_API_KEY` is set
  - student has a non-empty `guardian_contact_number`
- The job is queued **after the HTTP response is sent** (`afterResponse`) so a slow or failing Semaphore call never blocks the `201` scan response.
- Jobs are processed by **`php artisan queue:work`** (or Horizon later). Multiple workers drain the queue in parallel (“batch” throughput).
- Retry: job retries a few times with backoff; then `attendance_logs.sms_status` becomes `FAILED`.
- **Recovery:** `php artisan attendance:dispatch-pending-sms` re-queues jobs for rows still `PENDING` with a guardian number.

**BPS does not call Semaphore** for this path if you rely on SNCS SMS.

---

## Example: Laravel `Http` client from BPS

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($sncsTurnstileToken)
    ->acceptJson()
    ->asJson()
    ->post("{$sncsBaseUrl}/api/v1/attendance/scan", [
        'rfid' => $normalizedRfidFromYourScan,
    ]);

if ($response->successful()) {
    // optional: merge SNCS log_id / action into your UI
} else {
    // log + optional user-facing message
}
```

Store `SNCS_BASE_URL` and `SNCS_TURNSTILE_TOKEN` in BPS `.env` (never commit tokens).

---

## Checklist before demo day

- [ ] SNCS `.env`: `SEMAPHORE_*`, `QUEUE_CONNECTION`, worker running.
- [ ] SNCS database seeded or imported users with **matching** `rfid` and **guardian** numbers for SMS smoke test.
- [ ] BPS `.env`: base URL + turnstile token; one successful `POST` from Postman first, then from BPS code.
- [ ] Agree whether BPS still sends its **own** student SMS on time in/out (duplicate texts if both fire).

---

*Document owner: SNCS / turnstile-attendance-monitoring repo. Update when the public API version or paths change.*
