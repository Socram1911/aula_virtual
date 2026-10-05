# Feature Specification: Aula Virtual Académica (Institutional Virtual Classroom)

**Feature Branch**: `001-aula-virtual-academica`

**Created**: 2026-10-04

**Status**: Draft

**Input**: User description: "Software Design Document (SDD) for an institutional academic virtual classroom platform that manages academic periods, trayectos (tracks), sections, students, teachers, saberes, units, content, interactive activities, evaluations, attempts, grades, academic progress, reports, and audit."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Administrators configure the academic structure (Priority: P1)

An administrator sets up the backbone of the platform: they create an academic period (e.g., 2026-2027), create/activate trayectos within it, create numeric sections, register students, and create teacher accounts. This structure is what every other role depends on.

**Why this priority**: Without periods, trayectos, sections, and users, nothing else in the platform can exist. This is the foundational slice.

**Independent Test**: An administrator can create a period, a trayecto, three sections, and enroll several students; all entities appear correctly organized in the admin dashboard.

**Acceptance Scenarios**:

1. **Given** an administrator is logged in, **When** they create a period with a name, code, start date, and end date, **Then** the period appears in the list with a draft status and can be activated.
2. **Given** an active period, **When** the administrator creates a trayecto with a code and name and associates it to the period, **Then** the trayecto appears under that period.
3. **Given** a trayecto associated to a period, **When** the administrator creates a section with number 20 under that trayecto, **Then** the section is displayed as "Sección 20".
4. **Given** an existing section "Sección 20" in the same period + trayecto, **When** the administrator tries to create another section 20 in that period + trayecto, **Then** the system rejects it as a duplicate.
5. **Given** a section, **When** the administrator enrolls a student into it, **Then** the student appears in the section's student list.

---

### User Story 2 - Administrators configure saberes and section assignments (Priority: P1)

An administrator creates the curricular catalog: they create saberes (e.g., Matemática) associated to a trayecto, organize each saber into units, and then assign a saber to one or more sections for a period, assigning one or more teachers (one primary).

**Why this priority**: The saber + assignment model is the core of the platform's curriculum design and the reuse of saberes across sections is a key architectural requirement.

**Independent Test**: An administrator can create a saber with units, assign it to two different sections of the same trayecto, and assign a primary teacher; both sections reference the same saber without duplicating its units.

**Acceptance Scenarios**:

1. **Given** an administrator, **When** they create a saber with code, name, description, and trayecto, **Then** the saber appears in the trayecto's saber list.
2. **Given** a saber, **When** the administrator adds ordered units (title and description), **Then** the units appear under the saber in the configured order.
3. **Given** a saber, **When** the administrator assigns it to a section for a period with start/end dates, **Then** the assignment appears under that section.
4. **Given** a saber already assigned to a section for a period, **When** the administrator assigns the same saber to the same section for the same period again, **Then** the system rejects it as a duplicate.
5. **Given** a saber assignment, **When** the administrator assigns a primary teacher and a secondary teacher, **Then** both appear on the assignment with the primary marked as such.
6. **Given** a saber assigned to two sections, **When** a teacher edits the saber's units in one section, **Then** the same units are reflected in both sections (single source, no duplication).

---

### User Story 3 - Teachers build and publish learning units (Priority: P1)

A teacher opens one of their assigned saberes and builds a unit using a visual editor, adding ordered content blocks (text, image, video, PDF, link), interactive activities, and evaluations. They can create, edit, duplicate, reorder, activate, and deactivate blocks, then publish the unit for students.

**Why this priority**: The teacher-built units are the actual learning material students consume; this is the heart of instruction.

**Independent Test**: A teacher can create a unit with five content blocks (text, image, video, PDF, link), reorder them, and publish the unit; the blocks appear to a student in the configured order.

**Acceptance Scenarios**:

