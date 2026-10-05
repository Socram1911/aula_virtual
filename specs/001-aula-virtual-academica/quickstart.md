# Quickstart & Validation Guide: Aula Virtual Académica

**Feature**: 001-aula-virtual-academica | **Date**: 2026-10-04

This guide documents how to run and validate the feature end-to-end once implemented. It lists prerequisites, setup, run/test commands, and the validation scenarios that prove each user story works. It intentionally contains **no implementation code** — that belongs to `tasks.md` and the implementation phase.

## Prerequisites

- PHP 8.x with the `pdo_pgsql` extension enabled.
- PostgreSQL (target database) reachable and running.
- Composer (for PSR-4 autoloading and dependencies).
- A web server: Apache (XAMPP) or nginx + PHP, with the document root pointed at `public/`.
- Node.js is **not** required (no JS build step).

## One-time setup

1. Install PHP dependencies: `composer install`
2. Copy the environment template and fill in database credentials: `cp config/.env.example config/.env`
3. Create the database schema by running the migrations (see `database/` — command name finalized in `tasks.md`): e.g. `php database/migrate.php`
4. Seed initial roles (ADMIN, TEACHER, STUDENT) and a first admin user: `php database/seed.php`
5. Ensure `storage/uploads/` exists and is writable by the web server.

## Running

- Local: point XAMPP/Apache at `public/` and open `http://localhost/`.
- Run the test suite: `vendor/bin/phpunit`

## Validation scenarios

Run these to prove each user story from [spec.md](./spec.md). Each is independently verifiable.

### 1. Academic structure administration (Story 1)

1. Log in as an administrator.
2. Create an academic period "2026-2027" (draft → activate).
3. Create a trayecto "Trayecto 3" and associate it to the period.
4. Create section "Sección 20" under Trayecto 3.
5. Attempt to create a second "Sección 20" in the same period + trayecto.

**Expected**: period, trayecto, and section appear correctly; the duplicate section is rejected.

### 2. Saberes and assignments (Story 2)

1. Create a saber "Matemática" (code MAT-03) under Trayecto 3.
2. Add units "Unidad 1" and "Unidad 2".
3. Assign "Matemática" to Sección 20 for 2026-2027 and assign a primary teacher.
4. Assign the same saber to Sección 33 for 2026-2027.

**Expected**: both sections reference the same saber and its units (no duplication); duplicate assignment of the same saber+section+period is rejected.

### 3. Teacher builds a unit (Story 3)

1. Log in as the teacher assigned to "Matemática".
2. Create a unit and add blocks: text, image, PDF, video (YouTube), link.
3. Reorder the blocks; publish the unit.

**Expected**: a student in Sección 20 sees the unit with blocks in the configured order.

### 4. Student learning experience (Story 4)

1. Log in as a student enrolled in Sección 20.
2. Open the dashboard → "Mis Saberes" → Matemática → Unidad 1.
3. View text, image, PDF, video, and link blocks.

**Expected**: the student's dashboard shows Sección 20 and Matemática with progress; all content renders (video plays embedded).

### 5. Interactive activities (Story 5)

1. As the teacher, add a multiple-choice activity and a true/false activity to a unit.
2. As the student, answer both and submit via the UI.

**Expected**: immediate correct/incorrect feedback; formative activities affect progress only; graded results are recorded server-side.

### 6. Evaluations with automatic grading (Story 6)

1. As the teacher, create an evaluation with questions, correct answers, max score 10, passing score 6, max attempts 2.
2. As the student, start an attempt, answer, and submit.

**Expected**: score, percentage, and pass/fail are shown immediately; attempt count is enforced; exceeding the limit or the availability window blocks further attempts.

### 7. Progress, grades, and reports (Story 7)

1. After the student completes activities and an evaluation, check:
   - Student dashboard → progress per saber and unit; grades.
   - Teacher dashboard → drill to the section → students → progress → grades.
   - Admin reports → students per section, saberes per trayecto, academic results.
   - Admin audit → entries for the key actions performed above.

**Expected**: progress and grades are consistent across student, teacher, and admin views; audit records show who did what and when.

## Automated checks

- `vendor/bin/phpunit` — runs unit, integration, and contract tests. Contract tests assert the request/response shapes in [contracts/api.md](./contracts/api.md).
- Manual smoke: each scenario above maps to acceptance scenarios in [spec.md](./spec.md).
