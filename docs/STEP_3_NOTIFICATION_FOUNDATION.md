# Step 3 Technical Report: Scholarship Notification Data Model, Paid User Gating & Idempotency

This report documents the implementation of the database and business logic foundation required for safe automated scholarship WhatsApp notifications.

---

## 1. Existing Tables Reused

-   **`notification_logs`**: Reused for storing notification events. Already contains critical fields like `user_id`, `scholarship_id`, `notification_type`, `channel`, `recipient`, `payload`, `attempts` (as `attempt_count`), `available_at` (as `scheduled_at`), `sent_at`, `failed_at`, `error_message` (as `last_error`), `idempotency_key`, `status`, `provider_message_id`, `created_at`, and `updated_at`.
-   **`notification_preferences`**: Reused for checking user notification preferences.
-   **`users`**: Reused for authentication status, global opt-in flags (`whatsapp_opt_in`), and phone numbers (`whatsapp_phone`, `phone`).
-   **`subscriptions`** / **`subscription_plans`**: Reused for evaluating active premium memberships.
-   **`scholarships`**: Reused for details, status (`published`), and expiration deadlines.

---

## 2. New Tables Added

None. Following the project rule of reusing the existing database architecture, we successfully avoided adding duplicate tables.

---

## 3. New Columns Added

-   **`provider`** (`VARCHAR(50) DEFAULT NULL`) added via migration `050_add_provider_to_notification_logs.php` to the `notification_logs` table. This allows recording which provider processed the message (e.g. `'wacrm'`).

---

## 4. New Indexes & Unique Constraints

-   The `idempotency_key` column in `notification_logs` already carries a database-level `UNIQUE KEY uq_notif_idempotency (idempotency_key)`. This index was successfully leveraged to prevent double insertions under concurrent worker loops.

---

## 5. Notification Types