1. **Given** a teacher viewing an assigned saber, **When** they create a unit and add a text block, an image block, a PDF block, and a link block, **Then** the blocks are listed in the unit in the order they were added.
2. **Given** a unit with blocks, **When** the teacher reorders the blocks, **Then** the new order persists and is shown to students.
3. **Given** a unit with a block, **When** the teacher deactivates that block, **Then** the block is hidden from students but retained for later reactivation.
4. **Given** a unit, **When** the teacher duplicates a block, **Then** a copy of the block is created and can be edited independently.
5. **Given** a completed unit, **When** the teacher publishes it, **Then** students enrolled in the section can view it.
6. **Given** a published unit, **When** the teacher unpublishes it, **Then** students can no longer view it.

---

### User Story 4 - Students browse and learn from assigned saberes (Priority: P1)

A student logs in, sees their section and assigned saberes with progress, opens a saber, navigates its units, and consumes the content (text, images, PDFs, embedded videos, and links).

**Why this priority**: This is the primary value delivered to the end learner; without it the platform has no purpose.

**Independent Test**: A student can log in, see their section and assigned saberes, open a saber, and view a unit's text, video, PDF, and link content.

**Acceptance Scenarios**:

1. **Given** a student with valid credentials, **When** they log in, **Then** they land on a dashboard showing their section and assigned saberes.
2. **Given** the student dashboard, **When** the student opens a saber, **Then** they see the saber's units with their individual progress.
3. **Given** an open unit, **When** the student views a video block, **Then** the video plays embedded in the page (e.g., YouTube iframe) without downloading the file.
4. **Given** an open unit with a PDF block, **When** the student opens the PDF, **Then** the document is viewable/downloadable.
5. **Given** a unit configured with sequential unlocking, **When** the student has not completed the previous unit, **Then** the next unit is locked until the previous one is completed.

---

### User Story 5 - Students complete interactive activities and receive feedback (Priority: P1)

Students complete interactive activities embedded in units (multiple choice, true/false, single answer) and receive immediate feedback. Activity correctness and scores are determined by the server, never solely by the browser.

**Why this priority**: Interactive practice is how students apply what they learn and how the platform begins to measure understanding.

**Independent Test**: A student can answer a multiple-choice activity and a true/false activity and immediately see whether each answer was correct.

**Acceptance Scenarios**:

1. **Given** a unit with a multiple-choice activity, **When** the student selects an answer and submits, **Then** they immediately see whether the answer is correct.
2. **Given** a unit with a true/false activity, **When** the student submits an answer, **Then** the system records correctness based on server-side rules.
3. **Given** a formative activity, **When** the student completes it, **Then** it does not affect their grade, only their progress.
4. **Given** a graded activity, **When** the student completes it, **Then** its result contributes to progress according to the saber's configuration.

---

### User Story 6 - Students take evaluations with automatic grading (Priority: P2)

A teacher creates an evaluation (associated with a unit or with the saber assignment) with questions and defined correct answers, attempt limits, and a time limit. Students attempt the evaluation; the system automatically grades it and shows the score, percentage, and pass/fail result.

**Why this priority**: Formal evaluations are the primary measurement of learning and feed grades and reports, but they depend on content and activities existing first.

**Independent Test**: A teacher can create an evaluation with questions, a student can attempt it, and both see the auto-graded score and percentage.

**Acceptance Scenarios**:

1. **Given** a teacher, **When** they create an evaluation with a title, maximum score, passing score, and questions with points and correct answers, **Then** the evaluation appears under its unit or saber assignment.
2. **Given** an evaluation with a time limit, **When** a student starts an attempt, **Then** the attempt is timed and expires when the limit is reached.
3. **Given** a student who submits an evaluation attempt, **When** the attempt completes, **Then** the system auto-grades it and shows the score, percentage, and pass/fail result.
4. **Given** an evaluation with a maximum number of attempts, **When** a student reaches the limit, **Then** they cannot start another attempt.
5. **Given** an evaluation with an availability window, **When** a student tries to attempt it outside the window, **Then** the system prevents the attempt.

---

### User Story 7 - Stakeholders track progress, grades, and reports (Priority: P2)

