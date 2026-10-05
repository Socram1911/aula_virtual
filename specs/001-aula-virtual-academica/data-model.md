# Data Model: Aula Virtual Académica

**Feature**: 001-aula-virtual-academica | **Date**: 2026-10-04

This document defines the domain entities, their fields, relationships, validation rules, and state transitions. It is the source of truth for database migrations and repositories. Field types are PostgreSQL-oriented; implementation details (exact DDL) belong in `tasks.md` and the implementation phase.

## Conventions

- `id` is a surrogate primary key (e.g., `BIGSERIAL`/UUID — final type decided at implementation).
- `created_at`/`updated_at` are audit timestamps on mutable entities.
- `status` is a constrained enum-like string (checked by the application and enforced by a DB `CHECK`).
- All foreign keys enforce referential integrity; `UNIQUE` constraints listed per entity are mandatory.

## Entities

### 1. AcademicPeriod (Período Académico)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| name | string | NOT NULL |
| code | string | NOT NULL, UNIQUE |
| start_date | date | NOT NULL |
| end_date | date | NOT NULL, >= start_date |
| status | enum | DRAFT, ACTIVE, CLOSED, ARCHIVED |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `academic_period_tracks`; has many `sections`; has many `saber_assignments`.
- **State transitions**: DRAFT → ACTIVE → CLOSED → ARCHIVED (forward only).
- **Validation**: dates must be a valid range; only ACTIVE periods accept new assignments/enrollments.

### 2. Track (Trayecto)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| code | string | NOT NULL, UNIQUE |
| name | string | NOT NULL |
| description | text | nullable |
| position | int | NOT NULL (ordering) |
| status | enum | ACTIVE, INACTIVE |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `sabers`; linked to periods via `academic_period_tracks`; has many `sections`.
- **Validation**: `position` must be unique within the active set; interface shows "Trayecto" even though the technical name is "track".

### 3. AcademicPeriodTrack (Relación período ↔ trayecto)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| academic_period_id | FK → AcademicPeriod | NOT NULL |
| track_id | FK → Track | NOT NULL |
| status | enum | ACTIVE, INACTIVE |
| created_at, updated_at | timestamp | — |

- **Relationships**: joins a period and a track so a track has historical existence across periods.
- **Validation**: UNIQUE(academic_period_id, track_id).

### 4. Section (Sección)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| academic_period_id | FK → AcademicPeriod | NOT NULL |
| track_id | FK → Track | NOT NULL |
| number | int | NOT NULL (visible identifier) |
| name | string | nullable (defaults to "Sección <number>") |
| description | text | nullable |
| status | enum | ACTIVE, INACTIVE |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `section_students`; has many `saber_assignments`.
- **Validation**: UNIQUE(academic_period_id, track_id, number); `number` is a positive integer; displayed as "Sección 20".

### 5. User (Usuario)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| first_name | string | NOT NULL |
| last_name | string | NOT NULL |
| email | string | NOT NULL, UNIQUE, valid email format |
| password_hash | string | NOT NULL (hashed) |
| status | enum | ACTIVE, INACTIVE |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `user_roles`; as a student, has `section_students`; as a teacher, has `assignment_teachers`.
- **Validation**: email unique; passwords hashed with `password_hash()`; plaintext passwords never stored.

### 6. Role / UserRole

| Field | Type | Constraints |
|-------|------|-------------|
| Role.id | PK | — |
| Role.name | string | UNIQUE (ADMIN, TEACHER, STUDENT) |
| UserRole.id | PK | — |
| UserRole.user_id | FK → User | NOT NULL |
| UserRole.role_id | FK → Role | NOT NULL |

- **Relationships**: a user has one or more roles.
- **Validation**: UNIQUE(user_id, role_id); seed roles ADMIN, TEACHER, STUDENT.

### 7. SectionStudent (Inscripción)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| section_id | FK → Section | NOT NULL |
| student_id | FK → User (role STUDENT) | NOT NULL |
| enrollment_date | date | NOT NULL |
| withdrawal_date | date | nullable |
| status | enum | ENROLLED, WITHDRAWN |
| created_at, updated_at | timestamp | — |

- **Relationships**: links a student to a section.
- **Validation**: UNIQUE(section_id, student_id); student must have the STUDENT role; withdrawal_date >= enrollment_date.

### 8. Saber

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| code | string | NOT NULL, UNIQUE |
| name | string | NOT NULL |
| description | text | nullable |
| track_id | FK → Track | NOT NULL |
| unlock_mode | enum | FREE, SEQUENTIAL (default FREE) |
| status | enum | ACTIVE, INACTIVE |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `units`; imparted via `saber_assignments` (reusable across sections without duplicating units).
- **Validation**: belongs to exactly one track; `unlock_mode` controls unit navigation.

