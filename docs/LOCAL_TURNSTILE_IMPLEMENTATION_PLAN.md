# Local Turnstile Implementation Plan

This document translates [LOCAL_TURNSTILE.md](D:/laragon/www/turnstile-attendance-monitoring/docs/LOCAL_TURNSTILE.md) into an implementation plan for this Laravel application.

It is intended for the new branch that replaces the current ESP32-oriented attendance flow with a local turnstile device integration.

---

## Goal

Implement a new attendance pipeline where:

- the turnstile device reads the RFID card
- the turnstile stores or exposes the tap event
- Laravel receives the attendance event from the device
- Laravel identifies the student or employee
- Laravel records attendance, updates monitoring, and sends SMS when needed

This plan assumes the turnstile itself is now the intelligent device and Laravel is the central processing platform.

---

## Current State in This Repo

The current codebase already has:

- a `turnstiles` table and `Turnstile` model
- Sanctum-authenticated device API routes
- attendance logging
- queued guardian SMS sending

The current implementation is still based on an older ESP32-style flow:

- device sends only `rfid`
- Laravel derives `IN` or `OUT` by alternating the user's last attendance action
- student-focused attendance is the main supported path

This does not fully match the new local-turnstile design described in `LOCAL_TURNSTILE.md`.

---

## Main Design Shift

The new implementation should move from:

```text
RFID-only scan ingestion
```

to:

```text
device event ingestion
```

That means Laravel should treat each incoming record as a full attendance event coming from a known gate.

Recommended canonical event shape:

```json
{
  "rfid_uid": "A1B2C3D4",
  "gate_code": "GATE_01_IN",
  "tapped_at": "2026-05-28 07:35:12",
  "status": "granted"
}
```

Laravel should then enrich the event with:

- source IP
- resolved gate record
- resolved student or employee
- SMS state
- raw payload for audit/debugging

---

## Recommended Delivery Approach

### Primary mode

Use **push mode** first if the supplier supports it.

```text
Turnstile -> Laravel API
```

This gives the simplest realtime flow and matches the local monitoring requirement best.

### Fallback modes

Support one of these only if required by the supplier:

- scheduled pull from device API
- vendor SDK integration
- CSV or database import through a middleware service

Push mode should remain the preferred architecture for the first implementation.

---

## Phase Plan

## Phase 1 - Confirm supplier contract

Before building the backend flow, confirm:

1. exact turnstile model
2. communication type
3. request or export format
4. authentication capability
5. whether gate identity is included in payload
6. whether timestamps come from the device
7. whether events are pushed individually or retrieved in batches

Current hardware identified from the provided photos:

- item type: `Tripod Turnstile`
- model: `CXT-SW125`
- manufacturer: `CXT Technology Co., Ltd.`
- leave factory date shown on label: `2019.11`

Important note:

- the photos confirm the physical turnstile model only
- the photos do not confirm whether this installed unit has the required controller, RFID reader, LAN module, SDK, or HTTP/TCP integration capability
- the final implementation still depends on the installed control board and whatever communication interface the supplier actually enabled

Deliverables:

- final sample payloads
- final transport choice
- final authentication choice
- decision on whether Laravel receives single events, bulk events, or both

Feasibility note for `CXT-SW125`:

- this model can likely support the physical access-control part of the project
- it may support the attendance part only if the installed controller can expose events through TCP/IP, HTTP, SDK, relay integration, or exportable logs
- based on the photos alone, the Laravel plan should be treated as `possible but not yet confirmed`

Minimum confirmation still required from the supplier or installer:

- whether the unit has an RFID access controller already installed
- whether tap logs can be pushed to a custom server
- whether logs can be pulled from the device over LAN
- whether it can identify entry and exit by device or by lane direction
- whether each device can be assigned a static IP address
- whether the controller has protocol, SDK, or API documentation

---

## Phase 2 - Finalize domain rules

Lock these business rules before refactoring services:

### RFID source of truth

Decide whether RFID stays in:

- `usr_users.rfid`

or moves to:

- `usr_student_details`
- `usr_employee_details`

For this repo, the current source of truth is `usr_users.rfid`.

### Direction logic

Direction should come from the gate record:

- `IN`
- `OUT`

Laravel should no longer infer direction by alternating the user's previous logs.

### Identity priority

Recommended lookup order:

1. student
2. employee
3. unknown RFID

### SMS rules

Lock whether SMS should send for:

- student `IN`
- student `OUT`
- delayed sync events
- duplicate repeated taps

---

## Phase 3 - Refactor the database schema

The current schema is not yet shaped for full device event ingestion.

### Update `turnstiles`

Add fields needed for turnstile identity and health:

- `gate_code`
- `direction`
- `last_seen_at`
- optional `device_type`
- optional `notes`

Recommended rules:

- `gate_code` must be unique
- static IP is preferred
- IP should be treated as a secondary identifier, not the primary one

### Refactor `attendance_logs`

Move from a student-scan table to a device-event-backed attendance table.

Recommended fields:

- `id`
- `turnstile_id`
- `user_id` nullable
- `rfid_uid`
- `direction`
- `status`
- `tapped_at`
- `source_ip`
- `raw_payload`
- `sms_status`
- timestamps