Students see their progress and grades per saber and unit. Teachers see the progress and grades of their students and can drill from trayecto → section → saber → students → progress → grades. Administrators see institution-wide reports and an audit trail of key actions.

**Why this priority**: Tracking and reporting close the feedback loop and satisfy institutional accountability needs, but they depend on activities, evaluations, and grades being generated.

**Independent Test**: After a student completes activities and an evaluation, the student, the teacher, and the administrator can each view the student's progress percentage and grade through their respective dashboards.

**Acceptance Scenarios**:

1. **Given** a student who has completed some units, **When** they view their dashboard, **Then** they see progress per saber (e.g., "Matemática 65%") and per unit.
2. **Given** a teacher with an assigned saber, **When** they view students, **Then** they see each student's progress and grades for that saber.
3. **Given** a student with a recorded grade, **When** the student views grades, **Then** they see their grade for each saber.
4. **Given** an administrator, **When** they run reports, **Then** they can view students per trayecto/section, saberes per trayecto/section, teachers per saber, and academic results.
5. **Given** key actions (create saber, assign saber, enroll student, modify grade), **When** they occur, **Then** an audit record captures who performed the action, on which entity, and when.

---

### Edge Cases

- What happens when a student attempts an evaluation after its availability window or time limit has expired?
- What happens when a student exceeds the maximum number of attempts for an evaluation?
- What happens when an administrator tries to create a duplicate section number within the same period + trayecto?
- What happens when a saber is assigned twice to the same section in the same period?
- What happens when a student is enrolled twice in the same section?
- What happens when a student withdraws from a section: do their existing grades and progress remain visible?
- What happens when a unit or content block is deactivated while a student is mid-progress?
- What happens when an evaluation is edited after students have already attempted it?
- What happens when an embedded video or linked file is unavailable (broken link or missing file)?
- What happens when a student submits an activity or evaluation while offline?

## Requirements *(mandatory)*

### Functional Requirements

**Access & Security**

- **FR-001**: The system MUST require users to authenticate with a valid email and password before accessing any protected area.
- **FR-002**: The system MUST support three roles — Administrator, Teacher, and Student — each with distinct dashboards and permissions.
- **FR-003**: The system MUST restrict each user to only the data and actions permitted by their role and their specific assignments.
- **FR-004**: The system MUST protect all data-modifying actions against cross-site request forgery.
- **FR-005**: The system MUST validate all user input and control access to every protected action.
- **FR-006**: The system MUST treat all user-supplied input as untrusted data and MUST prevent that input from being interpreted as system commands or from altering data beyond what the user is authorized to do.

**Academic Structure**

- **FR-007**: An administrator MUST be able to create, edit, and archive academic periods with a name, code, start date, end date, and status (draft, active, closed, archived).
- **FR-008**: An administrator MUST be able to create, edit, order, and activate/deactivate trayectos (tracks) with a code, name, description, and display order.
- **FR-009**: An administrator MUST be able to associate trayectos with academic periods, allowing a trayecto to exist historically across multiple periods.
- **FR-010**: An administrator MUST be able to create and edit sections that belong to a period and a trayecto and are identified by a numeric number.
- **FR-011**: The system MUST display each section as "Sección <number>" (e.g., "Sección 20").
- **FR-012**: The system MUST enforce that a section number is unique within the combination of period + trayecto.

**Users, Enrollment & Teachers**

- **FR-013**: An administrator MUST be able to create and manage users with first name, last name, email, and status, and to assign roles.
- **FR-014**: An administrator MUST be able to enroll a student in a section with an enrollment date and record a withdrawal date.
- **FR-015**: The system MUST enforce that a student can be enrolled in a given section only once.
- **FR-016**: Teachers MUST be users with the Teacher role, and students MUST be users with the Student role.

**Saberes, Units & Assignments**

