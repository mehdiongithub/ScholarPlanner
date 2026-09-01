# Step 5: WACRM WhatsApp Dispatch Worker

## 1. Dispatch Architecture
The dispatch layer connects the claimed notification queue to the WACRM provider using a unidirectional, decoupled pipeline:

```
cron/process_notifications.php
  ↓
NotificationSchedulerService::claimPendingBatch()
  ↓
Claimed rows in 'processing' state with unique claimToken
  ↓
NotificationDispatchService::dispatchBatch()
  ↓
Pre-flight Eligibility Re-check (Paid, Opt-in, Published, Deadline)
  ↓
WacrmWhatsAppProvider::sendTemplateMessage()
  ↓
WACRM API (Bearer Auth)
  ↓
Success ('sent') OR Retry/Failure Handling
```

The dispatch layer contains **no** scholarship matching algorithms or subscription billing checks beyond verifying existing permissions and flags via standard service interfaces (`SubscriptionService::can()`, `NotificationService::hasWhatsAppOptIn()`).

---

## 2. Notification Lifecycle
The notification lifecycle strictly follows the state machine:
- `pending` &rarr; `processing` &rarr; `sent` (on confirmed provider delivery)
- `pending` &rarr; `processing` &rarr; `retrying` &rarr; `pending` (on transient error, with exponential/provider backoff, up to 3 attempts)
- `pending` &rarr; `processing` &rarr; `failed` (on permanent error or exceeding 3 attempts)
- `pending` &rarr; `processing` &rarr; `cancelled` (on pre-flight eligibility failure, e.g. expired subscription, opt-out)

---

## 3. Eligibility Pre-flight Re-check
Before invoking the WACRM API, `NotificationDispatchService::recheckEligibility()` performs a fresh check against current database state:
1. **User Existence & Status**: User must exist and have `status = 'active'`.
2. **Paid Subscription Entitlement**: `SubscriptionService::can($userId, 'whatsapp_alerts')` must return `true`.
3. **WhatsApp Opt-in**: `NotificationService::hasWhatsAppOptIn($userId, $type)` must return `true`.
4. **Phone Formatting**: Recipient phone number must normalize to standard E.164 format.
5. **Scholarship Validity**: Scholarship must remain in `status = 'published'` and `application_deadline` must not have passed.
6. **Admin Settings**: System toggle `whatsapp_notifications_enabled` and type-specific toggles must remain enabled.

If any criterion fails, the notification is transitioned to `cancelled` with an explicit reason logged in `error_message`, and no WhatsApp API request is made.

---

## 4. WACRM Request Flow
- Outgoing API calls are routed exclusively through `WacrmWhatsAppProvider`.
- Base URL and Bearer API Key are loaded server-side from `config/whatsapp.php`.
- The request payload is formatted as:
  ```json
  {
    "to": "+923008888888",
    "type": "template",
    "template": {
      "name": "new_match",
      "language": "en_US",
      "params": ["Scholarship Title", "Provider Name", "2026-09-30", "https://..."]
    }
  }
  ```

---

## 5. Template Handling
- Template names are dynamically resolved from configuration (`config/whatsapp.php`):
  - `NEW_MATCH` &rarr; `wacrm.templates.new_match` (default: `new_match`)
  - `DEADLINE_REMINDER` / `SCHOLARSHIP_DEADLINE_SOON` &rarr; `wacrm.templates.deadline_soon` (default: `deadline_soon`)
  - `SCHOLARSHIP_DEADLINE_TODAY` &rarr; `wacrm.templates.deadline_today` (default: `deadline_today`)
- Parameters are extracted from trusted database entities and sanitized against XSS/injections.

---

## 6. Success Handling
- Upon receiving HTTP 200/2xx with a valid WACRM response confirming message acceptance:
  - `status = 'sent'`
  - `sent_at = NOW()`
  - `provider = 'wacrm'`
  - `provider_message_id = $res['message_id']`
  - `error_message = NULL`

---

## 7. Failure Handling
- **Permanent Errors** (HTTP 400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, Invalid Phone, Missing Config):
  - `status = 'failed'`
  - `failed_at = NOW()`
  - `error_message` records redacted error description.

---

## 8. Retry Handling
- **Transient Errors** (HTTP 429 Rate Limited, HTTP 500/502/503 Server Errors, cURL Timeout / Network Error):
  - `attempts` incremented by 1.
  - If `attempts < 3`:
    - `status = 'pending'`
    - `available_at` set to `NOW() + delay` (default: 300s, or `Retry-After` header).
    - `error_message` records transient error.
  - If `attempts >= 3`:
    - `status = 'failed'`
    - `failed_at = NOW()`
    - `error_message = 'Exceeded maximum retry attempts: ...'`

---

