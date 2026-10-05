# API Contracts: Aula Virtual Académica

**Feature**: 001-aula-virtual-academica | **Date**: 2026-10-04

JSON endpoints used by the frontend for interactive/async operations. All endpoints are served under `/api/*`, require an authenticated session, enforce role-based authorization, and protect state changes with CSRF where applicable. Responses are JSON; errors use a consistent envelope.

## Conventions

- **Auth**: session cookie; `401` when not authenticated; `403` when unauthorized.
- **Envelope (success)**: `{ "data": ..., "meta": ... }` (shape shown per endpoint).
- **Envelope (error)**: `{ "error": { "code": "string", "message": "string", "details": [...] } }`.
- **Scoring authority**: correctness/points are always computed server-side; client submissions carry only answer selections.

## Endpoints

### POST `/api/activity/{id}/submit`

Submit an answer for an interactive activity (content block of type ACTIVITY).

**Request**:

```json
{ "answer": "string | number | string[]" }
```

**Response 200**:

```json
{
  "data": {
    "is_correct": true,
    "score": 1,
    "feedback": "Respuesta correcta",
    "progress": { "unit_percentage": 65, "saber_percentage": 55 }
  }
}
```

**Errors**: `404` unknown/inactive activity; `409` already completed; `422` invalid answer shape.

---

### POST `/api/assessment/{id}/attempt`

Start a new attempt for an evaluation.

**Response 201**:

```json
{
  "data": {
    "attempt_id": 123,
    "attempt_number": 2,
    "started_at": "2026-10-04T20:30:00Z",
    "time_limit_seconds": 1800,
    "questions": [
      { "id": 1, "type": "MULTIPLE_CHOICE", "question_text": "...", "points": 2, "options": [ { "id": 10, "option_text": "..." } ] }
    ]
  }
}
```

**Errors**: `403` outside availability window; `409` attempt limit reached; `404` unknown/unpublished assessment.

---

### POST `/api/assessment/{id}/attempt/{attemptId}/submit`

Submit answers for an in-progress attempt (auto-graded server-side).

**Request**:

```json
{ "answers": [ { "question_id": 1, "value": [10] } ] }
```

**Response 200**:

```json
{
  "data": {
    "attempt_id": 123,
    "status": "COMPLETED",
    "score": 8,
    "max_score": 10,
    "percentage": 80,
    "passed": true,
    "grade": { "grade_value": "8.0", "percentage": 80 }
  }
}
```

**Errors**: `404` attempt not found/not owned; `409` attempt already COMPLETED/EXPIRED; `422` incomplete or malformed answers.

---

### POST `/api/progress/block`

Record completion of a content block by the current student.

**Request**:

```json
{ "block_id": 42 }
```

**Response 200**:

```json
{
  "data": {
    "block_id": 42,
    "block_status": "COMPLETED",
    "unit_percentage": 100,
    "unit_completed": true,
    "saber_percentage": 55,
    "next_unlocked_unit_id": 7
  }
}
```

**Errors**: `403` block not part of the student's assigned saber; `404` unknown block.

---

### POST `/api/progress/video`

Record video playback events (`video_started`, `video_progress`, `video_completed`) for a VIDEO block.

**Request**:

```json
{ "block_id": 42, "event": "video_completed", "position_seconds": 318 }
```

**Response 200**:

```json
{ "data": { "recorded": true } }
```

---

### GET `/api/student/progress`

Current student's progress summary across assigned saberes.

**Response 200**:

```json
{
  "data": {
    "saberes": [
      { "saber_assignment_id": 5, "saber_name": "Matemática", "percentage": 65, "units": [ { "unit_id": 7, "title": "Unidad 1", "percentage": 100, "status": "COMPLETED" } ] }
    ]
  }
}
```

---

### GET `/api/student/grades`

Current student's grades per saber assignment.

**Response 200**:

```json
{
  "data": {
    "grades": [
      { "saber_assignment_id": 5, "saber_name": "Matemática", "score": 8, "percentage": 80, "grade_value": "8.0", "graded_at": "2026-10-04T20:30:00Z" }
    ]
  }
}
```

---

### GET `/api/teacher/assignment/{id}/students`

Teacher view of students, progress, and grades for a saber assignment (authorized teachers only).

**Response 200**:

```json
{
  "data": {
    "students": [
      { "student_id": 3, "name": "Carlos Pérez", "progress_percentage": 55, "grade": { "score": 8, "percentage": 80, "grade_value": "8.0" } }
    ]
  }
}
```
