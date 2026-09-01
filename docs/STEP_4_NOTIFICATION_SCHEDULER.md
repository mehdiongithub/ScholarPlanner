# Step 4: Admin Notification Scheduling & Cron/Worker Foundation

## 1. Admin Settings
Administrators can configure the automatic scholarship notification engine via the Admin Settings UI (**GET/POST `/admin/settings`**). The settings are persisted in the `settings` table under `group_name = 'notifications'`:

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `whatsapp_notifications_enabled` | Boolean (`1`/`0`) | `1` | Master toggle to enable or disable automated alerts |
| `whatsapp_allowed_days` | CSV String | `Monday,Tuesday,Wednesday,Thursday,Friday` | Days of the week on which automated dispatches are permitted |
| `whatsapp_send_time` | String (`HH:MM`) | `10:00` | Daily 24-hour dispatch schedule |
| `whatsapp_timezone` | String | `Asia/Karachi` | Timezone used for all schedule window and day calculations |
| `whatsapp_batch_size` | Integer | `50` | Maximum alert candidates processed per iteration (range: 1 - 500) |
| `whatsapp_new_match_enabled` | Boolean (`1`/`0`) | `1` | Enable/disable `NEW_MATCH` WhatsApp alerts |
| `whatsapp_deadline_reminder_enabled` | Boolean (`1`/`0`) | `1` | Enable/disable `DEADLINE_REMINDER` alerts |

---

## 2. Database Changes
- **Migration**: `database/migrations/051_add_notification_scheduler_settings.php`
  - Added column `processing_started_at TIMESTAMP NULL DEFAULT NULL AFTER attempts` to `notification_logs`.
  - Seeded default notification scheduler configuration rows into `settings` table.
  - Implemented rollback `down` handler to drop `processing_started_at` column and remove seeded notification settings.

---

## 3. Scheduler Architecture
The scheduler architecture is encapsulated inside `App\Services\NotificationSchedulerService`:
1. **Schedule Evaluation**: Reads settings and checks master toggle, weekday match, and time window against the configured timezone.
2. **Advisory Lock Management**: Uses MySQL `GET_LOCK` and `RELEASE_LOCK` to prevent overlapping cron runs.
3. **Stale Processing Recovery**: Identifies crashed worker records in `processing` state and safely resets them to `pending` (or marks `failed` if retry limit is exceeded).
4. **Atomic Batch Claiming**: Transitions pending rows matching permitted alert types to `processing` state with an atomic update and unique batch token.
5. **Candidate Verification**: Validates subscriber eligibility, opt-in consent, valid phone numbers, and scholarship status before dispatch.

---

## 4. Cron Entry Point
The unified CLI entry point is `cron/process_notifications.php`.
- **Standard Execution**:
  ```bash
  php cron/process_notifications.php
  ```
- **Dry-Run Inspection** (no records updated, no WhatsApp messages sent):
  ```bash
  php cron/process_notifications.php --dry-run
  ```
- **Forced Execution** (bypasses day/time window check for manual testing):
  ```bash
  php cron/process_notifications.php --force
  ```

---

## 5. Timezone Handling
- Timezone is configurable per admin settings (`whatsapp_timezone`, e.g. `Asia/Karachi`, `America/New_York`, `UTC`).
- `NotificationSchedulerService::isScheduleDue()` calculates current local time and current weekday using `DateTimeZone($configuredTimezone)`.
- Server UTC time is never compared directly against local schedule without timezone conversion.

---

## 6. Allowed-Day Handling
- Administrators can select any combination of weekdays (`Monday` through `Sunday`).
- Schedule evaluator extracts `$now->format('l')` and verifies membership in `whatsapp_allowed_days`.
- Disallowed days safely log `day_not_allowed` and skip execution.

---

## 7. Time-Window Behavior
- Evaluated against `whatsapp_send_time` in 24-hour format (`HH:MM`).
- If current time is earlier than `whatsapp_send_time`, the scheduler skips with `before_send_time`.
- Once `whatsapp_send_time` is reached on an allowed day, eligible pending alerts are claimed and processed.
- Status transitions (`pending` &rarr; `processing` &rarr; `sent`) and Step 3 idempotency keys prevent multiple runs within the window from duplicate dispatching.

---

## 8. Lock Mechanism
- Uses MySQL session advisory lock: `SELECT GET_LOCK('scholarship_notification_scheduler', 0)`.
- Returns immediately (`0` timeout); if another worker process is active, the second process exits with code 0 without blocking.
- Lock is automatically released on process completion or in the `finally` block via `SELECT RELEASE_LOCK(...)`.

