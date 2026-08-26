# Applicant Profile & Scholarship Preferences Documentation

This document explains the personal demographics, education history tracking, preference normalization, and security models implemented for student profiles.

---

## 1. Profile Completeness Algorithm
A user's profile completeness is calculated out of 100% via the `ProfileCompletionService::calculate()` method, using the following weight breakdown:

| Section | Mapped Fields | Weight |
| :--- | :--- | :--- |
| **Personal Details (30%)** | `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender` | 5% per field |
| **Location Demographics (20%)** | `nationality_country_id`, `residence_country_id`, `residence_state_id`, `city_id` | 5% per field |
| **Academic History (25%)** | Has at least one educational record in `education_records` | 25% flat |
| **Study Preferences (25%)** | Preferred countries (8%), preferred fields (8%), preferred degree levels (9%) | 25% total |

---

## 2. Dynamic Age Calculation
The system avoids storing calculated ages in database tables. Instead:
- Birthdays are saved in the `student_profiles.date_of_birth` column.
- Dynamic ages are calculated on render by `AgeCalculator::calculateAge($dob)` relative to the current date.

---

## 3. Normalized Study Preferences (Many-to-Many Mappings)
To prevent storing comma-separated values inside single columns, study preferences are mapped via three normalized tables:

1. **`user_preferred_countries`** (`user_id`, `country_id`):
   - Mapped to `countries.id`. Unique index prevents duplicate entries.
2. **`user_preferred_fields`** (`user_id`, `field_of_study_id`):
   - Mapped to `fields_of_study.id`. Unique index prevents duplicate entries.
3. **`user_preferred_degree_levels`** (`user_id`, `degree_level`):
   - Stores target degree levels (e.g. Master's, PhD). Unique index prevents duplicates.

---

## 4. Education History Constraints
- Users can manage multiple education degrees.
- **Current Education Limit**: Users can mark one degree as current (`is_current = 1`). Saving a new current record automatically resets all other records for that user to `is_current = 0` inside a database transaction to prevent contradictory states.

---

## 5. Security & Isolation Model

### IDOR (Insecure Direct Object Reference) Protection
- Visitors can only access and update their own profiles.
- Target user IDs are always resolved using `Auth::userId()` from session data, not request parameters (e.g. `?user_id=X`).
- **Education Ownership Validation**: Updating or deleting an education record checks that the target `education_records.id` belongs to the logged-in `Auth::userId()` BEFORE any query is executed:
  ```php
  $stmt = $db->prepare("SELECT user_id FROM education_records WHERE id = :id");
  // Aborts with 403 on mismatch
  ```

### Mass Assignment Hardening
- Forms do not update database tables by dumping full `$_POST` bodies.
- Instead, the controller whitelists and extracts specific fields. Unrecognized fields are discarded.

### CSRF Protection
- State-changing actions (`profile/update`, `education/add`, `education/update`, `education/delete`, `preferences/update`) verify tokens using `Security::verifyCsrfToken()`.

### XSS Hardening
- Profile name, biography, institution name, and degree titles are outputted using the `e()` HTML escaper.