Notes:

- `user_id` should be nullable if the RFID is unknown
- `raw_payload` is important for supplier debugging and audit history
- `tapped_at` should reflect the device event time, not only server receive time

### Add `unknown_rfid_logs`

Recommended fields:

- `id`
- `turnstile_id` nullable
- `rfid_uid`
- `source_ip`
- `tapped_at`
- `raw_payload`
- timestamps

### Add `sms_logs`

Recommended fields:

- `id`
- `attendance_log_id`
- `recipient_number`
- `message`
- `provider`
- `status`
- `sent_at`
- `failed_reason`
- timestamps

This keeps SMS history independent from the main attendance table.

---

## Phase 4 - Replace the ingestion service

Refactor the attendance service from:

- `recordScan(turnstile, rfid)`

to an event-driven service that accepts a full attendance payload.

Recommended processing flow:

1. validate payload
2. resolve gate by `gate_code`
3. fallback to source IP only if needed
4. determine direction from gate record
5. resolve RFID owner
6. create attendance log if known
7. create unknown RFID log if unmatched
8. dispatch SMS job if eligible
9. update monitoring output

Important change:

- do not use alternating previous attendance rows to decide `IN` or `OUT`

---

## Phase 5 - Replace the device API contract

The current API is still ESP32-oriented and should be treated as legacy during rollout.

Recommended new endpoints:

### Receive turnstile event

```text
POST /api/v1/turnstile/events
```

Purpose:

- receive attendance event from a gate
- identify student or employee
- create attendance or unknown RFID record
- trigger SMS workflow

### Gate heartbeat

```text
POST /api/v1/turnstile/{gate_code}/heartbeat
```

Purpose:

- update device online status
- refresh `last_seen_at`
- support gate monitoring dashboard

### Monitoring logs

```text
GET /api/v1/monitor/latest-logs
```

Purpose:

- return recent attendance activity for live monitoring

### Monitoring gates

```text
GET /api/v1/monitor/gates
```

Purpose:

- show gate health and online or offline state

Authentication recommendation:

- keep Sanctum bearer tokens if the turnstile can send headers
- otherwise use an approved fallback such as IP allowlisting plus request signing

---

## Phase 6 - Refactor SMS processing

Keep SMS as a queued workflow.

Recommended flow:

```text
Event received
-> Attendance saved
-> SMS decision evaluated
-> SMS job dispatched
-> Provider called
-> sms_logs updated
```

Required behavior:

- do not send SMS directly inside the request lifecycle
- support deduplication window
- support delayed sync message wording
- mark skipped, sent, and failed states clearly

Recommended initial scope:

- student guardian SMS only
- employee attendance logs without SMS unless later required

---

## Phase 7 - Monitoring and dashboards

Start with polling before considering realtime broadcasting.

Recommended polling intervals:

- live monitoring: every 2 to 3 seconds
- guard dashboard: every 5 seconds
- gate status: every 10 to 15 seconds

Display targets:

- name
- user type
- RFID status
- gate
- direction
- tap time
- SMS status
- online or offline device state

Recommended offline rule:

- mark a gate offline if heartbeat is older than the agreed timeout window

---

## Phase 8 - Seed and configure the gate records

Prepare records for the eight physical gates.

Each physical gate should have two turnstile records:

- one `IN` device record
- one `OUT` device record

That means the Laravel side should expect 16 total gate-device records for 8 physical gates.

Recommended attributes per gate device:

- `gate_code`
- `name`
- `direction`
- `ip_address`
- `location`
- `status`

Recommended gate-code and IP-address mapping:

| Physical Gate | Direction | Gate Code | Example Static IP |
| --- | --- | --- | --- |
| Gate 01 | IN | `GATE_01_IN` | `192.168.1.101` |
| Gate 01 | OUT | `GATE_01_OUT` | `192.168.1.102` |
| Gate 02 | IN | `GATE_02_IN` | `192.168.1.103` |
| Gate 02 | OUT | `GATE_02_OUT` | `192.168.1.104` |
| Gate 03 | IN | `GATE_03_IN` | `192.168.1.105` |
| Gate 03 | OUT | `GATE_03_OUT` | `192.168.1.106` |
| Gate 04 | IN | `GATE_04_IN` | `192.168.1.107` |
| Gate 04 | OUT | `GATE_04_OUT` | `192.168.1.108` |
| Gate 05 | IN | `GATE_05_IN` | `192.168.1.109` |
| Gate 05 | OUT | `GATE_05_OUT` | `192.168.1.110` |
| Gate 06 | IN | `GATE_06_IN` | `192.168.1.111` |
| Gate 06 | OUT | `GATE_06_OUT` | `192.168.1.112` |
| Gate 07 | IN | `GATE_07_IN` | `192.168.1.113` |
| Gate 07 | OUT | `GATE_07_OUT` | `192.168.1.114` |
| Gate 08 | IN | `GATE_08_IN` | `192.168.1.115` |
| Gate 08 | OUT | `GATE_08_OUT` | `192.168.1.116` |

