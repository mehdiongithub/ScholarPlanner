# Step 6: Controlled Live WACRM End-to-End Test & Production Readiness

## 1. Required `.env` Configuration Variables
The following environment variables are required to operate the WACRM integration:

```env
WHATSAPP_PROVIDER=wacrm
WACRM_BASE_URL=https://api.wacrm.example.com
WACRM_API_KEY=your_production_bearer_api_key
WACRM_API_TIMEOUT=15
WACRM_TEMPLATE_NEW_MATCH=new_match
WACRM_TEMPLATE_DEADLINE_SOON=deadline_soon
WACRM_TEMPLATE_DEADLINE_TODAY=deadline_today
```

---

## 2. Safe Configuration Instructions
1. Never commit `.env` to Git. Ensure `.env` is listed in `.gitignore`.
2. Keep `.env.example` populated with empty placeholders only.
3. Keep API keys restricted to server-side environments; never expose them to frontend JavaScript or client-facing HTML.
4. All cURL exceptions, logs, and database error messages must be sanitized using `redactSecrets()`.

---

## 3. Controlled Test Setup
Controlled testing is performed against an isolated test user and fixture:
- **Test User**: Dedicated user record with active status and an administrator-controlled E.164 phone number.
- **Entitlement**: Active premium subscription attached in `subscriptions` table (`status = 'active'`).
- **Opt-in**: Explicit consent for WhatsApp alerts enabled in `users.whatsapp_opt_in` and `notification_preferences`.
- **Scholarship**: Published scholarship with a future deadline matching the test student profile.

---

## 4. Dry-Run Verification Command
To inspect the scheduler and queue without modifying database records or calling WACRM:

```bash
php cron/process_notifications.php --dry-run
```

**Expected Dry-Run Behavior**:
- Detects whether schedule window is due.
- Scans pending candidates matching allowed types.
- Modifies zero database rows.
- Initiates zero network connections.

---

## 5. Live/Controlled Dispatch Command
To execute the worker in production or controlled testing:

```bash
# Standard scheduled execution (respects window and batch size)
php cron/process_notifications.php

# Forced execution for controlled verification
php cron/process_notifications.php --force
```

---

## 6. Expected Notification Lifecycle
```
PENDING
  ↓ (claimPendingBatch with random claim token)
PROCESSING
  ↓ (Pre-flight eligibility validation)
  ↓ (WacrmWhatsAppProvider::sendTemplateMessage)
SENT / RETRYING / FAILED / CANCELLED
```
- **Sent**: HTTP 200/2xx confirmed &rarr; `status = 'sent'`, `sent_at = NOW()`, `provider_message_id = '...'`.
- **Retrying**: Transient error (HTTP 429, 500, 502, 503, timeout) with attempts < 3 &rarr; `status = 'pending'`, `available_at = NOW() + delay`.
- **Failed**: Permanent error (HTTP 400, 401, 403, 404, invalid phone) or attempts &ge; 3 &rarr; `status = 'failed'`.
- **Cancelled**: Pre-flight ineligibility (expired subscription, opt-out, unpublished scholarship) &rarr; `status = 'cancelled'`.

---

## 7. Duplicate Send Protection
1. **Database Idempotency**: Unique constraint `uq_notif_idempotency` (`user_id`, `scholarship_id`, `notification_type`, `channel`) guarantees that at most one notification row exists per user/scholarship match.
2. **Claim Token Ownership**: Claimed batch items are tagged with a unique random claim token (`provider_message_id = 'claim_...'`). Prior to calling WACRM, worker verifies row ownership.
3. **Queue Re-run Safety**: Subsequent executions find zero eligible pending records for already-sent alerts.

---

## 8. Retry & Rate-Limit Behavior
- Transient errors automatically schedule backoff retries up to 3 attempts.
- HTTP 429 captures the `Retry-After` header and schedules next attempt accordingly.
- Permanent client/auth errors fail immediately without wasteful retries.

---

## 9. Security Audit Results
- **Git Security**: `.env` is ignored by `.gitignore`. Zero credentials committed to Git history.
- **Log Security**: `storage/logs/` scanned; zero Bearer tokens or API keys exposed in logs.
- **Code Security**: Zero hardcoded credentials in source code.
- **Concurrency Locking**: MySQL advisory lock `GET_LOCK('scholarship_notification_scheduler', 0)` verified across distinct database sessions.

---

## 10. Production Cron Configuration
To automate the notification worker, configure the following crontab entry:

```crontab
* * * * * cd /path/to/scholarship && php cron/process_notifications.php >> storage/logs/cron_notifications.log 2>&1
```
*(Replace `/path/to/scholarship` with the absolute root path to your installation).*

---

## 11. Live Test Status
- **A. Code-Level Verification**: `PASS` (All service models, dispatch worker, matching, idempotency, claiming, dry-run, and retry policies verified).
- **B. WACRM API Authentication Verification**: `PENDING` (Live API credentials not configured in `.env`).
- **C. Actual WhatsApp Delivery Verification**: `PENDING` (Controlled live delivery requires production WACRM credentials and recipient phone number).

---

## 12. Integration Limitations
- **External Provider Idempotency**: WACRM API does not provide a custom client-side deduplication token parameter. Protection relies on application-side atomic claim locking, database uniqueness constraints, and small batch sizes.
- **Live Dispatch Requirement**: Real WhatsApp delivery depends on configured WACRM account status, template approval on Meta, and sufficient message quota.

---

## 13. Regression Test Results
- **Master Test Runner**: `php tests/run.php`
- **Total Test Suites**: 24
- **Passed**: 24
- **Failed**: 0
- **Exit Code**: 0
- **PHP Syntax Check**: 0 errors across all directories.
