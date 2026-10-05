---

description: "Task list for feature implementation"
---

# Tasks: Aula Virtual AcadÃ©mica (Institutional Virtual Classroom)

**Input**: Design documents from `/specs/001-aula-virtual-academica/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: Included minimally per [plan.md](./plan.md)'s PHPUnit strategy â€” a PHPUnit harness plus contract tests for the two server-authoritative scoring endpoints (activity submit, assessment attempt submit).

**Organization**: Tasks are grouped by user story so each story is independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions.

## Path Conventions

Single web application at repository root (`c:\xampp\htdocs\aula_virtual`):

- `app/Core/` â€” framework primitives (Router, Request, Response, Session, Database, Auth, Exceptions)
- `app/Modules/<Module>/` â€” domain modules, each with `Controllers/`, `Models/`, `Services/`, `Repositories/`, `DTO/`, `Views/`, `Routes/`
- `public/` â€” web root (front controller + assets)
- `storage/uploads/` â€” uploaded files
- `config/` â€” environment/app config
- `database/` â€” migrations & seeders
- `tests/` â€” `Unit/`, `Integration/`, `Contract/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure.

- [X] T001 Create project directory structure per [plan.md](./plan.md): `app/Core/`, `app/Modules/{Auth,Users,AcademicPeriods,Tracks,Sections,Students,Sabers,Units,Content,Activities,Assessments,Grades,Progress,Files,Reports,Audit}/`, `public/assets/css`, `public/assets/js`, `storage/uploads/`, `config/`, `database/`, `tests/{Unit,Integration,Contract}/`
- [X] T002 Create `composer.json` with PSR-4 autoload mapping `App\` â†’ `app/`, require `vlucas/phpdotenv` and `phpunit/phpunit` (dev), then run `composer install`
- [X] T003 [P] Create `config/.env.example` and `config/config.php` (loads env, exposes DB/app settings)
- [X] T004 [P] Create `public/index.php` front controller and `public/assets/css/app.css`, `public/assets/js/app.js` stubs; include Bootstrap 5 via CDN
- [X] T005 [P] Create `database/migrate.php` (migration runner) and `database/seed.php` (role/user seeding entry point)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented.

**âš ï¸ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T006 Create `app/Core/Router/Router.php` (route registration + dispatch; integrate with `public/index.php` front controller)
- [X] T007 [P] Create `app/Core/Request/Request.php` (input/query/body access, input sanitization helper) and `app/Core/Response/Response.php` (HTML view + JSON response helpers)
- [X] T008 [P] Create `app/Core/Session/Session.php` (secure session wrapper: httponly, samesite, secure flag)
- [X] T009 [P] Create `app/Core/Database/Connection.php` (PDO PostgreSQL connection + prepared-statement helper) and `app/Core/Database/QueryBuilder.php` (bound-parameter query builder â€” no string concatenation of user input)
- [X] T010 [P] Create `app/Core/Exceptions/` (base exception, HTTP exceptions 400/403/404/409/422, global error handler)
- [X] T011 [P] Create `app/Core/Auth/Auth.php` (login/logout, current user, `password_hash()`/`password_verify()` with Argon2id) and `app/Core/Auth/PasswordHasher.php`
- [X] T012 [P] Create `app/Core/Csrf/Csrf.php` (token generate/validate) and wire CSRF check into every state-changing form/request
- [X] T013 Create `app/Core/Controller.php` (base controller: view rendering, auth guard, role check, CSRF guard) and `app/Shared/Views/layout.php` (Bootstrap layout with role-aware navigation: Inicio/Mi SecciÃ³n/Mis Saberes/Dashboard/Admin menu)
- [X] T014 Create `database/` migrations for `users`, `roles`, `user_roles` with constraints per [data-model.md](./data-model.md) (`users.email` UNIQUE + valid email format; `roles.name` UNIQUE with ADMIN/TEACHER/STUDENT; `user_roles` UNIQUE(user_id, role_id)); seed the three roles and a first ADMIN user in `database/seed.php`
- [X] T015 [P] Create `phpunit.xml` and `tests/bootstrap.php` (autoload + test database config)
- [X] T016 Create `app/Modules/Auth/` login/logout: `Controllers/AuthController.php`, `Routes/routes.php`, `Views/login.php` (`GET /login`, `POST /login`, `POST /logout`) â€” email+password login

**Checkpoint**: Foundation ready â€” user story implementation can now begin.

---

## Phase 3: User Story 1 - Administrators configure the academic structure (Priority: P1) ðŸŽ¯ MVP

**Goal**: An administrator creates academic periods, trayectos, numeric sections, users/roles, and enrolls students.

**Independent Test**: Admin creates a period "2026-2027" â†’ trayecto "Trayecto 3" â†’ section "SecciÃ³n 20" â†’ enrolls a student; the duplicate "SecciÃ³n 20" in the same period+trayecto is rejected.

### Implementation for User Story 1

- [X] T017 [P] [US1] Create `AcademicPeriod` model, repository, and migration in `app/Modules/AcademicPeriods/` (`name` NOT NULL; `code` NOT NULL UNIQUE; `start_date`/`end_date` NOT NULL with `end_date >= start_date`; `status` enum DRAFT/ACTIVE/CLOSED/ARCHIVED)
- [X] T018 [P] [US1] Create `Track` model, repository, and migration in `app/Modules/Tracks/` (`code` NOT NULL UNIQUE; `name` NOT NULL; `position` NOT NULL for ordering; `status` ACTIVE/INACTIVE)
- [X] T019 [P] [US1] Create `AcademicPeriodTrack` model, repository, and migration (FKs to period + track; UNIQUE(academic_period_id, track_id)) in `app/Modules/Tracks/`
- [X] T020 [P] [US1] Create `Section` model, repository, and migration in `app/Modules/Sections/` (`number` positive int; UNIQUE(academic_period_id, track_id, number); displayed as "SecciÃ³n <number>"; `status` ACTIVE/INACTIVE)
- [X] T021 [P] [US1] Create user management repository + `Role`/`UserRole` models in `app/Modules/Users/` (`first_name`/`last_name` NOT NULL; `email` UNIQUE; `status` ACTIVE/INACTIVE)
- [X] T022 [P] [US1] Create `SectionStudent` enrollment model, repository, and migration in `app/Modules/Students/` (`enrollment_date` NOT NULL; `withdrawal_date` nullable >= enrollment_date; UNIQUE(section_id, student_id); `status` ENROLLED/WITHDRAWN)
- [X] T023 [US1] Implement `AcademicPeriodService` + `AcademicPeriodController` + `Views/` + `Routes/routes.php` in `app/Modules/AcademicPeriods/` (CRUD; status transition DRAFTâ†’ACTIVEâ†’CLOSEDâ†’ARCHIVED forward-only; routes `/academic-periods`, `/academic-periods/{id}`)
- [X] T024 [US1] Implement `TrackService` + `TrackController` + `Views/` + `Routes/` in `app/Modules/Tracks/` (CRUD, position ordering, associate tracks to periods via `academic_period_tracks`; routes `/tracks`, `/tracks/{id}`)
- [X] T025 [US1] Implement `SectionService` + `SectionController` + `Views/` + `Routes/` in `app/Modules/Sections/` (CRUD; enforce UNIQUE(period+trayecto+number) with friendly error; routes `/sections`, `/sections/{id}`)
- [X] T026 [US1] Implement `UserService` + `UserController` + `Views/` + `Routes/` in `app/Modules/Users/` (user CRUD, role assignment; routes `/users`, `/students`, `/teachers` listing)
- [X] T027 [US1] Implement enrollment service + controller + `Views/` in `app/Modules/Students/` (enroll a STUDENT-role user into a section with enrollment date; record withdrawal; prevent duplicate enrollment in same section)
- [X] T028 [US1] Add role-based access checks for all US1 routes (ADMIN only) and wire the admin dashboard navigation entries (Usuarios, PerÃ­odos, Trayectos, Secciones)

**Checkpoint**: User Story 1 fully functional and independently testable.

---

## Phase 4: User Story 2 - Administrators configure saberes and section assignments (Priority: P1)

**Goal**: An administrator creates saberes (with units), assigns them to sections for a period, and assigns teachers (one primary).

**Independent Test**: Create saber "MatemÃ¡tica" with units, assign to SecciÃ³n 20 and SecciÃ³n 33 for 2026-2027, assign a primary teacher; duplicate saber+section+period assignment is rejected; both sections share the same saber units.

### Implementation for User Story 2

- [X] T029 [P] [US2] Create `Saber` model, repository, and migration in `app/Modules/Sabers/` (`code` NOT NULL UNIQUE; `name` NOT NULL; `track_id` FK NOT NULL; `unlock_mode` enum FREE/SEQUENTIAL default FREE; `status` ACTIVE/INACTIVE)
- [X] T030 [P] [US2] Create `Unit` model, repository, and migration in `app/Modules/Sabers/` (`saber_id` FK NOT NULL; `title` NOT NULL; `position` NOT NULL; `status` enum DRAFT/PUBLISHED/UNPUBLISHED)
- [X] T031 [P] [US2] Create `SaberAssignment` model, repository, and migration in `app/Modules/Sabers/` (FKs saber/section/academic_period; `start_date`/`end_date` nullable; UNIQUE(saber_id, section_id, academic_period_id); `status` DRAFT/PUBLISHED/CLOSED)
- [X] T032 [P] [US2] Create `AssignmentTeacher` model, repository, and migration in `app/Modules/Sabers/` (`is_primary` bool default false; UNIQUE(saber_assignment_id, teacher_id); at most one `is_primary=true` per assignment via partial unique index)
- [X] T033 [US2] Implement `SaberService` + `SaberController` + `Views/` + `Routes/` in `app/Modules/Sabers/` (saber CRUD; routes `/sabers`, `/sabers/{id}`, `/sabers/{id}/units`)
- [X] T034 [US2] Implement unit CRUD (list/create/edit/order under a saber) in `app/Modules/Sabers/` (service + controller + views; routes `/sabers/{id}/units`, `/units/{id}`)
- [X] T035 [US2] Implement `SaberAssignmentService` + `AssignmentController` + `Views/` + `Routes/` in `app/Modules/Sabers/` (assign saberâ†’section+period enforcing UNIQUE(saber+section+period) and track match; assign primary/secondary teachers; publish/close; routes `/saber-assignments`, `/saber-assignments/{id}`)

**Checkpoint**: User Stories 1 AND 2 both work independently.

---

## Phase 5: User Story 3 - Teachers build and publish learning units (Priority: P1)

**Goal**: A teacher builds a unit with ordered content blocks (text, image, video, PDF, link) and publishes it.

**Independent Test**: Teacher creates a unit, adds five blocks (text, image, PDF, video, link), reorders them, and publishes; a student in the section sees the blocks in order.

### Implementation for User Story 3

- [X] T036 [P] [US3] Create `ContentBlock` model, repository, and migration in `app/Modules/Content/` (`unit_id` FK NOT NULL; `type` enum TEXT/IMAGE/VIDEO/PDF/LINK/EMBED/ACTIVITY/ASSESSMENT; `position` NOT NULL; `title` nullable; `content` text/json; `settings` jsonb nullable; `status` ACTIVE/INACTIVE)
- [X] T037 [P] [US3] Create `File` model, repository, migration, and upload service in `app/Modules/Files/` (`original_name` NOT NULL; `stored_path` NOT NULL UNIQUE; `mime_type` NOT NULL; `size_bytes` NOT NULL; `uploader_id` FK NOT NULL; store files under `storage/uploads/`, keep only metadata in DB; enforce allowed MIME types + size limits)
- [X] T038 [US3] Implement `UnitEditorService` in `app/Modules/Units/` (block create/edit/delete/duplicate/reorder/activate/deactivate with `position` ordering; validate block payload per type â€” VIDEO requires a valid YouTube video id)
- [X] T039 [US3] Implement unit editor controller + routes + `Views/` in `app/Modules/Units/` (`/units/{id}/edit`) with a visual block toolbar (`+ Texto`, `+ Video`, `+ Imagen`, `+ PDF`, `+ Actividad`, `+ EvaluaciÃ³n`) and `app/Modules/Units/` client JS for add/reorder/remove
- [X] T040 [US3] Implement content block rendering views in `app/Modules/Content/Views/` (text, image, PDF, link, and YouTube iframe embed via stored `youtube_video_id`)
- [X] T041 [US3] Implement file upload controller + authorized download route in `app/Modules/Files/` (uploads protected by auth/role; downloads routed through an access-controlled controller)
- [X] T042 [US3] Implement unit publish/unpublish in `app/Modules/Units/` (DRAFTâ†’PUBLISHEDâ†’UNPUBLISHED) and gate student visibility to PUBLISHED units only

**Checkpoint**: User Stories 1â€“3 all work independently.

---

## Phase 6: User Story 4 - Students browse and learn from assigned saberes (Priority: P1)

**Goal**: A student logs in, sees their section and assigned saberes with progress, and consumes unit content.

**Independent Test**: Student logs in, dashboard shows SecciÃ³n 20 and MatemÃ¡tica, opens a saber and a unit, and views text/image/video/PDF/link blocks.

### Implementation for User Story 4

- [X] T043 [P] [US4] Create student dashboard controller + `Views/` in `app/Modules/Sabers/` (`/dashboard`, `/my-section`, `/my-sabers`) showing the student's section and assigned saberes
- [X] T044 [US4] Implement saber detail + unit content views for students (`/sabers/{id}`, `/units/{id}`) rendering blocks from US3
- [X] T045 [US4] Implement `UnlockService` in `app/Modules/Progress/` (FREE vs SEQUENTIAL unit navigation; SEQUENTIAL gating uses a progress lookup stub finalized in US7)
- [X] T046 [US4] Add student route authorization: students may only access saberes/sections assigned to them via `saber_assignments` + `section_students`

**Checkpoint**: User Story 4 independently functional (learning flow works end-to-end).

---

## Phase 7: User Story 5 - Students complete interactive activities (Priority: P1)

**Goal**: Students complete multiple-choice, true/false, and single-answer activities with immediate feedback; server is the scoring authority.

**Independent Test**: Student answers a multiple-choice and a true/false activity and immediately sees correct/incorrect feedback; formative activities do not affect grade.

### Tests for User Story 5 âš ï¸

- [X] T047 [P] [US5] Contract test for `POST /api/activity/{id}/submit` in `tests/Contract/ActivitySubmitTest.php` (assert request/response shape from [contracts/api.md](./contracts/api.md), server-side correctness, 404/409/422 cases)

### Implementation for User Story 5

- [X] T048 [P] [US5] Create activity question-schema validator in `app/Modules/Activities/` (validates `settings` JSON for MULTIPLE_CHOICE/TRUE_FALSE/SINGLE_ANSWER: prompt, options, exactly one correct for TRUE_FALSE/SINGLE_ANSWER, one-or-more for MULTIPLE_CHOICE)
- [X] T049 [US5] Implement `ActivityService` + `ActivityController` in `app/Modules/Activities/` for `POST /api/activity/{id}/submit` â€” recompute correctness and score server-side against stored `settings`; return `{ is_correct, score, feedback }` per [contracts/api.md](./contracts/api.md)
- [X] T050 [US5] Implement activity client JS in `app/Modules/Activities/` (`public/assets/js` module: render the three question types, submit via `fetch()`, show immediate feedback without trusting client score)
- [X] T051 [US5] Implement formative vs graded handling: FORMATIVE activities update progress only; GRADED activities additionally record a grade source (delegates to US7 grade service); enforce "already completed â†’ 409"

**Checkpoint**: User Story 5 independently functional (activities scored server-side).

---

## Phase 8: User Story 6 - Students take evaluations with automatic grading (Priority: P2)

**Goal**: Teachers create evaluations (questions + correct answers, attempt/time limits); students attempt them; the system auto-grades and shows score, percentage, and pass/fail.

**Independent Test**: Teacher creates an evaluation (max 10, passing 6, max 2 attempts); student attempts and submits; score/percentage/pass-fail shown; attempt limit and availability window enforced.

### Tests for User Story 6 âš ï¸

- [X] T052 [P] [US6] Contract test for `POST /api/assessment/{id}/attempt` and `POST /api/assessment/{id}/attempt/{attemptId}/submit` in `tests/Contract/AssessmentAttemptTest.php` (assert [contracts/api.md](./contracts/api.md) shapes; auto-grading accuracy; attempt-limit/window enforcement)

### Implementation for User Story 6

- [X] T053 [P] [US6] Create `Assessment` model, repository, and migration in `app/Modules/Assessments/` (`unit_id` OR `saber_assignment_id` exactly one set; `title` NOT NULL; `max_score` > 0; `passing_score` default 60% of max, <= max_score; `max_attempts` nullable; `time_limit` seconds nullable; `available_from/until` nullable; `status` DRAFT/PUBLISHED/CLOSED)
- [X] T054 [P] [US6] Create `Question` + `QuestionOption` models, repositories, and migrations in `app/Modules/Assessments/` (`type` MULTIPLE_CHOICE/TRUE_FALSE/SINGLE_ANSWER; `points` >= 0; `position`; options with `is_correct`; at least one correct option per question)
- [X] T055 [P] [US6] Create `AssessmentAttempt` + `AttemptAnswer` models, repositories, and migrations in `app/Modules/Assessments/` (`attempt_number`; UNIQUE(assessment_id, student_id, attempt_number); `status` IN_PROGRESS/COMPLETED/CANCELLED/EXPIRED; `score`/`percentage` set on completion; UNIQUE(attempt_id, question_id))
- [X] T056 [US6] Implement `AssessmentService` + `AssessmentController` + `Views/` + `Routes/` in `app/Modules/Assessments/` (teacher CRUD, publish, question/option editing; routes `/assessments`, `/assessments/{id}`)
- [X] T057 [US6] Implement attempt endpoints in `app/Modules/Assessments/` (`POST /api/assessment/{id}/attempt` start attempt honoring max_attempts + availability window + time_limit; `POST /api/assessment/{id}/attempt/{attemptId}/submit`)
- [X] T058 [US6] Implement auto-grading service in `app/Modules/Assessments/` (compare `attempt_answers` to correct options server-side; compute score/percentage/pass; transition IN_PROGRESSâ†’COMPLETED/EXPIRED; write `Grade` via US7 grade service)
- [X] T059 [US6] Implement evaluation client JS + student views in `app/Modules/Assessments/` (render questions, timer for time_limit, submit via `fetch()`, show result)

**Checkpoint**: User Story 6 independently functional (evaluations auto-graded).

---

## Phase 9: User Story 7 - Stakeholders track progress, grades, and reports (Priority: P2)

**Goal**: Students see progress/grades per saber/unit; teachers drill trackâ†’sectionâ†’saberâ†’studentsâ†’progressâ†’grades; admins see reports and audit trail.

**Independent Test**: After a student completes activities + an evaluation, student/teacher/admin each see consistent progress and grades; audit records key actions.

### Implementation for User Story 7

- [X] T060 [P] [US7] Create progress models, repositories, and migrations in `app/Modules/Progress/` (`StudentSaberProgress` UNIQUE(student_id, saber_assignment_id); `StudentUnitProgress` UNIQUE(student_id, unit_id); `StudentBlockProgress` UNIQUE(student_id, block_id) with `status` NOT_STARTED/IN_PROGRESS/COMPLETED)
- [X] T061 [P] [US7] Create `GradeComponent` + `Grade` models, repositories, and migrations in `app/Modules/Grades/` (`GradeComponent.weight` percentage, weights sum to 100 per assignment; `Grade` UNIQUE(saber_assignment_id, student_id, source_type, source_id) when source_id set; `graded_by` nullable = auto)
- [X] T062 [P] [US7] Create `AuditLog` model, repository, and migration in `app/Modules/Audit/` (`user_id` nullable; `action`/`entity_type`/`entity_id` NOT NULL; `old_data`/`new_data` jsonb; immutable rows)
- [X] T063 [US7] Implement `ProgressService` in `app/Modules/Progress/` (compute/update saber+unit+block progress from completed blocks/activities/evaluations; finalize SEQUENTIAL unlock from US4 stub)
- [X] T064 [US7] Implement progress API endpoints in `app/Modules/Progress/` (`GET /api/student/progress`, `POST /api/progress/block`, `POST /api/progress/video`) per [contracts/api.md](./contracts/api.md)
- [X] T065 [US7] Implement `GradeService` in `app/Modules/Grades/` (weighted component aggregation â†’ composite score/percentage/grade_value; manual grade adjust recording `graded_by` + `graded_at`; grades API `GET /api/student/grades` and `GET /api/teacher/assignment/{id}/students`)
- [X] T066 [US7] Implement student grades views and teacher grades views (drill trackâ†’sectionâ†’saberâ†’studentsâ†’progressâ†’grades) in `app/Modules/Grades/` and `app/Modules/Sabers/`
- [X] T067 [US7] Implement `ReportService` + `ReportController` + `Views/` in `app/Modules/Reports/` (teacher: students per section, progress, grades, evaluation results; admin: students per trayecto/section, saberes per trayecto/section, teachers per saber, academic results)
- [X] T068 [US7] Implement `AuditService` in `app/Modules/Audit/` (record key actions: create/modify saber, create/publish unit, create evaluation, modify grade, assign saber, assign teacher, enroll student) + admin audit view `/audit`
- [X] T069 [US7] Integrate progress + grades into the student dashboard (`/dashboard`) and teacher dashboard (from US4/US1) so all three roles show consistent numbers

**Checkpoint**: All user stories independently functional; full feature complete.

---

## Phase 10: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories.

- [X] T070 [P] Accessibility & responsive polish: ARIA labels, keyboard navigation, Bootstrap responsive breakpoints across all module views
- [X] T071 [P] Security hardening review: confirm CSRF on all state-changing forms, role/authorization matrix on every route, prepared statements everywhere (no user-input concatenation), file-download authorization
- [X] T072 [P] Run `vendor/bin/phpunit` and fix failures
- [X] T073 [P] Execute the 7 validation scenarios in [quickstart.md](./quickstart.md) end-to-end and confirm expected outcomes
- [X] T074 [P] Update `README.md` with setup/run instructions (composer install, env config, migrations, seed, document root)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies â€” start immediately.
- **Foundational (Phase 2)**: Depends on Setup â€” BLOCKS all user stories.
- **User Stories (Phase 3â€“9)**: All depend on Foundational; proceed in priority order (P1 first) or in parallel if staffed.
- **Polish (Phase 10)**: Depends on all desired user stories.

### User Story Dependencies

- **US1 (P1)** â€” academic structure: no story dependencies (after Foundational). Entities: AcademicPeriod, Track, AcademicPeriodTrack, Section, User, Role/UserRole, SectionStudent.
- **US2 (P1)** â€” saberes & assignments: depends on US1 (needs Track, Section, User, AcademicPeriod). Entities: Saber, Unit, SaberAssignment, AssignmentTeacher.
- **US3 (P1)** â€” unit builder: depends on US2 (needs Unit). Entities: ContentBlock, File.
- **US4 (P1)** â€” student learning: depends on US1+US2+US3 (needs sections, assignments, units, blocks).
- **US5 (P1)** â€” activities: depends on US3 (needs ContentBlock ACTIVITY) and US4 (student context).
- **US6 (P2)** â€” evaluations: depends on US2 (unit/assignment) and US7's Grade service for writing grades (grade write may be deferred; auto-grade can be tested without final grades).
- **US7 (P2)** â€” progress/grades/reports/audit: depends on US1â€“US6 for the data it aggregates; its Grade service is referenced by US5/US6.

### Within Each User Story

- Models + repositories before services.
- Services before controllers/endpoints.
- Controllers/endpoints before views/JS integration.
- Story complete before moving to the next priority.

### Parallel Opportunities

- All Phase 1 `[P]` tasks can run in parallel.
- All Phase 2 `[P]` tasks can run in parallel (within Foundational).
- Within US1: T017â€“T022 (models/migrations, different files) in parallel; then T023â€“T027 (services/controllers) can be built in parallel per module.
- Within US2: T029â€“T032 in parallel; then T033â€“T035 in parallel.
- Within US3: T036â€“T037 in parallel.
- Within US6: T053â€“T055 in parallel.
- Within US7: T060â€“T062 in parallel.
- Once Foundational completes, US1 and US2 can proceed (US2 after US1's entities), and independent modules within each story can be staffed separately.

---

## Parallel Example: User Story 1

```bash
# Launch all model/migration tasks for US1 together (different files):
Task: "T017 AcademicPeriod model/repo/migration in app/Modules/AcademicPeriods/"
Task: "T018 Track model/repo/migration in app/Modules/Tracks/"
Task: "T019 AcademicPeriodTrack model/repo/migration in app/Modules/Tracks/"
Task: "T020 Section model/repo/migration in app/Modules/Sections/"
Task: "T021 User/Role/UserRole repository+models in app/Modules/Users/"
Task: "T022 SectionStudent enrollment model/repo/migration in app/Modules/Students/"

