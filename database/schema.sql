-- Esquema inicial — Aula Virtual Académica (MySQL / MariaDB)
-- Nota: el SDD especifica PostgreSQL como objetivo de producción; este esquema
-- está adaptado a MySQL/MariaDB para el desarrollo local (decisión del usuario).

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    status ENUM('ACTIVE','INACTIVE','PENDING') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migración idempotente: permite contraseña nula (registro pendiente) y el estado PENDING.
ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL;
ALTER TABLE users MODIFY COLUMN status ENUM('ACTIVE','INACTIVE','PENDING') NOT NULL DEFAULT 'PENDING';

CREATE TABLE IF NOT EXISTS user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_roles (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academic_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('DRAFT','ACTIVE','CLOSED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tracks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    position INT NOT NULL DEFAULT 0,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academic_period_tracks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_period_id INT UNSIGNED NOT NULL,
    track_id INT UNSIGNED NOT NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_period_track (academic_period_id, track_id),
    CONSTRAINT fk_apt_period FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id) ON DELETE CASCADE,
    CONSTRAINT fk_apt_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_period_id INT UNSIGNED NOT NULL,
    track_id INT UNSIGNED NOT NULL,
    number INT NOT NULL,
    name VARCHAR(150) NULL,
    description TEXT NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_section (academic_period_id, track_id, number),
    CONSTRAINT fk_sec_period FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id) ON DELETE CASCADE,
    CONSTRAINT fk_sec_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS section_students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    enrollment_date DATE NOT NULL,
    withdrawal_date DATE NULL,
    status ENUM('ENROLLED','WITHDRAWN') NOT NULL DEFAULT 'ENROLLED',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_section_student (section_id, student_id),
    CONSTRAINT fk_ss_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_ss_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sabers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    track_id INT UNSIGNED NOT NULL,
    unlock_mode ENUM('FREE','SEQUENTIAL') NOT NULL DEFAULT 'FREE',
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_saber_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saber_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    position INT NOT NULL DEFAULT 0,
    status ENUM('DRAFT','PUBLISHED','UNPUBLISHED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit_saber FOREIGN KEY (saber_id) REFERENCES sabers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    uploader_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_file_uploader FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_id INT UNSIGNED NOT NULL,
    type ENUM('TEXT','IMAGE','VIDEO','PDF','LINK','EMBED','ACTIVITY','ASSESSMENT','H5P') NOT NULL,
    position INT NOT NULL DEFAULT 0,
    title VARCHAR(200) NULL,
    content TEXT NULL,
    settings JSON NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_block_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migración idempotente: amplía el ENUM de content_blocks.type para incluir H5P.
ALTER TABLE content_blocks MODIFY COLUMN type ENUM('TEXT','IMAGE','VIDEO','PDF','LINK','EMBED','ACTIVITY','ASSESSMENT','H5P') NOT NULL;

CREATE TABLE IF NOT EXISTS saber_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saber_id INT UNSIGNED NOT NULL,
    section_id INT UNSIGNED NOT NULL,
    academic_period_id INT UNSIGNED NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('DRAFT','PUBLISHED','CLOSED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assignment (saber_id, section_id, academic_period_id),
    CONSTRAINT fk_sa_saber FOREIGN KEY (saber_id) REFERENCES sabers(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_period FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assignment_teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saber_assignment_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assign_teacher (saber_assignment_id, teacher_id),
    CONSTRAINT fk_at_assignment FOREIGN KEY (saber_assignment_id) REFERENCES saber_assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_at_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_id INT UNSIGNED NULL,
    saber_assignment_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    max_score DECIMAL(10,2) NOT NULL DEFAULT 10.00,
    passing_score DECIMAL(10,2) NOT NULL DEFAULT 6.00,
    max_attempts INT UNSIGNED NULL,
    time_limit INT UNSIGNED NULL,
    available_from DATETIME NULL,
    available_until DATETIME NULL,
    status ENUM('DRAFT','PUBLISHED','CLOSED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assess_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    CONSTRAINT fk_assess_assignment FOREIGN KEY (saber_assignment_id) REFERENCES saber_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    type ENUM('MULTIPLE_CHOICE','TRUE_FALSE','SINGLE_ANSWER') NOT NULL,
    question_text TEXT NOT NULL,
    points DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    position INT NOT NULL DEFAULT 0,
    settings JSON NULL,
    CONSTRAINT fk_q_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS question_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    position INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_qo_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    attempt_number INT NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    score DECIMAL(10,2) NULL,
    percentage DECIMAL(5,2) NULL,
    status ENUM('IN_PROGRESS','COMPLETED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'IN_PROGRESS',
    UNIQUE KEY uq_attempt (assessment_id, student_id, attempt_number),
    CONSTRAINT fk_aa_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    CONSTRAINT fk_aa_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attempt_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer_value JSON NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    score DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attempt_answer (attempt_id, question_id),
    CONSTRAINT fk_ana_attempt FOREIGN KEY (attempt_id) REFERENCES assessment_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ana_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grade_components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saber_assignment_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    source_type ENUM('ACTIVITIES','ASSESSMENTS','PROJECT','H5P') NOT NULL,
    weight DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    position INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_gc_assignment FOREIGN KEY (saber_assignment_id) REFERENCES saber_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saber_assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    source_type ENUM('ACTIVITY','ASSESSMENT','MANUAL','COMPOSITE','H5P') NOT NULL,
    source_id INT UNSIGNED NULL,
    score DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    grade_value VARCHAR(50) NOT NULL,
    comments TEXT NULL,
    graded_by INT UNSIGNED NULL,
    graded_at DATETIME NOT NULL,
    UNIQUE KEY uq_grade (saber_assignment_id, student_id, source_type, source_id),
    CONSTRAINT fk_g_assignment FOREIGN KEY (saber_assignment_id) REFERENCES saber_assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_g_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_g_graded_by FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_saber_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    saber_assignment_id INT UNSIGNED NOT NULL,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    completed_units INT NOT NULL DEFAULT 0,
    status ENUM('NOT_STARTED','IN_PROGRESS','COMPLETED') NOT NULL DEFAULT 'NOT_STARTED',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ssp (student_id, saber_assignment_id),
    CONSTRAINT fk_ssp_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ssp_assignment FOREIGN KEY (saber_assignment_id) REFERENCES saber_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_unit_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    completed_blocks INT NOT NULL DEFAULT 0,
    status ENUM('NOT_STARTED','IN_PROGRESS','COMPLETED') NOT NULL DEFAULT 'NOT_STARTED',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sup (student_id, unit_id),
    CONSTRAINT fk_sup_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sup_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_block_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    block_id INT UNSIGNED NOT NULL,
    status ENUM('NOT_STARTED','IN_PROGRESS','COMPLETED') NOT NULL DEFAULT 'NOT_STARTED',
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sbp (student_id, block_id),
    CONSTRAINT fk_sbp_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sbp_block FOREIGN KEY (block_id) REFERENCES content_blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seguimiento de visualización de contenido (validación de que el estudiante vio el material).
-- Columnas idempotentes (MariaDB admite ADD COLUMN IF NOT EXISTS).
ALTER TABLE student_block_progress ADD COLUMN IF NOT EXISTS viewed_at DATETIME NULL;
ALTER TABLE student_block_progress ADD COLUMN IF NOT EXISTS video_percent DECIMAL(5,2) NULL;

CREATE TABLE IF NOT EXISTS content_view_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    block_id INT UNSIGNED NOT NULL,
    action ENUM('VIEWED','COMPLETED','VIDEO_PROGRESS') NOT NULL,
    meta JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_view_student_block (student_id, block_id),
    CONSTRAINT fk_cvl_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cvl_block FOREIGN KEY (block_id) REFERENCES content_blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sesiones en base de datos (necesarias en entornos con disco efímero, p. ej. Render).
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    payload MEDIUMTEXT NOT NULL,
    last_activity BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Calificaciones H5P: amplía los ENUM existentes (idempotente) para bases ya creadas.
ALTER TABLE grade_components MODIFY COLUMN source_type ENUM('ACTIVITIES','ASSESSMENTS','PROJECT','H5P') NOT NULL;
ALTER TABLE grades MODIFY COLUMN source_type ENUM('ACTIVITY','ASSESSMENT','MANUAL','COMPOSITE','H5P') NOT NULL;

-- Evaluaciones H5P: pesos por contenido evaluable concreto.
ALTER TABLE grade_components ADD COLUMN IF NOT EXISTS block_id INT UNSIGNED NULL;
ALTER TABLE grade_components ADD CONSTRAINT fk_gc_block FOREIGN KEY (block_id) REFERENCES content_blocks(id) ON DELETE CASCADE;

-- Entregas de evaluaciones H5P (un intento por estudiante y contenido).
CREATE TABLE IF NOT EXISTS h5p_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    block_id INT UNSIGNED NOT NULL,
    score DECIMAL(10,2) NULL,
    max_score DECIMAL(10,2) NULL,
    percentage DECIMAL(5,2) NULL,
    auto_graded TINYINT(1) NOT NULL DEFAULT 0,
    answer_text TEXT NULL,
    status ENUM('PENDING','RELEASED') NOT NULL DEFAULT 'PENDING',
    graded_by INT UNSIGNED NULL,
    graded_at DATETIME NULL,
    released_at DATETIME NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_h5p_sub (student_id, block_id),
    CONSTRAINT fk_h5p_sub_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_h5p_sub_block FOREIGN KEY (block_id) REFERENCES content_blocks(id) ON DELETE CASCADE,
    CONSTRAINT fk_h5p_sub_graded_by FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estado de contenido H5P (captura de respuestas de ensayos).
CREATE TABLE IF NOT EXISTS h5p_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    block_id INT UNSIGNED NOT NULL,
    state JSON NOT NULL,
    answer_text TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_h5p_state (student_id, block_id),
    CONSTRAINT fk_h5p_state_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_h5p_state_block FOREIGN KEY (block_id) REFERENCES content_blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
