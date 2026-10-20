# Guía de despliegue (servicios de capa gratuita)

Esta aplicación fue preparada para funcionar con **dos motores de base de datos**:

- **MySQL/MariaDB** → desarrollo local (XAMPP).
- **PostgreSQL** → producción con **Neon** (serverless Postgres).

## Arquitectura

```
Navegador ──► Hosting PHP (gratis) ──► Neon (PostgreSQL)
                     │
                     ├──► Resend (correos)
                     └──► storage/uploads/ y storage/h5p/ (archivos locales)
```

## Servicios gratuitos recomendados

| Componente | Opciones | Notas |
|------------|----------|-------|
| **Base de datos** | [Neon](https://neon.tech) (PostgreSQL) | Capa gratuita con connection string SSL |
| **Hosting PHP** | Railway, Render (web service), Alwaysdata, Fly.io | Debe soportar PHP 8.1+ y las extensiones `pdo_pgsql`, `phar` |
| **Correo** | [Resend](https://resend.com) | Ya integrado (`RESEND_API_KEY`) |
| **Dominio** | Subdominio del hosting (gratis) o dominio propio | Configúralo en `APP_URL` |

> ⚠️ **Almacenamiento de archivos**: los PDF y paquetes H5P se guardan en disco
> (`storage/uploads/`, `storage/h5p/`). En capas gratuitas el disco suele ser
> **efímero** (se borra en cada redeploy) o de solo lectura. Para producción real
> conviene migrar a almacenamiento de objetos (Cloudflare R2, Supabase Storage, S3).
> Sin esto, los archivos subidos pueden perderse.

## Paso a paso

### 1. Crear la base de datos en Neon

1. Crea una cuenta en [neon.tech](https://neon.tech) y un **proyecto**.
2. En el panel copia la **connection string** (tiene esta forma):

   ```
   postgresql://neondb_owner:CONTRASEÑA@ep-xxx-xxxxx.region.aws.neon.tech/neondb?sslmode=require
   ```

### 2. Configurar `.env` (en el servidor)

Desglosa la connection string en las variables:

```env
APP_NAME="Aula Virtual Académica"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-app.example.com     # ← dominio real (obligatorio)
APP_TIMEZONE=UTC

DB_DRIVER=pgsql
DB_HOST=ep-xxx-xxxxx.region.aws.neon.tech
DB_PORT=5432
DB_NAME=neondb
DB_USER=neondb_owner
DB_PASS=CONTRASEÑA
DB_SSLMODE=require

SESSION_NAME=aula_virtual_session

RESEND_API_KEY=re_xxxx
RESEND_FROM="Aula Virtual Académica <no-reply@tu-dominio-verificado.com>"
```

> **Importante**: `APP_URL` debe ser el dominio real desde donde se sirve la app,
> o los estilos, imágenes y contenido H5P no cargarán (CORS).

### 3. Ejecutar migración y sembrado

En el servidor (o apuntando `.env` a Neon desde tu máquina):

```bash
composer dump-autoload -o
php database/migrate.php   # aplica schema.pgsql.sql
php database/seed.php      # crea roles + admin
```

### 4. Subir el código

- Sube todo el proyecto **excepto** `.env` (ya está en `.gitignore`).
- Apunta el **DocumentRoot / carpeta pública** del hosting a `public/`.
- Verifica que el hosting tenga `pdo_pgsql` y `phar` habilitados.

### 5. Verificar

1. Abre `https://tu-app.example.com/login`.
2. Inicia sesión con `admin@aula.local` / `admin123` y **cambia la contraseña**.
3. Crea un período, trayecto, sección y una evaluación con fecha → comprueba que
   el estudiante la ve en "Mis Actividades".

## Variables de entorno (resumen)

| Variable | Descripción |
|----------|-------------|
| `DB_DRIVER` | `mysql` o `pgsql` |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexión a la BD |
| `DB_SSLMODE` | Solo PostgreSQL (`require` para Neon) |
| `APP_URL` | Dominio real (sin barra final) |
| `APP_TIMEZONE` | Zona horaria de la app (UTC recomendado) |
| `RESEND_API_KEY`, `RESEND_FROM` | Envío de correos |

## Extensiones PHP requeridas

- `pdo` + `pdo_pgsql` (Neon) o `pdo_mysql` (local)
- `phar` (descompresión de paquetes H5P)
- `openssl` (HTTPS / correo)
