-- Esquema PostgreSQL — Aula Virtual Académica (producción: Neon y similares).
-- Migrado desde MySQL/MariaDB. Notas:
--   * ENUM → VARCHAR + CHECK
--   * TINYINT(1) → BOOLEAN
--   * AUTO_INCREMENT → SERIAL/BIGSERIAL
--   * JSON → JSONB
--   * DATETIME → TIMESTAMP

CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING' CHECK (status IN ('ACTIVE','INACTIVE','PENDING')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_roles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, role_id)
);

CREATE TABLE IF NOT EXISTS academic_periods (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','ACTIVE','CLOSED','ARCHIVED')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tracks (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','INACTIVE')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS academic_period_tracks (
    id BIGSERIAL PRIMARY KEY,
    academic_period_id BIGINT NOT NULL REFERENCES academic_periods(id) ON DELETE CASCADE,
    track_id BIGINT NOT NULL REFERENCES tracks(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','INACTIVE')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (academic_period_id, track_id)
);

CREATE TABLE IF NOT EXISTS sections (
    id BIGSERIAL PRIMARY KEY,
    academic_period_id BIGINT NOT NULL REFERENCES academic_periods(id) ON DELETE CASCADE,
    track_id BIGINT NOT NULL REFERENCES tracks(id) ON DELETE CASCADE,
    number INTEGER NOT NULL,
    name VARCHAR(150) NULL,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','INACTIVE')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (academic_period_id, track_id, number)
);

CREATE TABLE IF NOT EXISTS section_students (
    id BIGSERIAL PRIMARY KEY,
    section_id BIGINT NOT NULL REFERENCES sections(id) ON DELETE CASCADE,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    enrollment_date DATE NOT NULL,
    withdrawal_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ENROLLED' CHECK (status IN ('ENROLLED','WITHDRAWN')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (section_id, student_id)
);

CREATE TABLE IF NOT EXISTS sabers (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    track_id BIGINT NOT NULL REFERENCES tracks(id) ON DELETE CASCADE,
    unlock_mode VARCHAR(20) NOT NULL DEFAULT 'FREE' CHECK (unlock_mode IN ('FREE','SEQUENTIAL')),
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','INACTIVE')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS units (
    id BIGSERIAL PRIMARY KEY,
    saber_id BIGINT NOT NULL REFERENCES sabers(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','PUBLISHED','UNPUBLISHED')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS files (
    id BIGSERIAL PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INTEGER NOT NULL,
    uploader_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS content_blocks (
    id BIGSERIAL PRIMARY KEY,
    unit_id BIGINT NOT NULL REFERENCES units(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL CHECK (type IN ('TEXT','IMAGE','VIDEO','PDF','LINK','EMBED','ACTIVITY','ASSESSMENT','H5P')),
    position INTEGER NOT NULL DEFAULT 0,
    title VARCHAR(200) NULL,
    content TEXT NULL,
    settings JSONB NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','INACTIVE')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS saber_assignments (
    id BIGSERIAL PRIMARY KEY,
    saber_id BIGINT NOT NULL REFERENCES sabers(id) ON DELETE CASCADE,
    section_id BIGINT NOT NULL REFERENCES sections(id) ON DELETE CASCADE,
    academic_period_id BIGINT NOT NULL REFERENCES academic_periods(id) ON DELETE CASCADE,
    start_date DATE NULL,
    end_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','PUBLISHED','CLOSED')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (saber_id, section_id, academic_period_id)
);

CREATE TABLE IF NOT EXISTS assignment_teachers (
    id BIGSERIAL PRIMARY KEY,
    saber_assignment_id BIGINT NOT NULL REFERENCES saber_assignments(id) ON DELETE CASCADE,
    teacher_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (saber_assignment_id, teacher_id)
);

CREATE TABLE IF NOT EXISTS assessments (
    id BIGSERIAL PRIMARY KEY,
    unit_id BIGINT NULL REFERENCES units(id) ON DELETE CASCADE,
    saber_assignment_id BIGINT NULL REFERENCES saber_assignments(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    max_score NUMERIC(10,2) NOT NULL DEFAULT 10,
    passing_score NUMERIC(10,2) NOT NULL DEFAULT 6,
    max_attempts INTEGER NULL,
    time_limit INTEGER NULL,
    available_from TIMESTAMP NULL,
    available_until TIMESTAMP NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','PUBLISHED','CLOSED')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS questions (
    id BIGSERIAL PRIMARY KEY,
    assessment_id BIGINT NOT NULL REFERENCES assessments(id) ON DELETE CASCADE,
    type VARCHAR(30) NOT NULL CHECK (type IN ('MULTIPLE_CHOICE','TRUE_FALSE','SINGLE_ANSWER')),
    question_text TEXT NOT NULL,
    points NUMERIC(10,2) NOT NULL DEFAULT 1,
    position INTEGER NOT NULL DEFAULT 0,
    settings JSONB NULL
);

CREATE TABLE IF NOT EXISTS question_options (
    id BIGSERIAL PRIMARY KEY,
    question_id BIGINT NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    position INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS assessment_attempts (
    id BIGSERIAL PRIMARY KEY,
    assessment_id BIGINT NOT NULL REFERENCES assessments(id) ON DELETE CASCADE,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    attempt_number INTEGER NOT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    score NUMERIC(10,2) NULL,
    percentage NUMERIC(5,2) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'IN_PROGRESS' CHECK (status IN ('IN_PROGRESS','COMPLETED','CANCELLED','EXPIRED')),
    UNIQUE (assessment_id, student_id, attempt_number)
);

CREATE TABLE IF NOT EXISTS attempt_answers (
    id BIGSERIAL PRIMARY KEY,
    attempt_id BIGINT NOT NULL REFERENCES assessment_attempts(id) ON DELETE CASCADE,
    question_id BIGINT NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    answer_value JSONB NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    score NUMERIC(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (attempt_id, question_id)
);

CREATE TABLE IF NOT EXISTS grade_components (
    id BIGSERIAL PRIMARY KEY,
    saber_assignment_id BIGINT NOT NULL REFERENCES saber_assignments(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    source_type VARCHAR(20) NOT NULL CHECK (source_type IN ('ACTIVITIES','ASSESSMENTS','PROJECT','H5P')),
    weight NUMERIC(5,2) NOT NULL DEFAULT 0,
    position INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS grades (
    id BIGSERIAL PRIMARY KEY,
    saber_assignment_id BIGINT NOT NULL REFERENCES saber_assignments(id) ON DELETE CASCADE,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    source_type VARCHAR(20) NOT NULL CHECK (source_type IN ('ACTIVITY','ASSESSMENT','MANUAL','COMPOSITE','H5P')),
    source_id BIGINT NULL,
    score NUMERIC(10,2) NOT NULL,
    percentage NUMERIC(5,2) NOT NULL DEFAULT 0,
    grade_value VARCHAR(50) NOT NULL,
    comments TEXT NULL,
    graded_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    graded_at TIMESTAMP NOT NULL,
    UNIQUE (saber_assignment_id, student_id, source_type, source_id)
);

CREATE TABLE IF NOT EXISTS student_saber_progress (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    saber_assignment_id BIGINT NOT NULL REFERENCES saber_assignments(id) ON DELETE CASCADE,
    percentage NUMERIC(5,2) NOT NULL DEFAULT 0,
    completed_units INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'NOT_STARTED' CHECK (status IN ('NOT_STARTED','IN_PROGRESS','COMPLETED')),
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, saber_assignment_id)
);

CREATE TABLE IF NOT EXISTS student_unit_progress (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    unit_id BIGINT NOT NULL REFERENCES units(id) ON DELETE CASCADE,
    percentage NUMERIC(5,2) NOT NULL DEFAULT 0,
    completed_blocks INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'NOT_STARTED' CHECK (status IN ('NOT_STARTED','IN_PROGRESS','COMPLETED')),
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, unit_id)
);

CREATE TABLE IF NOT EXISTS student_block_progress (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    block_id BIGINT NOT NULL REFERENCES content_blocks(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'NOT_STARTED' CHECK (status IN ('NOT_STARTED','IN_PROGRESS','COMPLETED')),
    completed_at TIMESTAMP NULL,
    viewed_at TIMESTAMP NULL,
    video_percent NUMERIC(5,2) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, block_id)
);

CREATE TABLE IF NOT EXISTS content_view_logs (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    block_id BIGINT NOT NULL REFERENCES content_blocks(id) ON DELETE CASCADE,
    action VARCHAR(30) NOT NULL CHECK (action IN ('VIEWED','COMPLETED','VIDEO_PROGRESS')),
    meta JSONB NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_view_student_block ON content_view_logs (student_id, block_id);

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT NOT NULL,
    old_data JSONB NULL,
    new_data JSONB NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Sesiones en base de datos (necesarias en entornos con disco efímero, p. ej. Render).
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    payload TEXT NOT NULL,
    last_activity BIGINT NOT NULL
);

-- Calificaciones H5P: amplía los CHECK existentes (idempotente) para bases ya creadas.
ALTER TABLE grades DROP CONSTRAINT IF EXISTS grades_source_type_check;
ALTER TABLE grades ADD CONSTRAINT grades_source_type_check CHECK (source_type IN ('ACTIVITY','ASSESSMENT','MANUAL','COMPOSITE','H5P'));
ALTER TABLE grade_components DROP CONSTRAINT IF EXISTS grade_components_source_type_check;
ALTER TABLE grade_components ADD CONSTRAINT grade_components_source_type_check CHECK (source_type IN ('ACTIVITIES','ASSESSMENTS','PROJECT','H5P'));