## 9. Rate-Limit Handling (HTTP 429)
- `WacrmWhatsAppProvider` captures the `Retry-After` HTTP header via cURL header callback.
- `NotificationDispatchService` sets `available_at` based on the specified retry interval, preventing tight polling loops.

---

## 10. Duplicate-Send Protection & Limitations
- **Claim Token Ownership**: Claimed batch items are marked with a unique random claim token (`provider_message_id = 'claim_...'`). Prior to dispatch, `dispatchItem` verifies current row ownership.
- **Database Idempotency**: Unique constraint `uq_notif_idempotency` (`user_id`, `scholarship_id`, `notification_type`, `channel`) prevents duplicate queue creation.
- **Limitation Note**: In the event of a worker crash *after* WACRM accepted the HTTP request but *before* MySQL commits `status = 'sent'`, recovery mechanisms identify the orphaned job. If WACRM does not provide provider-level deduplication keys, application-level state locking and small claim batches provide the highest possible safety boundary.

---

## 11. Provider Message IDs
- When WACRM returns `message_id`, it is stored in `notification_logs.provider_message_id`.
- This enables message status tracking and auditability without exposing internal keys to end-users.

---

## 12. Dry-Run Behavior
- Invoking `php cron/process_notifications.php --dry-run` performs read-only inspections.
- It never initiates cURL connections, never marks notifications as sent, and modifies zero database records.

---

## 13. Test Provider & Automated Mocking
- Automated tests implement `MockTestWhatsAppProvider` and `CurlMockRegistry` to simulate all HTTP response codes (200, 400, 401, 403, 404, 429, 500, 502, 503, timeout, malformed JSON).
- Real WhatsApp messages are strictly prevented during automated test execution.

---

## 14. Security & Access Control
- API keys and Bearer tokens are stored exclusively in server environment files (`.env`) and never rendered to HTML/JS.
- `NotificationDispatchService` and `WacrmWhatsAppProvider` use `redactSecrets()` to sanitize authorization headers, passwords, and tokens before logging.
- MySQL advisory locks prevent overlapping CLI processes from race conditions.

---

## 15. Production Requirements
1. Configure WACRM credentials in `.env`:
   ```env
   WHATSAPP_PROVIDER=wacrm
   WACRM_BASE_URL=https://api.wacrm.example.com
   WACRM_API_KEY=your_production_api_key
   WACRM_API_TIMEOUT=15
   ```
2. Schedule cron in crontab:
   ```crontab
   * * * * * cd /path/to/scholarship && php cron/process_notifications.php >> storage/logs/cron_notifications.log 2>&1
   ```

---

## 16. Live WACRM Testing Status
- **Status**: Code-level dispatch verified; live WACRM delivery not tested because production credentials are not configured in the test environment.

---

## 17. Automated Test Suite Results
- Master Runner: `php tests/run.php`
- **Total Test Suites**: 24
- **Passed**: 24
- **Failed**: 0
- **Exit Code**: 0

`WacrmDispatchWorkerTest` verified all 32 required test assertions:
1. Pending notification can be claimed.
2. Claimed notification becomes processing with `processing_started_at`.
3. Active paid user can be dispatched.
4. Free user cannot be dispatched (cancelled with reason).
5. Expired subscription cannot be dispatched.
6. WhatsApp opt-out prevents sending.
7. Invalid phone prevents sending.
8. Expired scholarship deadline prevents sending.
9. Unpublished scholarship prevents sending.
10. Nonexistent user handled safely without sending.
11. Nonexistent scholarship handled safely without sending.
12. Successful WACRM response transitions status to `sent`.
13. Provider message ID is properly stored in `notification_logs`.
14. `sent_at` timestamp is populated.
15. HTTP 400 bad request recorded as permanent failure.
16. HTTP 401 unauthorized recorded as permanent failure.
17. HTTP 403 forbidden recorded as permanent failure.
18. HTTP 429 rate limit captures `retry_after` and schedules retry.
19. HTTP 500 server error schedules retry.
20. HTTP 502 bad gateway schedules retry.
21. HTTP 503 service unavailable schedules retry.
22. cURL timeout schedules retry.
23. Malformed JSON response treated as failure and not marked `sent`.
24. Maximum retry limit (3 attempts) marks notification `failed`.
25. Dry-run mode never contacts WACRM API.
26. Dry-run mode never marks notifications as sent.
27. Concurrent workers cannot dispatch the same claimed notification simultaneously.
28. Existing Step 3 `NEW_MATCH` idempotency remains intact.
29. Existing Step 4 scheduler tests pass without regression.
30. Existing WACRM provider tests pass without regression.
31. Existing subscription tests pass without regression.
32. Existing scholarship matching tests pass without regression.

---

## 18. PHP Syntax Check Results
- Recursive scan across `app/`, `config/`, `routes/`, `cron/`, `tests/`, `database/`.
- **Syntax Errors**: 0
