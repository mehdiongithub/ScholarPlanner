# Database Architecture & CLI Runners

This directory houses the complete InnoDB schema migration definitions and seeder files for the ScholarMatch platform.

---

## 1. Migration System

We use a custom, transaction-aware migration system to version control our database schema.

### Execution Command
Run all pending migrations in alphabetical order (tracked in the `migrations` table):
```bash
php database/migration_runner.php
```

### Rollback Command
Roll back the most recent batch of migrations:
```bash
php database/migration_runner.php --rollback
```

---

## 2. Seeding System

Seeders populate the database with default metadata, localized values, categories, and initial security accounts.

### Execution Command
Execute all seeders alphabetically:
```bash
php database/seeder_runner.php
```

---

## 3. Local Reset Guidelines

To completely wipe and rebuild the database locally (e.g. during development), follow these steps:

1. Connect to MySQL and drop the database:
```sql
DROP DATABASE IF EXISTS scholarship;
```
2. Simply access the application homepage `http://scholarship.test/` or run the test suite. The platform's self-healing database connector will automatically re-create the database `scholarship` with `utf8mb4_unicode_ci` collations.
3. Run migrations and seeders:
```bash
php database/migration_runner.php
php database/seeder_runner.php
```
