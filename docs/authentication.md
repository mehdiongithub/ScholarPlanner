# Authentication & User Account System Documentation

This document explains the security mechanics, registration requirements, session parameters, and token rotation implemented in ScholarMatch.

---

## 1. Registration Flow & Validations
Users register at `/register` using a form mapped to default active visitor statuses.
- **Normalizations**: Target emails are transformed to lowercase via `strtolower(trim($email))`. First and last names are trimmed of surrounding whitespace.
- **Validations**:
  - Required presence for first name, last name, email, phone, password, and country of residence (countries are queried directly from the `countries` table).
  - Casing and numeric verification: passwords require mixed casing (uppercase, lowercase, number) and a minimum length of 8 characters.
  - Unique keys: Email and phone numbers are verified for duplicates against the database.
  - Input patterns: Mobile phone numbers require international format formatting matching `/^\+?[0-9]{10,15}$/`.

---

## 2. Password Hardening Policies
- **Storage**: Plaintext passwords are never logged, stored, or outputted in API responses.
- **Hashing**: Registration and updates encrypt passwords using `password_hash($plain, PASSWORD_BCRYPT)`.
- **Verifications**: Authentications are validated using `password_verify($plain, $hash)`.
- **Fixation shield**: Upon login, `session_regenerate_id(true)` is executed to replace old session IDs and mitigate hijacking vectors.

---

## 3. Brute Force Protection (Login Throttling)
- Logs failed login attempts in the database: `login_attempts` (`email`, `ip_address`, `attempted_at`).
- **Throttling threshold**: If a user's IP or target email receives 5 failed attempts within 15 minutes, the login is blocked.
- **Temporary block**: Returns a warning: *"Too many failed login attempts. Please try again in 15 minutes."* This does not permanently lock the account.

---

## 4. Persistency ("Remember Me" Token Rotation)
- Uses a dual-token selector/validator pattern to prevent token-leak replays:
  - **Selector** (16 chars): Plain text ID sent to identify the record in `remember_tokens`.
  - **Validator** (64 chars): Plain text validator stored in browser cookies as `selector:validator`.
  - **Database Hash**: We store only the SHA-256 hash of the validator token.
- **Auto-Rotation**: On successful re-authentication, the selector remains the same but a new validator is generated, hashed in the database, and updated in the user's cookies.
- **Theft Protection**: If an invalid validator is sent with a valid selector (indicating replay/theft attempts), the system instantly deletes all remember tokens for the user and logs a security warning.

---

## 5. Password Recovery (Forgot Password Flow)
- Request link at `/forgot-password`.
- Generates a secure random 64-character token hash stored in `password_reset_tokens` along with an expiry timestamp (expires in 1 hour).
- **Privacy Policy**: The form output always renders a generic message: *"If the email is registered in our system, you will receive a reset link shortly."* (Never discloses whether the email exists).
- **Local Bypass UI**: If `APP_ENV=local`, a development-only reset link is displayed on the screen for setup ease (suppressed in production).

---

## 6. Access Control Gating (Role/Permission Guards)
- **Authentication**: `Auth::requireAuth()` redirects guest requests to `/login`.
- **Role Guard**: `Auth::requireRole($roles)` redirects unauthorized role accesses (e.g. visitors accessing `/admin`) to a custom `403 Forbidden` page.
- **Permission Guard**: `Auth::requirePermission($permission)` checks dynamic database configurations (`role_permissions` -> `permissions`). Admin is granted a global bypass. Employees are verified strictly against mapped actions.