### 9. Unit (Unidad)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| saber_id | FK → Saber | NOT NULL |
| title | string | NOT NULL |
| description | text | nullable |
| position | int | NOT NULL (ordering) |
| status | enum | DRAFT, PUBLISHED, UNPUBLISHED |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `content_blocks`.
- **State transitions**: DRAFT → PUBLISHED → UNPUBLISHED → PUBLISHED (publish/unpublish lifecycle).
- **Validation**: `position` ordering per saber; only PUBLISHED units are visible to students.

### 10. ContentBlock (Bloque de contenido)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| unit_id | FK → Unit | NOT NULL |
| type | enum | TEXT, IMAGE, VIDEO, PDF, LINK, EMBED, ACTIVITY, ASSESSMENT |
| position | int | NOT NULL (ordering) |
| title | string | nullable |
| content | text/json | block payload |
| settings | jsonb | nullable (block-specific config; e.g., activity questions) |
| status | enum | ACTIVE, INACTIVE |
| created_at, updated_at | timestamp | — |

- **Relationships**: belongs to a unit; VIDEO blocks reference `youtube_video_id`; IMAGE/PDF blocks reference `files`.
- **Validation**: `position` ordering per unit; payload validated per type (e.g., VIDEO requires a valid YouTube id; ACTIVITY requires a valid question definition in `settings`).

### 11. File (Archivo)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| original_name | string | NOT NULL |
| stored_path | string | NOT NULL, UNIQUE (relative to `storage/uploads/`) |
| mime_type | string | NOT NULL |
| size_bytes | int | NOT NULL |
| uploader_id | FK → User | NOT NULL |
| created_at | timestamp | — |

- **Relationships**: referenced by content blocks (IMAGE/PDF) and other uploads.
- **Validation**: allowed MIME types and size limits enforced on upload; files stored outside PostgreSQL (§25).

### 12. SaberAssignment (Asignación de Saber)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| saber_id | FK → Saber | NOT NULL |
| section_id | FK → Section | NOT NULL |
| academic_period_id | FK → AcademicPeriod | NOT NULL |
| start_date | date | nullable |
| end_date | date | nullable |
| status | enum | DRAFT, PUBLISHED, CLOSED |
| created_at, updated_at | timestamp | — |

- **Relationships**: has many `assignment_teachers`; has many `assessments`; has many `grades`; has `grade_components`.
- **Validation**: UNIQUE(saber_id, section_id, academic_period_id); the saber's track must match the section's track; section's period must match `academic_period_id`.

### 13. AssignmentTeacher (Docente de asignación)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| saber_assignment_id | FK → SaberAssignment | NOT NULL |
| teacher_id | FK → User (role TEACHER) | NOT NULL |
| is_primary | bool | NOT NULL default false |
| created_at | timestamp | — |

- **Relationships**: assigns teachers to a saber assignment; at most one primary per assignment.
- **Validation**: UNIQUE(saber_assignment_id, teacher_id); at most one `is_primary = true` per assignment (partial unique index).

### 14. Assessment (Evaluación)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| unit_id | FK → Unit | nullable (unit-level evaluation) |
| saber_assignment_id | FK → SaberAssignment | nullable (saber-level evaluation) |
| title | string | NOT NULL |
| description | text | nullable |
| max_score | numeric | NOT NULL (> 0) |
| passing_score | numeric | NOT NULL (default 60% of max) |
| max_attempts | int | nullable (null = unlimited) |
| time_limit | int | nullable (seconds; null = no limit) |
| available_from | timestamp | nullable |
| available_until | timestamp | nullable |
| status | enum | DRAFT, PUBLISHED, CLOSED |
| created_at, updated_at | timestamp | — |

- **Relationships**: exactly one of `unit_id` or `saber_assignment_id` must be set; has many `questions`; has many `assessment_attempts`.
- **Validation**: passing_score <= max_score; availability window valid; questions sum to max_score (if scored by points).

### 15. Question (Pregunta)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| assessment_id | FK → Assessment | NOT NULL |
| type | enum | MULTIPLE_CHOICE, TRUE_FALSE, SINGLE_ANSWER (MVP) |
| question_text | text | NOT NULL |
| points | numeric | NOT NULL (>= 0) |
| position | int | NOT NULL (ordering) |
| settings | jsonb | nullable (type-specific config) |

- **Relationships**: has many `question_options`.
- **Validation**: at least one correct option for objective types.

### 16. QuestionOption (Opción)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| question_id | FK → Question | NOT NULL |
| option_text | text | NOT NULL |
| is_correct | bool | NOT NULL default false |
| position | int | NOT NULL (ordering) |

- **Validation**: TRUE_FALSE/SINGLE_ANSWER have exactly one correct option; MULTIPLE_CHOICE may have one or more.

