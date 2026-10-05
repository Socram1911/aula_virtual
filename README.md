# Aula Virtual Académica

Plataforma web institucional de aula virtual: organiza el proceso educativo desde la creación de un **Período Académico** hasta el seguimiento del **progreso** y las **calificaciones** de los estudiantes.

Arquitectura **MVC modular en PHP 8.x** (sin framework completo), **Bootstrap 5** + **JavaScript** (ES6+, sin React) en el frontend, y acceso a datos mediante **PDO** con sentencias preparadas.

> **Nota sobre la base de datos**: el documento de diseño (SDD) especifica PostgreSQL como objetivo de producción. Para el **desarrollo local** se usa **MySQL/MariaDB** (decisión del usuario, ya que el entorno XAMPP provee `pdo_mysql`). La capa de acceso a datos está aislada en `app/Core/Database/Connection.php`, por lo que migrar a PostgreSQL solo requiere adaptar el DSN y el dialecto SQL del esquema.

## Requisitos

- PHP 8.1+ con `pdo_mysql` (o `pdo_pgsql` para producción).
- MySQL/MariaDB (desarrollo) o PostgreSQL (producción).
- Composer (solo para el autoload PSR-4; no hay dependencias externas de red).

## Instalación

```bash
# 1. Autoload
composer dump-autoload -o

# 2. Configurar entorno
copy config\.env.example .env
#   Editar .env con credenciales de base de datos (DB_HOST, DB_NAME, DB_USER, DB_PASS)

# 3. Crear esquema y datos iniciales
php database/migrate.php
php database/seed.php
```

Credenciales iniciales:

- **Usuario**: `admin@aula.local`
- **Contraseña**: `admin123`

## Ejecución

- **Apache (XAMPP)**: apuntar el *DocumentRoot* al directorio `public/` (el archivo `.htaccess` reenvía todo a `public/index.php`). Ejemplo de virtual host:

  ```apache
  DocumentRoot "C:/xampp/htdocs/aula_virtual/public"
  ```

- **Servidor embebido de PHP** (para pruebas):

  ```bash
  php -S 127.0.0.1:8090 -t public public/index.php
  ```

  Abrir `http://127.0.0.1:8090/login`.

## Pruebas

```bash
php tests/run.php          # Ejecutor liviano (sin PHPUnit)
vendor/bin/phpunit          # Alternativa cuando PHPUnit esté instalado
```

## Estructura

```text
app/
├── Core/        # Router, Request, Response, Session, Database, Auth, Csrf, Exceptions
├── Modules/     # Auth, Users, AcademicPeriods, Tracks, Sections, Students, Sabers,
│                # Units, Content, Activities, Assessments, Grades, Progress, Files,
│                # Reports, Audit, Learning, Dashboard
└── Shared/      # Layout y helpers compartidos
public/          # Front controller + assets
storage/uploads/ # Archivos subidos (fuera de la base de datos)
database/        # Esquema, migración y sembrado
tests/           # Pruebas de contrato
specs/           # Documentación Spec Kit (spec, plan, tasks)
```

Cada módulo sigue la separación de responsabilidades: `Controllers` (coordinación), `Services` (reglas de negocio), `Repositories` (persistencia), `Models` (entidades), `Views` (presentación) y `Routes`.

## Seguridad

- Contraseñas con `password_hash` (Argon2id).
- Sesiones seguras (httponly, samesite) y regeneración en el login.
- Protección **CSRF** en todos los formularios y peticiones JSON (`X-CSRF-Token`).
- **Sentencias preparadas** con PDO (nunca se concatena entrada del usuario en SQL).
- **El servidor es la autoridad final** para puntajes, calificaciones, respuestas correctas y aprobación.
- Auditoría de acciones clave y restricciones de integridad (únicas, claves foráneas).