- **FR-017**: An administrator MUST be able to create and manage saberes with a code, name, description, and associated trayecto.
- **FR-018**: A saber MUST be reusable: it can be assigned to multiple sections of the same trayecto without duplicating its units or content.
- **FR-019**: An administrator MUST be able to create, edit, and order units under a saber (title and description).
- **FR-020**: An administrator MUST be able to assign a saber to a section for a specific period (a saber assignment) with start date, end date, and status.
- **FR-021**: The system MUST enforce that the combination of saber + section + period is unique.
- **FR-022**: An administrator MUST be able to assign one or more teachers to a saber assignment, marking one as the primary teacher.

**Content & Unit Editor**

- **FR-023**: A teacher (or administrator) MUST be able to build a unit with a visual editor that adds ordered content blocks.
- **FR-024**: Content blocks MUST support at least the following types: text, image, video, PDF, and link.
- **FR-025**: A teacher MUST be able to create, edit, delete, duplicate, reorder, activate, and deactivate content blocks within a unit.
- **FR-026**: Video blocks MUST embed videos by reference (e.g., a YouTube video identifier) without uploading the video file to the platform.
- **FR-027**: File-based content (images, PDFs) MUST be stored as files, with the system recording only descriptive metadata.
- **FR-028**: A teacher MUST be able to publish and unpublish a unit to control student visibility.

**Activities**

- **FR-029**: A teacher MUST be able to create interactive activities within a unit supporting at least multiple-choice, true/false, and single-answer question types.
- **FR-030**: Each activity MUST be classifiable as formative (practice) or graded (contributes to academic results).
- **FR-031**: The system MUST determine activity correctness and scores on the server as the authority; client-side checks are for immediate feedback only and MUST never determine final scores or approval.

**Evaluations**

- **FR-032**: A teacher MUST be able to create an evaluation associated with a unit or with a saber assignment, with a title, description, maximum score, and passing score.
- **FR-033**: An evaluation MUST support optional limits on the number of attempts and a time limit, plus an availability window (available from/until).
- **FR-034**: A teacher MUST be able to add questions to an evaluation, each with a type, points, and position, and to define answer options with correct answers marked.
- **FR-035**: A student MUST be able to attempt an evaluation within its availability window, with attempts numbered and subject to configured limits.
- **FR-036**: The system MUST automatically grade objective evaluations and present the score, percentage, and pass/fail result to the student.
- **FR-037**: The system MUST record each attempt's answers and correctness for later review.

**Grades**

- **FR-038**: The system MUST record a grade per student per saber assignment, including score, percentage, and a grade value, linked to the grading user and time.
- **FR-039**: The system MUST support configurable grade components with weights (e.g., activities, evaluations, projects) that can differ per saber.
- **FR-040**: A teacher MUST be able to review and, where permitted, adjust a student's grade, with a record of who changed it and when.

**Progress**

- **FR-041**: The system MUST track each student's progress at the saber, unit, and content-block levels.
- **FR-042**: The system MUST support two unlocking modes per saber: sequential (a unit unlocks after the previous one is completed) and free navigation.
- **FR-043**: A student MUST be able to see their own progress percentage per saber and per unit.

**Dashboards & Reports**

- **FR-044**: The student dashboard MUST show the student's section, assigned saberes with progress, activities, evaluations, and grades.
- **FR-045**: The teacher dashboard MUST show their sections, assigned saberes, units, activities, evaluations, and grades, and allow drilling trayecto → section → saber → students → progress → grades.
- **FR-046**: The administrator dashboard MUST provide access to users, students, teachers, periods, trayectos, sections, saberes, assignments, reports, audit, and configuration.
- **FR-047**: Teachers MUST be able to generate reports of students per section, progress, grades, and evaluation results.
- **FR-048**: Administrators MUST be able to generate reports of students per trayecto/section, saberes per trayecto/section, teachers per saber, and academic results.

**Audit**

- **FR-049**: The system MUST record an audit entry (who, action, entity type, entity id, before/after data, and timestamp) for key actions such as creating a saber, modifying a saber, creating a unit, publishing a unit, creating an evaluation, modifying a grade, assigning a saber, assigning a teacher, and enrolling a student.

**Experience**