### 17. AssessmentAttempt (Intento)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| assessment_id | FK → Assessment | NOT NULL |
| student_id | FK → User (role STUDENT) | NOT NULL |
| attempt_number | int | NOT NULL |
| started_at | timestamp | NOT NULL |
| completed_at | timestamp | nullable |
| score | numeric | nullable (set on completion) |
| percentage | numeric | nullable (0–100) |
| status | enum | IN_PROGRESS, COMPLETED, CANCELLED, EXPIRED |

- **State transitions**: IN_PROGRESS → COMPLETED (submitted) | EXPIRED (time limit/window) | CANCELLED (abandoned).
- **Validation**: UNIQUE(assessment_id, student_id, attempt_number); attempt_number <= max_attempts when set; auto-graded on completion.

### 18. AttemptAnswer (Respuesta)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| attempt_id | FK → AssessmentAttempt | NOT NULL |
| question_id | FK → Question | NOT NULL |
| answer_value | jsonb/text | NOT NULL (selected option ids / free text) |
| is_correct | bool | NOT NULL |
| score | numeric | NOT NULL |
| created_at | timestamp | — |

- **Validation**: UNIQUE(attempt_id, question_id); correctness and score computed server-side.

### 19. GradeComponent (Componente de calificación)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| saber_assignment_id | FK → SaberAssignment | NOT NULL |
| name | string | NOT NULL (e.g., "Actividades", "Evaluaciones", "Proyecto") |
| source_type | enum | ACTIVITIES, ASSESSMENTS, PROJECT (MVP) |
| weight | numeric | NOT NULL (percentage 0–100) |
| position | int | NOT NULL |

- **Relationships**: belongs to a saber assignment; weights sum to 100 per assignment.
- **Validation**: sum of weights per assignment = 100.

### 20. Grade (Calificación)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| saber_assignment_id | FK → SaberAssignment | NOT NULL |
| student_id | FK → User (role STUDENT) | NOT NULL |
| source_type | enum | ACTIVITY, ASSESSMENT, MANUAL, COMPOSITE |
| source_id | bigint | nullable (id of the originating entity) |
| score | numeric | NOT NULL |
| percentage | numeric | NOT NULL (0–100) |
| grade_value | string/numeric | NOT NULL (final value) |
| comments | text | nullable |
| graded_by | FK → User | nullable (null = auto) |
| graded_at | timestamp | NOT NULL |

- **Relationships**: links a student, a saber assignment, and a grading source.
- **Validation**: UNIQUE(saber_assignment_id, student_id, source_type, source_id) where source_id not null; MANUAL/COMPOSITE edits record `graded_by`.

### 21. StudentSaberProgress / StudentUnitProgress / StudentBlockProgress

| Entity | Key fields |
|--------|------------|
| StudentSaberProgress | student_id, saber_assignment_id, percentage, completed_units, status, updated_at |
| StudentUnitProgress | student_id, unit_id, percentage, completed_blocks, status, updated_at |
| StudentBlockProgress | student_id, block_id, status (NOT_STARTED, IN_PROGRESS, COMPLETED), completed_at |

- **Relationships**: saber progress aggregates unit progress; unit progress aggregates block progress.
- **Validation**: UNIQUE(student_id, saber_assignment_id); UNIQUE(student_id, unit_id); UNIQUE(student_id, block_id).

### 22. AuditLog (Auditoría)

| Field | Type | Constraints |
|-------|------|-------------|
| id | PK | — |
| user_id | FK → User | nullable (system actions) |
| action | string | NOT NULL (e.g., "create saber", "assign teacher") |
| entity_type | string | NOT NULL |
| entity_id | bigint | NOT NULL |
| old_data | jsonb | nullable |
| new_data | jsonb | nullable |
| ip_address | string | nullable |
| created_at | timestamp | NOT NULL |

- **Relationships**: records who performed which action on which entity.
- **Validation**: immutable rows (no update/delete).

## Relationship Summary (primary flows)

```
AcademicPeriod ──< AcademicPeriodTrack >── Track
AcademicPeriod ──< Section >──< SectionStudent >── User(STUDENT)
Track ──< Saber ──< Unit ──< ContentBlock >── File (IMAGE/PDF)
Saber ──< SaberAssignment >──< AssignmentTeacher >── User(TEACHER)
SaberAssignment ──< GradeComponent
SaberAssignment ──< Assessment ──< Question ──< QuestionOption
Assessment ──< AssessmentAttempt ──< AttemptAnswer
SaberAssignment ──< Grade >── User(STUDENT)
SaberAssignment/Unit/ContentBlock ──< Progress entities >── User(STUDENT)
All entities ──< AuditLog
```

## Key Constraints (from spec FRs)

- Section number UNIQUE within (period + track) — FR-012.
- Saber assignment UNIQUE within (saber + section + period) — FR-021.
- Student enrollment UNIQUE within (section + student) — FR-015.
- Server-side scoring authority for activities and evaluations — FR-031, FR-036.