Defined inside the centralized registry class [`App\Services\NotificationTypes`](file:///c:/laragon/www/scholarship/app/Services/NotificationTypes.php):

-   **`NEW_MATCH`**: Triggered when a new matching scholarship opportunity is published.
-   **`DEADLINE_REMINDER`**: Triggered before a matching scholarship's deadline closes.
-   **Backwards compatibility constants**: Reused existing types `SCHOLARSHIP_DEADLINE_SOON`, `SCHOLARSHIP_DEADLINE_TODAY`, `DAILY_MATCH_DIGEST`, and `WEEKLY_MATCH_DIGEST`.

---

## 6. Notification Status Lifecycle

The system utilizes the database-level states:
-   **`pending`**: Event enqueued, waiting to be sent.
-   **`sent`**: Successfully sent to provider.
-   **`failed`**: Encountered a permanent error during dispatch.

---

## 7. Paid-User Entitlement Logic

Evaluated server-side via `SubscriptionService::can($userId, 'whatsapp_alerts')`. 
We updated `SubscriptionService::getActivePlan()` to add `starts_at <= NOW()` checks, ensuring:
-   Active premium users (`ends_at >= NOW()`) are eligible.
-   Grace periods (cancelled but ends_at in future) are eligible.
-   Expired subscriptions are blocked.
-   Future-dated subscriptions are blocked until they start.
-   Failed payment subscriptions are blocked.

---

## 8. Scholarship Validity & Matching Logic

-   **Validity**: The scholarship must be `status = 'published'` and `application_deadline >= today` (or null).
-   **Matching**: The notification service invokes `ScholarshipMatchingService::matchUserAndScholarship()` and verifies the student profile matches (eligibility status must be `'ELIGIBLE'`).
-   **Registration Timing**: Gating logic does NOT compare scholarship creation dates to user registration dates, allowing users to be notified of older matching scholarships that are still open.

---

## 9. WhatsApp Opt-In Logic

Consent must be explicitly declared on two levels:
1.  **Global Switch**: `users.whatsapp_opt_in = 1`.
2.  **Notification Preferences**: `whatsapp_alerts` must be active (`whatsapp_enabled = 1`) AND specific alerts mapping (e.g. `matching_scholarship_alerts` for `NEW_MATCH`) must be enabled.

---

## 10. Concurrency Protection & Idempotency Strategy

-   **Idempotency Keys**: Deterministic key generation is implemented in `NotificationQueueService::enqueue()` based on the format:
    -   For `NEW_MATCH` / `DEADLINE_REMINDER`: `"{userId}_{scholarshipId}_{notificationType}_{channel}"` (excludes the execution date, preventing duplicates on subsequent days).
    -   For digest types: `"{userId}_{scholarshipId}_{notificationType}_{channel}_{eventDate}"` (retains date suffix).
-   **Atomic Insertion**: The unique index constraint on `idempotency_key` ensures that concurrent workers attempting to insert duplicates simultaneously will trigger a MySQL duplicate key collision which is caught, returning `false` safely without inserting redundant records.

---

## 11. Files Created

-   **[`app/Services/NotificationTypes.php`](file:///c:/laragon/www/scholarship/app/Services/NotificationTypes.php)**: Centralized notification types registry class.
-   **[`database/migrations/050_add_provider_to_notification_logs.php`](file:///c:/laragon/www/scholarship/database/migrations/050_add_provider_to_notification_logs.php)**: Migration adding the `provider` column to `notification_logs`.
-   **[`tests/NotificationFoundationTest.php`](file:///c:/laragon/www/scholarship/tests/NotificationFoundationTest.php)**: Automated test suite covering the 17 specified scenarios.

---

## 12. Files Modified

-   **[`app/Services/SubscriptionService.php`](file:///c:/laragon/www/scholarship/app/Services/SubscriptionService.php)**: Added `starts_at <= NOW()` check.
-   **[`app/Services/NotificationQueueService.php`](file:///c:/laragon/www/scholarship/app/Services/NotificationQueueService.php)**: Implemented date-free idempotency key generation and added `provider` column database mappings.
-   **[`app/Services/NotificationService.php`](file:///c:/laragon/www/scholarship/app/Services/NotificationService.php)**: Added `hasWhatsAppOptIn()` and `createNewMatchNotification()` methods.
-   **[`tests/run.php`](file:///c:/laragon/www/scholarship/tests/run.php)**: Registered the new test suite inside the master runner.

---

## 13. Verification Results

### Database Migrations
Applied and tested successfully. Rollback capability (`--rollback` command flag) has been verified and functions correctly.

### Automated Test Suite Execution
All 22 test suites passed successfully with exit code 0:
```bash
--- Running NotificationFoundationTest ---
✔ TEST 1: Active paid user matching notification enqueued successfully.
✔ TEST 2: Free user blocked from matching notification successfully.
✔ TEST 3: Expired subscription blocked successfully.
✔ TEST 4: Failed subscription status blocked successfully.
✔ TEST 5: WhatsApp opt-ins and preference switches gating verified.
✔ TEST 6: Phone formatting and empty validation checks verified.
✔ TEST 7: Draft/unpublished scholarship gating verified.
✔ TEST 8: Expired scholarship gating verified.
✔ TEST 9: Match filters gating verified.
✔ TEST 10: Exactly ONE notification log row exists on valid match.
✔ TEST 11: Deduplication checks blocked double insert attempt.
✔ TEST 12: Database unique index protects against concurrent insertion race conditions.
✔ TEST 13: Scholarship created before user registration evaluates successfully.
✔ TEST 14: Multiple separate matching notifications allowed for same user.
✔ TEST 15: Same scholarship enqueues notifications separately for different matching users.
✔ TEST 16: Duplicate NEW_MATCH WhatsApp notifications are successfully blocked by uniqueness key.
✔ TEST 17: Co-existence of NEW_MATCH and DEADLINE_REMINDER notifications on the same scholarship verified.
NotificationFoundationTest PASSED.

========================================
    ALL TEST SUITES PASSED OVERALL       
========================================
```

### PHP Syntax Checks
Passed lint check recursively on `app/`, `config/`, `routes/`, `cron/`, and `tests/` directories with zero syntax errors.

---

### FINAL DECISION

**STEP 3 VERIFIED — NO BLOCKING ISSUES**
