# Implementation Plan: Aula Virtual Académica (Institutional Virtual Classroom)

**Branch**: `001-aula-virtual-academica` | **Date**: 2026-10-04 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-aula-virtual-academica/spec.md`

## Summary

Build an institutional academic virtual classroom ("Aula Virtual Académica") that organizes the teaching/learning process end-to-end: from creating an academic period, through trayectos (tracks) and numeric sections, to the reusable **Saber** curriculum model (Saber → Unit → Content → Activity/Evaluation → Grade → Progress). Three roles — Administrator, Teacher, and Student — interact through role-specific dashboards. The MVP covers administration (users, roles, periods, tracks, sections, students, teachers), academic management (saberes, units, assignments), content (text, image, PDF, YouTube, links), interactive activities (multiple choice, true/false, single answer), evaluations with automatic grading, progress and grades tracking, and an audit trail.

**Technical approach**: Server-rendered PHP 8.x web application using a modular MVC architecture (custom lightweight Router in `app/Core`, no full-stack framework), PostgreSQL accessed exclusively through PDO with prepared statements, Bootstrap 5 + vanilla JavaScript (ES6+, no React) for the UI and interactive activities, Composer (PSR-4) for autoloading, and PHPUnit for testing. Server-side is always the authority for scoring; JavaScript only provides preliminary feedback.

## Technical Context

**Language/Version**: PHP 8.x

**Primary Dependencies**: Composer (PSR-4 autoloading), PDO (PostgreSQL driver), Bootstrap 5, vanilla JavaScript ES6+, PHPUnit (testing)

**Storage**: PostgreSQL (relational; files stored on filesystem under `storage/uploads/` with metadata in the database)

**Testing**: PHPUnit (unit + integration + contract tests)

**Target Platform**: Web application served over Apache/nginx + PHP (XAMPP for local development)

**Project Type**: Web application (full-stack, server-rendered pages with JSON API endpoints for interactive features)

**Performance Goals**: 1,000 concurrent students without noticeable degradation; activity/evaluation results returned within 2 seconds of submission; progress and grades reflected within 5 seconds

**Constraints**: Spanish UI; responsive (desktop/tablet/mobile) and accessible; security-first (authentication, role-based authorization, CSRF protection, input validation, prepared statements, audit); referential integrity and unique constraints; no full-stack PHP framework and no React

**Scale/Scope**: Single institution, single campus; MVP scope with 3 roles, ~20 domain entities, and 50+ screens/routes; future features (forums, chat, live classes, certificates, gamification, analytics, mobile app, multi-campus, multi-institution) explicitly out of scope

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

The project constitution (`.specify/memory/constitution.md`) is present but still in template/placeholder state (no ratified principles). No enforceable gates apply. The following governing principles are derived from the source SDD's architectural decisions (ADR-001…ADR-011) and are treated as the design authority:

- **Modular MVC with strict separation of concerns** (ADR-001, §65): View → presentation, JavaScript → interaction, Controller → coordination, Service → business rules, Repository → persistence, Database → data & integrity. Every feature MUST respect this layering; no business logic in controllers or views.
- **Saber reusability** (ADR-006/007/010): Saberes are independent of sections and are imparted through assignments; units/content MUST NOT be duplicated across sections.
- **Server authority for scoring** (§23): the server is the sole authority for scores, grades, correct answers, and approval; JavaScript checks are preliminary only.
- **Security & integrity** (§51–54): authentication, authorization, secure sessions, CSRF, validation, prepared statements, access control, audit, file protection, and unique constraints are non-negotiable.

**Gate status**: PASS (no violations; no ratified constitution to contradict).

**Post-design re-check (after Phase 1)**: PASS — the data model and contracts preserve the modular MVC layering, Saber reusability, server-side scoring authority, and the security/integrity constraints; no new violations introduced.

## Project Structure

### Documentation (this feature)

```text
specs/001-aula-virtual-academica/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
│   ├── api.md           # JSON API endpoints (interactive features)
│   └── routes.md        # Page routes / navigation map
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
aula_virtual/
├── app/
│   ├── Core/                    # Router, Request, Response, Session, Database, Auth, Exceptions
│   ├── Modules/
│   │   ├── Auth/                # Login/logout, session, password handling
│   │   ├── Users/               # User management
│   │   ├── AcademicPeriods/     # Períodos académicos
│   │   ├── Tracks/              # Trayectos
│   │   ├── Sections/            # Secciones numéricas
│   │   ├── Students/            # Enrollment (section_students)
│   │   ├── Teachers/            # Teacher assignments
│   │   ├── Sabers/              # Saberes + units
│   │   ├── Units/               # Unit editor & publishing
│   │   ├── Content/             # Content blocks
│   │   ├── Activities/          # Interactive activities
│   │   ├── Assessments/         # Evaluations, questions, attempts
│   │   ├── Grades/              # Calificaciones + config
│   │   ├── Progress/            # Progreso per saber/unit/block
│   │   ├── Files/               # File upload/storage
│   │   ├── Reports/             # Reportes
│   │   └── Audit/               # Auditoría
│   └── Shared/                  # Shared views, helpers, DTO base classes
├── public/                      # Web root (index.php front controller, assets/css/js)
├── storage/
│   └── uploads/                 # Uploaded files (outside PostgreSQL)
├── config/                      # Environment & app configuration
├── database/                    # Migrations & seeders (PostgreSQL)
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Contract/                # API contract tests
├── composer.json
└── specs/                       # Spec Kit artifacts (this feature)
```

Each module follows the same internal layout (ADR-048):

```text
<Module>/
├── Controllers/
├── Models/
├── Services/
├── Repositories/
├── DTO/
├── Views/
└── Routes/
```

**Structure Decision**: Single web application (server-rendered) — not a split frontend/backend or mobile project. The source layout above is derived from SDD §47–49 and uses a custom lightweight Router in `app/Core` rather than a full-stack framework (ADR-001). JavaScript is served as static assets under `public/assets` and enhances interactivity (activities, unit editor, auto-submit) without a JS build toolchain.

## Complexity Tracking

> Fill ONLY if Constitution Check has violations that must be justified.

No violations — no entries.
