# User Schema Refactor

This document describes the refactor that moved the application from a single `users` table layout to a split schema using:

- `usr_users`
- `usr_student_details`
- `usr_employee_details`

It also covers how the attendance scan API and guardian SMS behavior were adjusted.

---

## Summary

The old structure stored shared account fields and student-only fields in one table.

The new structure separates them:

| Table | Purpose |
|------|---------|
| `usr_users` | Shared account/authentication data for all users |
| `usr_student_details` | Student-only fields such as student ID and guardian contact |
| `usr_employee_details` | Employee-only fields such as employee ID and role |

This change makes the schema closer to the legacy structure shown in phpMyAdmin while keeping Laravel auth and application logic working.

---

## New Table Design

### `usr_users`

Shared fields only:

- `id`
- `rfid`
- `first_name`
- `middle_name`
- `last_name`
- `email`
- `email_verified_at`
- `password`
- `remember_token`
- `status`
- timestamps
- soft deletes
- Fortify two-factor columns

Removed from `usr_users`:

- `student_id`
- `guardian_name`
- `guardian_contact_number`

### `usr_student_details`

Student-only fields:

- `id`
- `user_id`
- `id_number`
- `level`
- `section`
- `guardian_name`
- `guardian_contact_number`
- `active_id_number`
- timestamps
- soft deletes

Notes:

- `user_id` points to `usr_users.id`
- `id_number` is unique
- guardian contact data exists only here

### `usr_employee_details`

Employee-only fields:

- `id`
- `user_id`
- `employee_id`
- `employee_role`
- `active_employee_id`
- timestamps
- soft deletes

---

## Migration Flow

The refactor was implemented in stages so both fresh installs and existing databases can be supported.

### Fresh install behavior

Fresh migrations now create:

- `usr_users`
- `usr_student_details`
- `usr_employee_details`

with foreign keys already pointing to `usr_users`.

### Existing database upgrade behavior

For an existing database:

1. `users` is renamed to `usr_users`
2. existing student fields are copied from `usr_users` into `usr_student_details`
3. `student_id`, `guardian_name`, and `guardian_contact_number` are dropped from `usr_users`

The migration responsible for moving existing student data is:

- [2026_05_21_120004_move_student_fields_from_usr_users_to_usr_student_details.php](D:/laragon/www/turnstile-attendance-monitoring/database/migrations/2026_05_21_120004_move_student_fields_from_usr_users_to_usr_student_details.php:1)

Important:

- existing student records are preserved before the old columns are removed
- default placeholder values are used for `level` and `section` when old data does not contain them

---

## Model Behavior

The application still uses the `User` model for authentication.

### `App\Models\User`

`User` now points to:

- `usr_users`

The model also exposes these values as virtual attributes:

- `student_id`
- `guardian_name`
- `guardian_contact_number`

Those values are no longer stored in `usr_users`. They are read from and written to the related `studentDetail` record.

### `App\Models\StudentDetail`

A new model was added for:

- `usr_student_details`

Relationship:

- `User hasOne StudentDetail`
- `StudentDetail belongsTo User`

This lets existing controllers, forms, and API responses continue using `$user->student_id` without forcing a full rewrite of every caller.

---

## Seeder Changes

Seeders were updated to match the split schema.

### `DatabaseSeeder`

The default test/admin user is now created without a student profile.

This matters because not every user in `usr_users` should be treated as a student.

### `StudentSeeder`

Each seeded student now creates:

1. a base `usr_users` row
2. a related `usr_student_details` row

Student-only values are written to `usr_student_details`:

- `id_number`
- `level`
- `section`
- `guardian_name`
- `guardian_contact_number`
- `active_id_number`

---

## API Impact

Yes, this change affects the attendance scan and SMS flow.

Those areas were refactored so they now use student detail records correctly.

### Attendance scan lookup

The scan flow still starts with RFID in `usr_users`, but it now requires the scanned user to also have a `studentDetail` record.

Effect:

- users without a student detail record are not treated as valid student scan targets
- employee or admin accounts will not accidentally behave like student accounts

### API response

The scan endpoint still returns:

