# RFID Testing

This temporary RFID input layer is for on-site testing only. It receives, normalizes, maps, and logs scans without saving attendance, broadcasting to monitors, or unlocking controllers.

## Input Mode Toggle

Configure which temporary RFID inputs are allowed:

```env
RFID_INPUT_MODE=both
```

Allowed values:

- `http`
- `tcp`
- `both`
- `disabled`

Use `both` during on-site testing when either scanner input path may be used. Use `http` if the scanner supports HTTP POST. Use `tcp` if the scanner supports TCP client/server IP and port mode. Use `disabled` to temporarily reject or stop RFID input.

After changing `.env`, clear cached configuration:

```bash
php artisan config:clear
```

## HTTP Input

Endpoint:

```bash
POST /api/rfid/scan
GET /api/rfid/scan
```

Example JSON request:

```bash
curl -X POST http://SERVER_IP/api/rfid/scan \
  -H "Content-Type: application/json" \
  -d '{"card_uid":"ABC123456"}'
```

The endpoint also accepts form data, query string values, and raw request bodies. It checks these card keys first: `card_uid`, `CardNo`, `card`, `uid`, `rfid`, and `tag`.

## TCP Input

Start the TCP listener:

```bash
php artisan rfid:listen
```

Override host and port:

```bash
php artisan rfid:listen --host=0.0.0.0 --port=9000
```

Example netcat test:

```bash
echo "ABC123456" | nc SERVER_IP 9000
```

Pipe-separated payloads are also supported:

```bash
echo "DEVICE001|ABC123456|2026-06-01T10:00:00" | nc SERVER_IP 9000
```

## Environment Mapping

RFID reader IPs are mapped through `.env` values loaded by `config/turnstiles.php`.

```env
RFID_TCP_HOST=0.0.0.0
RFID_TCP_PORT=9000

GATE_01_GROUP=MON-01
GATE_01_TYPE=IN_OUT
GATE_01_IN_READER_IP=192.168.1.101
GATE_01_OUT_READER_IP=192.168.1.102
GATE_01_CONTROLLER_IP=
```

If a scanner IP does not match any configured reader IP, the scan is still logged as unmapped.

## Logs

Laravel writes these scan logs to the configured log channel. For local development, check:

```bash
storage/logs/laravel.log
```