---

## 9. Batch Processing
- Enforces `whatsapp_batch_size` (validated between 1 and 500).
- Limits the number of claimed rows per iteration (`LIMIT :batchSize`), preventing memory exhaustion.

---

## 10. Notification Claiming
- Atomic SQL state transition:
  ```sql
  UPDATE notification_logs
  SET status = 'processing',
      processing_started_at = NOW(),
      provider_message_id = :claimToken,
      updated_at = NOW()
  WHERE status = 'pending'
    AND channel = 'whatsapp'
    AND (available_at IS NULL OR available_at <= NOW())
    AND notification_type IN (...)
  ORDER BY id ASC
  LIMIT :batchSize
  ```
- Claimed rows are then fetched using `provider_message_id = :claimToken`, ensuring concurrency isolation.

---

## 11. Stale Processing Recovery
- Detects rows where `status = 'processing'` and `processing_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)`.
- Rows with `attempts < 3` are reset to `status = 'pending'`, incrementing `attempts = attempts + 1`.
- Rows with `attempts >= 3` are marked `status = 'failed'` with an error message.

---

## 12. Retry Foundation
- Metadata tracked in `notification_logs`: `attempts`, `processing_started_at`, `failed_at`, `error_message`.
- Hard-capped at 3 maximum attempts to prevent infinite retry loops.

---

## 13. Dry-Run Mode
- Invoked via `php cron/process_notifications.php --dry-run`.
- Evaluates scheduling rules and inspects pending candidates without modifying records or contacting external APIs.

---

## 14. Security & Access Control
- Admin Settings endpoints are guarded by `Auth::requirePermission('settings.view')` and `Auth::requirePermission('settings.edit')`.
- All form submissions require valid CSRF tokens (`Security::verifyCsrfToken`).
- Server-side validation rejects invalid days, malformed times, unrecognized timezones, and out-of-range batch sizes.
- cURL operations and error logs strictly redact Bearer tokens and API keys.

---

## 15. Automated Test Suite
Test suite in `tests/NotificationSchedulerTest.php` covers all 22 required test scenarios:
1. `test1_AdminCanSaveNotificationSettings`
2. `test2_UnauthorizedUserCannotChangeSettings`
3. `test3_CsrfProtectionWorks`
4. `test4_InvalidWeekdayRejected`
5. `test5_InvalidTimeRejected`
6. `test6_InvalidTimezoneRejected`
7. `test7_InvalidBatchSizeRejected`
8. `test8_SchedulerDisabledNoProcessing`
9. `test9_SchedulerEnabledOnAllowedDayEligible`
10. `test10_SchedulerEnabledOnDisallowedDayNoProcessing`
11. `test11_SchedulerOutsideConfiguredTimeNoProcessing`
12. `test12_SchedulerInsideConfiguredTimeProcessingAllowed`
13. `test13_DryRunDoesNotSendMessages`
14. `test14_DryRunDoesNotMarkNotificationsAsSent`
15. `test15_BatchSizeIsRespected`
16. `test16_DuplicateCronExecutionDoesNotDuplicateNotificationEvents`
17. `test17_ConcurrentSchedulerProcessesCannotClaimSameNotification`
18. `test18_StaleProcessingRecordsRecoveredSafely`
19. `test19_NewMatchIdempotencyFromStep3RemainsIntact`
20. `test20_ExistingScholarshipMatchingIntegration`
21. `test21_ExistingSubscriptionGatingIntegration`
22. `test22_ExistingWacrmProviderIntegration`

---

## 16. Test Verification Results
- Master Test Runner: `php tests/run.php`
- **Total Test Suites**: 23
- **Passed**: 23
- **Failed**: 0
- **Exit Code**: 0

---

## 17. PHP Syntax Check Results
- Recursive scan across `app/`, `config/`, `routes/`, `cron/`, `tests/`, `database/`.
- **Syntax Errors**: 0

---

## 18. Production Cron Configuration Instructions
To run the automated scheduler in production, add the following cron entry (runs once every minute to check schedule and process batches):

```crontab
* * * * * cd /path/to/scholarship && php cron/process_notifications.php >> storage/logs/cron_notifications.log 2>&1
```
*(Replace `/path/to/scholarship` with the absolute root path to the project directory).*