- `student_id`
- `student_name`
- `action`
- `scanned_at`

But `student_id` now comes from `usr_student_details.id_number`.

---

## SMS Behavior

Guardian SMS is now student-only by design.

### Before

SMS eligibility checked:

- `users.guardian_contact_number`

### After

SMS eligibility checks:

- `user.studentDetail.guardian_contact_number`

This affects:

- `AttendanceService`
- `SendAttendanceSmsJob`
- `attendance:dispatch-pending-sms`

Effect:

- only users with a student detail record can trigger guardian SMS
- employee records will never send guardian SMS unless they incorrectly have a student detail record
- attendance logs still use `user_id`, but guardian contact resolution is now student-detail-based

---

## Files Changed

Main schema/model changes:

- [app/Models/User.php](D:/laragon/www/turnstile-attendance-monitoring/app/Models/User.php:1)
- [app/Models/StudentDetail.php](D:/laragon/www/turnstile-attendance-monitoring/app/Models/StudentDetail.php:1)
- [database/migrations/0001_01_01_000000_create_users_table.php](D:/laragon/www/turnstile-attendance-monitoring/database/migrations/0001_01_01_000000_create_users_table.php:1)
- [database/migrations/2026_05_21_120001_create_usr_student_details_table.php](D:/laragon/www/turnstile-attendance-monitoring/database/migrations/2026_05_21_120001_create_usr_student_details_table.php:1)
- [database/migrations/2026_05_21_120002_create_usr_employee_details_table.php](D:/laragon/www/turnstile-attendance-monitoring/database/migrations/2026_05_21_120002_create_usr_employee_details_table.php:1)
- [database/migrations/2026_05_21_120003_rename_users_table_to_usr_users.php](D:/laragon/www/turnstile-attendance-monitoring/database/migrations/2026_05_21_120003_rename_users_table_to_usr_users.php:1)
- [database/migrations/2026_05_21_120004_move_student_fields_from_usr_users_to_usr_student_details.php](D:/laragon/www/turnstile-attendance-monitoring/database/migrations/2026_05_21_120004_move_student_fields_from_usr_users_to_usr_student_details.php:1)

Scan and SMS behavior:

- [app/Services/AttendanceService.php](D:/laragon/www/turnstile-attendance-monitoring/app/Services/AttendanceService.php:1)
- [app/Jobs/SendAttendanceSmsJob.php](D:/laragon/www/turnstile-attendance-monitoring/app/Jobs/SendAttendanceSmsJob.php:1)
- [app/Console/Commands/DispatchPendingAttendanceSmsCommand.php](D:/laragon/www/turnstile-attendance-monitoring/app/Console/Commands/DispatchPendingAttendanceSmsCommand.php:1)

Seeders and validation:

- [database/seeders/DatabaseSeeder.php](D:/laragon/www/turnstile-attendance-monitoring/database/seeders/DatabaseSeeder.php:1)
- [database/seeders/StudentSeeder.php](D:/laragon/www/turnstile-attendance-monitoring/database/seeders/StudentSeeder.php:1)
- [app/Concerns/ProfileValidationRules.php](D:/laragon/www/turnstile-attendance-monitoring/app/Concerns/ProfileValidationRules.php:1)

---

## Verification

The refactor was verified with targeted Pest tests covering:

- table creation
- registration
- profile update
- attendance scan
- SMS dispatch
- SMS job behavior

Command used:

```powershell
php artisan test --compact tests\Feature\DetailTablesMigrationTest.php tests\Feature\Auth\RegistrationTest.php tests\Feature\Settings\ProfileUpdateTest.php tests\Feature\Api\AttendanceScanTest.php tests\Feature\Api\AttendanceSmsDispatchTest.php tests\Unit\Jobs\SendAttendanceSmsJobTest.php
```

Formatting:

```powershell
vendor\bin\pint --dirty --format agent
```

---

## Operational Notes

- Run migrations on the target database to apply the rename and data move.
- If the UI does not reflect related frontend changes, run `npm run build` or `npm run dev`.
- The local environment still shows a non-blocking Pest temp-file permission warning under `vendor\pestphp\pest\.temp`, but the targeted tests passed.
