# sncs-rfid-api-and-admin

**SNCS RFID Attendance — API, admin, live monitoring, and reports**

This repository is the **server and web application** for the RFID Turnstile Attendance & SMS Notification System. It receives tap events from gate hardware, validates them, logs attendance, dispatches SMS to parents, and provides **staff-facing admin**: configuration, **realtime monitoring**, and **reports** — in one Laravel + Inertia deployment.

What lives **elsewhere**:

- **`sncs-gate-firmware`** — microcontroller firmware (e.g. ESP32 / Arduino-class boards): RFID read, relay, offline queue, sync to this server.

---

## Table of Contents

1. [My Scope](#1-my-scope)
2. [How It Interacts With Others](#2-how-it-interacts-with-others)
3. [System Architecture](#3-system-architecture)
4. [API Endpoints (gate devices)](#4-api-endpoints-gate-devices)
5. [Tap Event Processing Flow](#5-tap-event-processing-flow)
6. [Offline / Store-and-Forward Handling](#6-offline--store-and-forward-handling)
7. [SMS Dispatch Logic](#7-sms-dispatch-logic)
8. [Database Ownership](#8-database-ownership)
9. [Tech Stack](#9-tech-stack)
10. [Environment Setup](#10-environment-setup)
11. [Device Token Management](#11-device-token-management)
12. [Responsibility Boundaries](#12-responsibility-boundaries)

---

## 1. My Scope

This repo owns the following:

**Gate-facing HTTP API**

- **Tap endpoint** — receives every RFID scan from gate hardware
- **Student/RFID resolution** — maps incoming RFID UIDs to student records
- **Attendance logging** — writes immutable records to the database per tap event
- **Deduplication engine** — prevents duplicate SMS within a configurable time window
- **SMS dispatch** — queues and sends parent notifications via Semaphore Philippines
- **Queue worker** — async job processing via Laravel Queues (Redis or DB driver)
- **Whitelist endpoint** — serves active RFID UIDs for offline validation on the device
- **Heartbeat endpoint** — receives gate online/offline status pings
- **Device authentication** — issues and validates API tokens per gate device

**Web application (same codebase)**

- **Admin UI** (Inertia + React) — students, parents, gates, RFID cards, settings
- **Live monitoring** — realtime or near-realtime views of taps, gates, or attendance as implemented in the app
- **Reports** — attendance and related exports/views from the same application
- **Device token UX** — create/rotate/revoke gate tokens and document provisioning for firmware

What this repo does **not** own:

- On-device firmware (`sncs-gate-firmware`)
- Physical hardware, relay wiring, or site networking

---

## 2. How It Interacts With Others

```
┌─────────────────────────────────────────────────────────────┐
│                     sncs-gate-firmware                      │
│  MCU (e.g. ESP32) + RFID reader + RTC + relay               │
│                                                             │
│  - Reads RFID tap                                           │
│  - Controls turnstile relay (open/close)                    │
│  - Stores events locally when offline (e.g. LittleFS)      │
│  - Flushes queue when back online                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │  POST /api/v1/tap
                           │  GET  /api/v1/whitelist
                           │  POST /api/v1/gates/{id}/heartbeat
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              sncs-rfid-api-and-admin  ← (THIS REPO)           │
│  Laravel 13 + Inertia (React) + MySQL + Redis + Semaphore   │
│                                                             │
│  Device API:                                                │
│    - Validate device token                                  │
│    - Resolve RFID UID → student                             │
│    - Log attendance, dedupe SMS, dispatch queue jobs        │
│    - Whitelist + heartbeat                                  │
│                                                             │
│  Web app (authenticated staff):                             │
│    - Admin CRUD + settings                                  │
│    - Live monitoring dashboards                             │
│    - Reports                                                │
│                                                             │
│  Single MySQL database — migrations and schema live here    │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. System Architecture

```
[ Gate Device ]
        │
        │ HTTP POST (rfid_uid, gate_id, direction, tapped_at, device_token)
        ▼
[ POST /api/v1/tap ] ← Nginx + PHP-FPM
        │
        ├── Validate device_token → 401 if invalid
        ├── Resolve rfid_uid → student record
        │     └── Unknown UID → log unknown tap, return 200, stop
        ├── Deduplication check
        │     └── Duplicate within window → log, return 200, skip SMS
        ├── Write attendance_log (immutable)
        ├── Dispatch SMS job → Laravel Queue
        │
        ▼
[ Queue Worker ]
        │
        ├── Check tapped_at age
        │     ├── ≤ 60 min late → normal SMS template
        │     └── > 60 min late → delayed SMS template or skip
        ├── Check parent contact exists and is active
        ├── Send SMS via Semaphore API
        └── Write sms_log (status: queued → sent/failed)
```

The **Inertia/React** layer reads the same database (and authorized API routes) for dashboards, monitoring, and reports — no separate admin service or database owner.

---

## 4. API Endpoints (gate devices)

All endpoints under `/api/v1/` require a valid `device_token` in the `Authorization` header.

### `POST /api/v1/tap`

Receives an RFID tap event from a registered gate device.

**Request:**

```json
{
  "rfid_uid":   "A1B2C3D4",
  "gate_id":    "GATE_1",
  "direction":  "IN",
  "tapped_at":  "2026-05-13T07:45:00Z",
  "queued":     false
}
```

> `queued: true` indicates the event was stored offline and is being flushed late.

**Response:**

```json
{
  "status":       "granted",
  "student_name": "Juan dela Cruz",
  "message":      "Welcome!",
  "response_ms":  120
}
```

**Status values:**

| Status | Meaning |
|--------|---------|
| `granted` | Valid student, gate should open |
| `denied` | Student inactive or suspended |
| `unknown` | RFID UID not in system |
| `duplicate` | Within deduplication window |

---

### `GET /api/v1/whitelist`

Returns all active RFID UIDs for offline validation on the gate device.

**Response:**

```json
{
  "synced_at": "2026-05-13T06:00:00Z",
  "uids": [
    { "rfid_uid": "A1B2C3D4", "student_name": "Juan dela Cruz", "status": "active" },
    { "rfid_uid": "E5F6G7H8", "student_name": "Maria Santos",   "status": "active" }
  ]
}
```

> Firmware typically syncs on boot, on an interval while online, and immediately after reconnecting.

---

### `POST /api/v1/gates/{id}/heartbeat`

Receives a periodic ping from the gate device to track online/offline status.

**Request:**

```json
{
  "gate_id":    "GATE_1",
  "firmware_v": "1.0.3",
  "queue_count": 0
}
```

> `queue_count` tells the backend how many events are still stored locally on the device.

**Response:**

```json
{
  "status": "ok",
  "server_time": "2026-05-13T07:45:00Z"
}
```

> Firmware may use `server_time` to correct RTC drift.

---

## 5. Tap Event Processing Flow

```
Receive POST /api/v1/tap
        │
        ▼
1. Authenticate device_token
   └── Invalid → HTTP 401, log security event, stop

        │
        ▼
2. Resolve rfid_uid → student
   └── Not found → log unknown_tap, return 200 (granted: false), stop

        │
        ▼
3. Check student status
   └── Inactive/suspended → log denied_tap, return 200 (status: denied), stop

        │
        ▼
4. Deduplication check
   └── Same student tapped within window → log duplicate, return 200, skip SMS

        │
        ▼
5. Write attendance_log (immutable)

        │
        ▼
6. Return response to gate device
   (turnstile opens based on this response)

        │
        ▼
7. Dispatch SMS job to queue
   (async — does not affect response time)
```

---

## 6. Offline / Store-and-Forward Handling

When the gate loses internet, firmware stores tap events locally and flushes them once connectivity is restored. This API handles those late-arriving events correctly.

**How late events are identified:**

```json
{
  "queued":    true,
  "tapped_at": "2026-05-13T07:45:00Z"
}
```

**SMS behavior for late events:**

```php
$minutesLate = now()->diffInMinutes($tap->tapped_at);

if ($minutesLate <= 60) {
    // Dispatch normal SMS
    // "Juan dela Cruz entered Main Gate at 7:45 AM."

} elseif ($minutesLate <= 480) {
    // Dispatch delayed SMS template
    // "Juan dela Cruz entered Main Gate at 7:45 AM. (Delayed notification)"

} else {
    // Skip SMS, attendance still recorded
    // Log skip reason: too_late
}
```

> Thresholds (60 min, 480 min) are configurable via `.env` — the admin UI in this app can expose these as settings.

**Attendance record is always written regardless of SMS outcome.**

---

## 7. SMS Dispatch Logic

SMS is handled **asynchronously** via Laravel Queue to keep tap endpoint response time under ~200ms.

**Dispatch rules:**

1. Only fires on `direction: IN` by default (configurable)
2. Skips if parent contact is missing, malformed, or inactive
3. Skips if within deduplication window (default: 30 minutes)
4. Skips if event is too old (configurable threshold)
5. Retries up to 3 times with exponential backoff on failure

**SMS Templates:**

```
Normal:
[SNCS] {student_name} entered {gate_name} at {time}.

Delayed:
[SNCS] {student_name} entered {gate_name} at {time}. (Delayed notification)

Absent alert (future):
[SNCS] {student_name} has not arrived as of {threshold_time}.
```

**SMS Provider:** Semaphore Philippines (`https://api.semaphore.co/api/v4/messages`)

**All SMS outcomes are written to `sms_logs`:**

| Field | Values |
|-------|--------|
| status | `queued` `sent` `delivered` `failed` `skipped` |
| skip_reason | `duplicate` `no_contact` `too_late` `inactive_contact` |

---

## 8. Database Ownership

This application **owns and runs all migrations**. Admin, monitoring, and reports are **not** a separate database consumer repo — they use the same schema and deployment.

**Representative tables:**

| Table | Description |
|-------|-------------|
| `students` | Student profiles |
| `rfid_cards` | RFID UID to student mappings |
| `parent_contacts` | Parent/guardian contacts with SMS opt-in flags |
| `gates` | Gate config, device tokens, heartbeat timestamps |
| `attendance_logs` | Immutable tap event records |
| `sms_logs` | SMS dispatch records with delivery status |
| `unknown_taps` | Unrecognized RFID UID events |
| `audit_logs` | Admin action trail |

**Schema changes:**

Coordinate migrations and deploys as a single app. Feature work that touches both device API behavior and admin UX ships in this repository (e.g. merge to `develop`).

---

## 9. Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 13 (PHP 8.5) |
| Web UI | Inertia v3 + React 19 + Tailwind CSS v4 |
| Database | MySQL 8.x |
| Queue | Laravel Queues (Redis driver, DB fallback) |
| SMS Provider | Semaphore Philippines |
| Web Server | Nginx + PHP-FPM |
| Hosting | Hostinger VPS KVM 2 (₱700–900/mo) — example |

---

## 10. Environment Setup

```env
APP_NAME="SNCS RFID API & Admin"
APP_ENV=production
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sncs_attendance
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SEMAPHORE_API_KEY=
SEMAPHORE_SENDER_NAME=SNCS

# SMS Dispatch Thresholds (minutes)
SMS_DELAY_THRESHOLD_NORMAL=60
SMS_DELAY_THRESHOLD_LATE=480

# Deduplication window (minutes)
SMS_DEDUP_WINDOW=30
```

---

## 11. Device Token Management

Each physical gate device is issued a unique API token stored in the `gates` table. The token is included in every request from firmware.

**Token lifecycle:**

```
Staff creates or edits gate in this app (admin UI)
        │
        ▼
Application generates token (e.g. API / action from admin)
        │
        ▼
Token stored in gates.device_token (hashed)
        │
        ▼
Staff copies plaintext token → programs into sncs-gate-firmware
        │
        ▼
Device uses token on every request:
Authorization: Bearer {device_token}
```

**Token rotation** can support a grace period (e.g. old token valid briefly while the new token is flashed to the device).

---

## 12. Responsibility Boundaries

| Concern | Owner |
|---------|--------|
| Tap endpoint, SMS dispatch, queue worker | **sncs-rfid-api-and-admin (this repo)** |
| Whitelist endpoint, heartbeat endpoint | **sncs-rfid-api-and-admin (this repo)** |
| Database schema and migrations | **sncs-rfid-api-and-admin (this repo)** |
| Admin UI, live monitoring, reports | **sncs-rfid-api-and-admin (this repo)** |
| Student/parent/gate management UI | **sncs-rfid-api-and-admin (this repo)** |
| Gate token generation UI | **sncs-rfid-api-and-admin (this repo)** |
| Firmware, offline storage, relay control | **sncs-gate-firmware** |
| Physical hardware, wiring, network setup | Client responsibility |
| VPS provisioning and hosting fees | Client responsibility |
| Semaphore account and SMS credits | Client responsibility |
| Sender ID registration (NTC/telco) | Client responsibility |

---

*Document version: 2.0 | Project: SNCS RFID Attendance System | Classification: Internal*