Naming rule:

- use `GATE_{NN}_IN` for the entry-side turnstile of a physical gate
- use `GATE_{NN}_OUT` for the exit-side turnstile of the same physical gate
- keep one static IP per device record

Best practice:

- use static IP addresses
- do not rely on IP address alone for gate identity

---

## Phase 9 - Testing strategy

Every phase should be covered with automated tests.

### Feature tests

Add coverage for:

- known student RFID event
- known employee RFID event
- unknown RFID event
- inactive user event
- invalid gate code
- heartbeat update
- SMS queued for valid student event
- SMS skipped during deduplication

### Unit tests

Add coverage for:

- event-to-gate resolution
- identity lookup priority
- SMS eligibility rules
- delayed sync behavior

### Integration validation

Before full rollout:

1. validate one real gate end-to-end
2. confirm payload mapping
3. confirm timestamps are correct
4. confirm duplicate taps behave correctly
5. confirm monitoring reflects the live device state

---

## Phase 10 - Cutover strategy

Implement the new flow in a controlled rollout.

### Recommended sequence

1. add backward-compatible schema changes
2. add new service layer for turnstile events
3. add new API endpoints
4. add monitoring endpoints
5. add SMS log tracking
6. test with one device
7. test with all 8 devices
8. retire ESP32-specific routes and logic after confirmation

### Rollout rule

Do not remove the current flow until:

- supplier integration is proven
- event payload is stable
- gate direction handling is verified
- SMS behavior is accepted

---

## File Areas Likely to Change

The implementation will likely affect these areas:

- `routes/api.php`
- `app/Http/Controllers/Api/AttendanceController.php`
- `app/Services/AttendanceService.php`
- `app/Jobs/SendAttendanceSmsJob.php`
- `app/Models/Turnstile.php`
- `app/Models/AttendanceLog.php`
- new models for unknown RFID and SMS logs
- new request classes for turnstile event ingestion
- new or updated migrations
- tests covering API, service, and SMS behavior

---

## Risks and Decisions to Resolve Early

These are the highest-value decisions to settle before implementation starts:

1. Does the supplier support push mode?
2. What exact payload does the device send?
3. Can the device send bearer tokens?
4. Is `usr_users.rfid` staying as the RFID source of truth?
5. Should unknown RFID events be stored even when the gate is unresolved?
6. Should employee attendance ever trigger SMS?
7. What deduplication window should be used?

If these are resolved first, the backend implementation becomes much more straightforward.

---

## Recommended First Build Scope

To reduce risk, the first working version should support:

- push mode only
- one attendance event endpoint
- gate resolution by `gate_code`
- known student and employee lookup
- unknown RFID logging
- guardian SMS for student `IN` only
- monitoring via polling
- heartbeat-based gate status

This keeps the first milestone small enough to validate against a real supplier device before building secondary integrations.

---

## LAN WebSocket Prototype

Because the turnstile setup is LAN-based, this repo can support a prototype that demonstrates the target process even before full hardware integration is confirmed.

Prototype goal:

- simulate gate devices on the local network
- send attendance events into Laravel
- broadcast live updates to the monitoring interface over WebSocket
- prove the end-to-end monitoring and attendance flow for demo purposes

Important scope note:

- this prototype demonstrates the planned system behavior
- it does not by itself prove that the installed `CXT-SW125` controller can natively speak WebSocket
- WebSocket should be treated as the realtime UI layer, while the gate-to-server connection remains LAN-based event delivery

### Prototype architecture

```text
[ Demo Gate Client / Gate Simulator on LAN ]
                |
                | HTTP or TCP event delivery over LAN
                v
[ Laravel API Event Endpoint ]
                |
                | validate gate_code, RFID, direction, timestamp
                v
[ Attendance Event Service ]
                |
                | save attendance log / unknown RFID / heartbeat
                v
[ Database ]
                |
                | broadcast event
                v
[ WebSocket Server ]
                |
                v
[ Live Monitoring UI / Guard Dashboard ]
```

### Prototype request flow

```text
1. Simulated gate device sends a LAN event
2. Laravel receives the event for `GATE_01_IN` or `GATE_01_OUT`
3. Laravel resolves the gate and direction
4. Laravel stores the attendance event
5. Laravel broadcasts the result to the frontend through WebSocket
6. Monitoring pages update in near realtime
```

### Recommended prototype assumptions

- one simulated device per gate-direction record
- start with `GATE_01_IN` and `GATE_01_OUT`
- use static demo LAN IP addresses
- allow mocked RFID values for testing
- keep guardian SMS optional for the prototype
- use heartbeat messages to show online and offline gate status

### Recommended prototype architecture decisions

- gate-to-Laravel traffic uses LAN delivery
- Laravel-to-frontend realtime updates use WebSocket
- direction comes from the stored gate record, not from alternating attendance history
- gate identity should use `gate_code` first and IP address second

### Prototype success criteria

- a demo gate can send an `IN` event
- a demo gate can send an `OUT` event
- monitoring updates without page refresh
- gate online and offline state can be shown
- known and unknown RFID outcomes can both be demonstrated
