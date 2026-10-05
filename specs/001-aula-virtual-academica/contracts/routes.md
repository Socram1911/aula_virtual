# Page Routes (Navigation Map): Aula Virtual Académica

**Feature**: 001-aula-virtual-academica | **Date**: 2026-10-04

Server-rendered page routes. Friendly Spanish labels are shown in the UI even where the underlying path uses technical English names (§46). All routes except `/login` require authentication; role columns indicate which roles may access each area.

## Public

| Route | Label | Access |
|-------|-------|--------|
| `/login` | Iniciar sesión | Public |

## Student

| Route | Label | Access |
|-------|-------|--------|
| `/dashboard` | Inicio | STUDENT |
| `/my-section` | Mi Sección | STUDENT |
| `/my-sabers` | Mis Saberes | STUDENT |
| `/sabers/{id}` | Saber (detalle) | STUDENT |
| `/units/{id}` | Unidad (contenido) | STUDENT |
| `/my-activities` | Actividades | STUDENT |
| `/assessments/{id}` | Evaluación | STUDENT |
| `/grades` | Calificaciones | STUDENT |
| `/profile` | Perfil | All authenticated |

## Teacher

| Route | Label | Access |
|-------|-------|--------|
| `/dashboard` | Dashboard | TEACHER |
| `/sections` | Secciones | TEACHER, ADMIN |
| `/my-sabers` | Mis Saberes | TEACHER |
| `/sabers/{id}/units` | Unidades del Saber | TEACHER, ADMIN |
| `/units/{id}/edit` | Editor de unidades | TEACHER, ADMIN |
| `/assessments` | Evaluaciones | TEACHER, ADMIN |
| `/grades` | Calificaciones | TEACHER |
| `/reports` | Reportes | TEACHER, ADMIN |

## Administrator

| Route | Label | Access |
|-------|-------|--------|
| `/users` | Usuarios | ADMIN |
| `/students` | Estudiantes | ADMIN |
| `/teachers` | Docentes | ADMIN |
| `/academic-periods` | Períodos | ADMIN |
| `/academic-periods/{id}` | Período (detalle) | ADMIN |
| `/tracks` | Trayectos | ADMIN |
| `/tracks/{id}` | Trayecto (detalle) | ADMIN |
| `/sections` | Secciones | TEACHER, ADMIN |
| `/sections/{id}` | Sección (detalle) | TEACHER, ADMIN |
| `/sabers` | Saberes | ADMIN |
| `/sabers/{id}` | Saber (detalle) | ADMIN |
| `/saber-assignments` | Asignaciones | ADMIN |
| `/saber-assignments/{id}` | Asignación (detalle) | ADMIN, TEACHER |
| `/reports` | Reportes | TEACHER, ADMIN |
| `/audit` | Auditoría | ADMIN |
| `/settings` | Configuración | ADMIN |

## Route patterns

- Collection → `/resource` (list) and `/resource/{id}` (detail) for periods, tracks, sections, sabers, assignments.
- Nested → `/sabers/{id}/units` for units of a saber; `/units/{id}` for a single unit's content.
- Async interactions use the JSON endpoints in [api.md](./api.md), not page routes.
