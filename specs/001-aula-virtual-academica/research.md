# Research: Aula Virtual Académica

**Feature**: 001-aula-virtual-academica | **Date**: 2026-10-04

This document resolves the open technical decisions from the plan's Technical Context and records the rationale for each choice. The source SDD already prescribes most architectural decisions (ADR-001…ADR-011); this research confirms and refines them with current best practices.

## R1 — Application framework: custom modular MVC (no full-stack framework)

- **Decision**: Implement a lightweight, custom MVC in `app/Core` (Router, Request, Response, Session, Database, Auth, Exceptions) with a `Modules/` directory per domain. No Laravel/Symfony/CakePHP.
- **Rationale**: ADR-001 explicitly mandates "PHP 8.x mediante arquitectura MVC modular", and §47–49 define the `Core`/`Modules`/`Shared` layout plus the Controller/Service/Repository separation. A full framework would contradict the prescribed structure and add unrequested coupling.
- **Alternatives considered**: Laravel (rejected — contradicts ADR-001 and the SDD's explicit custom structure); Symfony components (rejected — unnecessary for the prescribed scope).

## R2 — Dependency management and autoloading

- **Decision**: Composer with PSR-4 autoloading mapping the `App\` namespace to `app/`. Use minimal dependencies (e.g., `vlucas/phpdotenv` for environment config); everything else is first-party.
- **Rationale**: SDD §1 lists Composer as the dependency manager; PSR-4 is the PHP standard for a modular codebase.
- **Alternatives considered**: Manual `require`/`spl_autoload_register` (rejected — no dependency pinning, no ecosystem tooling).

## R3 — Database access: PDO with prepared statements

- **Decision**: PDO (PostgreSQL driver) exclusively; all queries use prepared statements with bound parameters; transactions for multi-step writes; explicit unique constraints and foreign keys enforced at the database level.
- **Rationale**: SDD §52 ("Todas las consultas … mediante PDO utilizando parámetros") and §53 (PKs, FKs, UNIQUE, NOT NULL, indexes, transactions) are hard requirements; PDO prepared statements are the standard defense against SQL injection.
- **Alternatives considered**: An ORM (Eloquent/Doctrine) — rejected; ADR-005 mandates PDO and the Repository pattern (§49).

## R4 — Authentication and session security

- **Decision**: Email + password login; passwords hashed with `password_hash()` (Argon2id when available, bcrypt fallback); PHP sessions hardened (httponly, samesite, secure flag in production); per-session CSRF token on all state-changing forms; role-based authorization checks in controllers/services.
- **Rationale**: SDD §12 (password_hash field), §51 (authentication, secure sessions, CSRF), and roles ADMIN/TEACHER/STUDENT (§13). Argon2id is the current PHP-recommended default.
- **Alternatives considered**: JWT/stateless API auth (rejected — server-rendered app with session-based auth per SDD §51); plain MD5/SHA (rejected — insecure).

## R5 — Frontend: Bootstrap 5 + vanilla JavaScript (no build step, no React)

- **Decision**: Bootstrap 5 via CDN or vendored static assets; hand-written ES6+ JavaScript in `public/assets/js` (modular per feature); no bundler, no React/Vue.
- **Rationale**: ADR-002 (Bootstrap 5) and ADR-003 ("JavaScript … sin depender de React"). A no-build approach keeps the project simple and deployable on XAMPP/Apache.
- **Alternatives considered**: React/Vue SPA (rejected — ADR-003); a bundler like Vite/Webpack (rejected — unnecessary complexity for server-rendered pages).

## R6 — Interactive activities storage and scoring

- **Decision**: Interactive activities are content blocks (`type = ACTIVITY`) whose question definitions (prompt, options, correct answer, points) live in the block's `settings` JSON. The client submits answers to a JSON endpoint; the **server** re-validates correctness against stored definitions and is the sole authority for scores. Activities are marked `FORMATIVE` or `GRADED`.
- **Rationale**: SDD §23 (server is the authority, never trust JS), §26 (formative vs graded), §22 (MVP types: MULTIPLE_CHOICE, TRUE_FALSE, SINGLE_ANSWER). Reusing the content-block model avoids an over-engineered parallel table for the MVP's three simple question types; future types (matching, drag/drop) can migrate to a dedicated structure when needed.
- **Alternatives considered**: A full `activities` + `activity_questions` table set mirroring assessments (rejected for MVP — unnecessary duplication for three simple types); trusting client-side scoring (rejected — violates §23).

## R7 — Evaluations: automatic grading model

- **Decision**: Assessments (evaluations) are first-class entities with questions and options; each attempt stores per-question answers with correctness; automatic grading compares stored answers to the question's correct options server-side; attempts honor `max_attempts`, `time_limit`, and the `available_from/until` window; statuses IN_PROGRESS → COMPLETED/EXPIRED/CANCELLED.
- **Rationale**: SDD §31–35 define the exact fields and statuses; §23 requires server-side grading authority.
- **Alternatives considered**: Grading in JavaScript then posting the score (rejected — violates §23).

## R8 — Videos and file storage

- **Decision**: Videos are embedded by YouTube iframe using a stored `youtube_video_id` (never uploaded); progress events (`video_started/progress/completed`) are recorded when possible. Other files (images, PDFs) are stored under `storage/uploads/` (or object storage) with metadata (original name, stored path, MIME, size, uploader) in a `files` table; public access is routed through a controller that enforces authorization.
- **Rationale**: SDD §24 (YouTube embed, store id, no server-side videos), §25 (files outside PostgreSQL, metadata in DB), §51 (file protection).
- **Alternatives considered**: Storing files as bytea blobs in PostgreSQL (rejected — §25 mandates external storage); self-hosted video hosting (rejected — §24).

## R9 — Grade calculation and configuration

- **Decision**: Grades are recorded per student per saber assignment. A configurable grade scheme per saber assignment defines weighted components (e.g., Activities 20%, Evaluations 60%, Project 20%) that can differ per saber; component values are aggregated into score/percentage and a final grade value. Passing score defaults to 60% per assessment unless configured otherwise.
- **Rationale**: SDD §36–37 (grades tied to student + saber assignment; configurable formulas with examples). A `grade_components` structure makes the formula configurable without hard-coding.
- **Alternatives considered**: Hard-coded weighting (rejected — §37 requires configurability); no default passing threshold (rejected — needs a sensible default for auto-grading).

## R10 — Progress and unlocking

- **Decision**: Progress is tracked at three levels (saber, unit, block) in dedicated progress tables; unit progression is computed from completed blocks/activities. Each saber has an unlock mode: `FREE` (all units available) or `SEQUENTIAL` (a unit unlocks after the previous is completed).
- **Rationale**: SDD §38–40 define the three progress levels and the FREE/SEQUENTIAL modes.
- **Alternatives considered**: Computing progress on the fly from raw events only (rejected — §38 mandates stored progress records).

## R11 — Testing strategy

- **Decision**: PHPUnit as the test runner, organized as Unit (services/repositories with mocked PDO), Integration (repositories against a test PostgreSQL database), and Contract (HTTP JSON API endpoints asserting request/response shapes). No full end-to-end browser suite in the MVP.
- **Rationale**: PHPUnit is the de facto PHP standard; the layering (§49) makes services/repositories the natural test seams.
- **Alternatives considered**: Pest (rejected — less ubiquitous in institutional PHP codebases); Behat/BDD (rejected — unnecessary ceremony for MVP).

## R12 — Routing and API surface

- **Decision**: A single front controller (`public/index.php`) dispatches page routes to controllers (server-rendered views) and `/api/*` routes to JSON endpoints for interactive features (activity submit, assessment attempt, progress, grades). Route definitions live per-module in `Routes/` and are registered with the `Core\Router`.
- **Rationale**: SDD §46 (route map), §50 (async JSON endpoints), §47 (Core Router). This gives clean, predictable URLs and a small JSON API surface for JavaScript.
- **Alternatives considered**: Full REST API for everything (rejected — server-rendered pages are the primary UI); no JSON endpoints (rejected — §50 requires async activity/assessment/progress operations).
