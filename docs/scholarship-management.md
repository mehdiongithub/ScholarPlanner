# Scholarship Database & Management System Documentation

This document explains the database structure, lifecycle transitions, validation rules, public routing, and authorization controls implemented for scholarships.

---

## 1. Database Schema & Normalization

To ensure database integrity and avoid N+1 queries, we created a migration [`036_add_scholarship_pivot_tables.php`](file:///c:/laragon/www/scholarship/database/migrations/036_add_scholarship_pivot_tables.php) that normalizes study criteria:

### Tables Map
1. **`scholarships`**: Stores core text, URLs, status, feature/quality flags, host country, and timestamps.
2. **`scholarship_countries`**: Pivot table mapping multiple target countries where study is supported.
3. **`scholarship_fields`**: Pivot table mapping multiple disciplines or fields of study.
4. **`scholarship_eligible_nationalities`**: Pivot table mapping nationalities eligible to apply (empty = Open to all).
5. **`scholarship_degree_levels`**: Pivot table mapping targeted degree levels (e.g. Master's, PhD).
6. **`scholarship_languages`**: Stores dynamic rows for language test criteria (e.g. IELTS minimum scores).
7. **`scholarship_eligibility_rules`**: Stores academic requirements (min GPA, scales, percentages, ages).
8. **`scholarship_benefits`**: Stores coverage parameters (tuition waivers, stipends, insurance).
9. **`scholarship_deadlines`**: Stores application deadlines and rounds.

---

## 2. Scholarship Lifecycle States
Scholarships transition through the following states:

- **`draft`**: The initial save state. Visible only to Admin and Employees with view permissions.
- **`pending_review`**: Submitted and awaiting verification.
- **`published`**: Verified and visible to the public. Sets `published_at = NOW()` and `verification_status = 'verified'`.
- **`expired`**: Dynamic status check based on date compared to current date.
- **`archived`**: Hidden from public listing but kept in the database for history tracking.

---

## 3. Route Mappings

### Public Routes
- `GET /scholarships` -> Lists all `published` scholarships. Supports search keyword queries, paginated card loops, sorting, and country/degree filters.
- `GET /scholarships/{slug}` -> Renders details page. Prevents access to drafts by public guests.

### Administration CRUD Routes
- `GET /admin/scholarships` -> Paginated dashboard list.
- `GET /admin/scholarships/create` -> Input form for new entries.
- `POST /admin/scholarships` -> Saves new records.
- `GET /admin/scholarships/{id}/edit` -> Loads existing values into edit form.
- `POST /admin/scholarships/{id}/update` -> Saves edits.
- `POST /admin/scholarships/{id}/delete` -> Deletes a record.
- `POST /admin/scholarships/{id}/publish` -> Publishes draft.
- `POST /admin/scholarships/{id}/archive` -> Archives entry.

---

## 4. Key Business Logic

### Unique SEO Slugs
- Generates clean hyphenated strings from title characters.
- Executes loop check queries against the database. If a slug is already taken, appends an incrementing counter (e.g. `erasmus-masters-1`) to avoid duplicate key exceptions.

### Obvious Duplicate Prevention
- Checks combinations of `provider_name`, `title`, and `official_application_url` before database insertions.
- Returns a validation block if a matching entry is found.

### Dynamic Deadlines Check
- Compares `application_deadline` date to the current server date:
  - If today is past the deadline, displays **`Deadline Passed`**.
  - If today is within 7 days of the deadline, displays **`Closing Soon`** (yellow alert).
  - Else, displays **`Open`** (green alert).

---

## 5. Security & Safety Controls

### XSS Sanitization
- Rich text fields are passed through a strict strip tags whitelist helper: `strip_tags($description, '<p><br><strong><em><ul><ol><li>')`.
- All attribute keywords containing script event triggers (`onload`, `onerror`, `onclick`, `javascript:`) are removed using regular expressions.
- General metadata fields use the SAPI `e()` HTML escape function.

### URL Safety
- Only allows URLs starting with `http://` or `https://` schemes.

### Permissions Gating
- Gated by authorization guards checking permissions on every action:
  - `scholarships.view`
  - `scholarships.create`
  - `scholarships.edit`
  - `scholarships.delete`
  - `scholarships.publish`
  - `scholarships.archive`
- Employee roles can only perform actions assigned to their accounts. Visitors attempting to access admin CRUD routes receive a `403 Forbidden` block.