- **FR-050**: The interface MUST be modern, professional, institutional, clean, responsive across desktop/tablet/mobile, and accessible.
- **FR-051**: The interface language MUST be Spanish.

### Key Entities *(include if feature involves data)*

- **Período Académico (Academic Period)**: A time window (name, code, start/end dates, status) that groups all academic activity.
- **Trayecto (Track)**: An academic level/route (code, name, description, order) within which sections and saberes are organized; configurable by the institution.
- **Sección (Section)**: A group of students within a trayecto and a period, identified by a numeric number (e.g., Sección 20).
- **Usuario (User)**: A person with first name, last name, email, and status, holding one or more roles.
- **Rol (Role)**: One of Administrator, Teacher, or Student.
- **Inscripción (Enrollment)**: The membership of a student in a section, with enrollment and withdrawal dates.
- **Saber**: The primary curricular unit (code, name, description) associated with a trayecto; reusable across sections.
- **Asignación de Saber (Saber Assignment)**: The imparting of a saber to a specific section in a specific period, with dates, status, and assigned teachers (primary/secondary).
- **Unidad (Unit)**: An ordered subdivision of a saber (title, description, order).
- **Bloque de Contenido (Content Block)**: An ordered content item (text, image, video, PDF, link, embed, activity, assessment) within a unit.
- **Actividad (Activity)**: An interactive practice item (multiple choice, true/false, single answer), either formative or graded.
- **Evaluación (Assessment)**: A formal measurement instrument with maximum score, passing score, and optional attempt/time limits.
- **Pregunta (Question)**: A question within an evaluation, with a type, points, position, and options (with correct answers marked).
- **Intento (Attempt)**: A student's single attempt at an evaluation, with attempt number, timestamps, score, percentage, and status.
- **Calificación (Grade)**: A result for a student on a saber assignment, with score, percentage, grade value, and who/when it was recorded.
- **Progreso (Progress)**: A student's advancement per saber, unit, and content block.
- **Registro de Auditoría (Audit Log)**: A record of who performed which action on which entity, with before/after data and timestamp.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator can set up a complete academic period (period, trayecto, three sections, saberes, and student enrollments) in under 30 minutes.
- **SC-002**: A teacher can create a unit containing five content blocks (text, image, video, PDF, link) and publish it in under 10 minutes.
- **SC-003**: A student can locate and open an assigned saber and its first unit within two interactions after logging in.
- **SC-004**: Students receive their activity or evaluation result immediately (within 2 seconds) after submitting.
- **SC-005**: Objective evaluations are auto-graded with 100% accuracy relative to the defined correct answers.
- **SC-006**: 95% of students complete an activity on the first attempt without external help.
- **SC-007**: Progress and grades reflect completed work within 5 seconds of submission.
- **SC-008**: The system supports at least 1,000 concurrent students without noticeable degradation.
- **SC-009**: The interface is fully usable on desktop, tablet, and mobile devices.
- **SC-010**: 90% of target users (administrators, teachers, and students) can complete their primary tasks without training or support.

## Assumptions

- The platform serves a single institution and single campus for v1; multi-campus and multi-institution support are future features and out of scope.
- The interface and learning content are in Spanish.
- Administrators have full management access; teachers can manage instructional content (units, content, activities, evaluations) only for the saberes and sections assigned to them; students have read-and-participate access only.
- Grade component weights are configurable with a sensible default (e.g., evaluations carry the primary weight), and a passing score defaults to 60% unless configured otherwise per evaluation.
- Videos are embedded by reference (e.g., YouTube) and are not hosted on the platform.
- Files (images, PDFs) are stored as files (on the server or object storage), with only metadata recorded in the system.
- Notifications (new unit, new activity, new evaluation, published grade, upcoming evaluation) are out of scope for the MVP but the architecture should allow adding them later.
- Future features listed in the source document (forums, chat, live classes, calendar, assignments, rubrics, certificates, gamification, badges, H5P, educational analytics, mobile app, multi-campus, multi-institution) are out of scope for v1.