# Then launch module service/controller work in parallel:
Task: "T023 AcademicPeriod service+controller+views+routes"
Task: "T024 Track service+controller+views+routes"
Task: "T025 Section service+controller+views+routes"
Task: "T026 User service+controller+views+routes"
Task: "T027 Enrollment service+controller+views"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL â€” blocks all stories).
3. Complete Phase 3: User Story 1 (academic structure administration).
4. **STOP and VALIDATE**: run the US1 scenario in [quickstart.md](./quickstart.md) independently.
5. Deploy/demo if ready.

### Incremental Delivery

1. Setup + Foundational â†’ login works, roles exist.
2. Add US1 â†’ admin can set up the academic structure â†’ Demo.
3. Add US2 â†’ saberes/units/assignments â†’ Demo.
4. Add US3 â†’ teacher unit builder â†’ Demo.
5. Add US4 â†’ student learning flow â†’ Demo.
6. Add US5 â†’ interactive activities â†’ Demo.
7. Add US6 â†’ evaluations + auto-grading â†’ Demo.
8. Add US7 â†’ progress/grades/reports/audit â†’ Demo (full MVP).
9. Each story adds value without breaking previous stories.

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together.
2. Once Foundational is done:
   - Developer A: US1 (academic structure)
   - Developer B: US2 (saberes & assignments) â€” after US1 entities
   - Developer C: US3 (unit builder) â€” after US2 units
3. US4/US5/US6/US7 follow their dependencies; independent modules within each story can be parallelized.

---

## Notes

- `[P]` tasks = different files, no dependencies.
- `[Story]` label maps each task to a user story for traceability.
- Each user story is independently completable and testable.
- Commit after each task or logical group; stop at any checkpoint to validate.
- Avoid vague tasks, same-file conflicts, and cross-story dependencies that break independence.
