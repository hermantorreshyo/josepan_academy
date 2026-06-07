# CLAUDE.md — Academia JOSEPAN 360

Guía de contexto para trabajar en este repositorio. Léela antes de modificar código.

## Qué es

Plataforma web interna de capacitaciones (e-learning) para JOSEPAN 360. **LAMP puro**: PHP 8 nativo, MySQL/MariaDB con PDO, HTML5 + CSS3 + JavaScript Vanilla. La autenticación es **centralizada en el OMNI API CORE [1001]**; la base de datos local solo guarda la lógica de la academia (progreso, asistencia, telemetría, puntos, certificación), referenciando al empleado por su **ID de OMNI**.

## Restricciones duras (no negociables)

- **Sin frameworks** (nada de Laravel, Symfony, Slim, etc.).
- **Sin Composer / sin dependencias de terceros.** El PDF se genera con `lib/PdfBuilder.php` (nativo); no añadir FPDF/TCPDF ni paquetes.
- **Sin Docker.**
- **JavaScript Vanilla** (sin React/Vue/jQuery).
- PHP 8.0+ nativo. SQL MySQL/MariaDB, motor InnoDB, `utf8mb4`.

## Arquitectura y reglas de oro

1. **DocumentRoot = `public/`.** Solo `public/` se expone por web. `config/`, `includes/`, `sdk_omni/`, `templates/`, `data/`, `lib/`, `downloads/` quedan **fuera del webroot** y llevan `.htaccess` de denegación. No mover páginas de cara al usuario fuera de `public/`.

2. **URLs siempre relativas.** El proyecto puede vivir en la raíz del dominio o en una subcarpeta. Nunca uses rutas absolutas con `/` inicial.
   - En HTML/CSS/JS: escribe rutas **sin barra inicial** (`assets/css/app.css`, `curso.php?id=...`, `admin/index.php`, `api/track_open.php`). Cada cabecera emite `<base href="<?= e(APP_BASE) ?>">`, que las resuelve.
   - En redirecciones de servidor (`header('Location: ...')`): usa **siempre** el helper `url('ruta')` (definido en `includes/functions.php`), nunca una ruta `/absoluta`.
   - La base se calcula en `includes/base.php` (`app_base_auto()`) y se publica como constante `APP_BASE` en `bootstrap.php`. Páginas autónomas (manuales, instalador) incluyen `includes/base.php` directamente.

3. **Autenticación solo vía OMNI.** El login **no** consulta la BD local. Instancia `OmniCoreClient` (`sdk_omni/`) y llama a `login()`, que hace `POST {API_CORE_BASE}{API_PREFIX}/auth/login`. El token, perfil y `permissions[]` se guardan en la sesión PHP con `establish_session($token, $user, $permissions)`. No introducir tablas de usuarios/contraseñas locales.

## Mapa de archivos

```
config/        config.php (lo genera el instalador) · database.php (PDO singleton) · config.example.php
sdk_omni/      OmniCoreClient.php   — SDK de autenticación OMNI [1001]
includes/      base.php · bootstrap.php · auth.php · functions.php · gamification.php
               models/  Empleado · Curso · Parametros · Progreso · Telemetria · Asistencia · Certificado
lib/           PdfBuilder.php       — generador PDF nativo
data/          cursos.php (contenido fuente del seeder) · documentos.php (índice biblioteca) · seeders.php
templates/     header.php · footer.php · manual_header.php · manual_footer.php
downloads/     PDFs reales (se sirven solo vía public/descargar.php)
schema.sql     Esquema de la BD local
manual-tecnico.html   Manual técnico estático (raíz, autocontenido)
public/        DocumentRoot
  install/index.php   Autoinstaller (BD + seeders + config + candado)
  index.php login.php logout.php curso.php perfil.php biblioteca.php descargar.php certificado.php
  manual-tecnico.html (copia web) · manual-usuario.php
  assets/css/app.css · assets/js/telemetria.js · assets/js/app.js
  api/   track_open.php · track_ping.php · complete_session.php
  admin/ index.php (reportes) · asistencia.php · aprobacion.php
```

## Arranque de una página típica

```php
require_once __DIR__ . '/../includes/bootstrap.php';   // config + sesión + modelos + APP_BASE + guard de instalación
require_once __DIR__ . '/../includes/auth.php';
require_login();          // o require_admin() / require_login_api()
// ... lógica ...
$pageTitle = '...'; $pageActive = '...';
require __DIR__ . '/../templates/header.php';
// ... HTML con rutas relativas ...
require __DIR__ . '/../templates/footer.php';
```

Las páginas en `public/admin/` y `public/api/` suben dos niveles (`../../includes/...`). Manuales e instalador son **autónomos** (no incluyen `bootstrap.php`) para ser visibles aunque el sistema no esté instalado.

## Base de datos

- **Maestras (pobladas por el seeder):** `cursos`, `cursos_sesiones` (UNIQUE `curso_id, sesion_num`), `niveles`, `parametros`.
- **Transaccionales:** `empleados` (PK = ID de OMNI, no autoincremental), `usuarios_progreso`, `asistencias`, `telemetria_tiempos`, `cursos_puntuacion`. FK a `empleados` y a `cursos` con `ON DELETE CASCADE`.
- El **contenido de cursos** vive en BD en runtime (modelo `Curso`), pero `data/cursos.php` es la **fuente del seeder**: si cambias cursos/sesiones, edítalo ahí y reejecuta el seeder.
- `niveles` y `parametros` son la fuente en runtime vía `Gamification::niveles()` y `Parametros::getInt()`, con respaldo a las constantes si la tabla no existe.

## Convenciones de código

- **Toda** consulta a BD usa sentencias preparadas vía `Database::run($sql, $params)` o `Database::pdo()->prepare(...)`. Nunca interpoles variables en SQL.
- **Toda** salida a HTML pasa por `e()` (`htmlspecialchars`). En manuales autónomos se usa `mh()`/`he()`.
- **CSRF** obligatorio en formularios (`csrf_token()` / `csrf_check()`) y en endpoints AJAX (cabecera `X-CSRF-Token`).
- **Operaciones acumulativas atómicas:** la telemetría suma con `segundos_activos = segundos_activos + :seg` (con tope defensivo de 120 s/ping). Nunca hagas un `UPDATE` con valor absoluto recalculado en PHP para contadores concurrentes.
- Idempotencia en seeders y upserts con `INSERT ... ON DUPLICATE KEY UPDATE`.
- Respuestas de API con `json_response($data, $status)`.
- Roles de administración: por rol del perfil OMNI (`ADMIN_ROLES`) o permiso `academia.admin` (`ADMIN_PERMS`). Documentación técnica: `is_tech()`.
- Estética corporativa: fondo claro, púrpura JOSEPAN (`#642a72`), acento horno (`#d98a3a`), `IBM Plex Mono` para cifras. Mobile-first.

## Instalación

`public/install/` es el autoinstaller: verifica entorno → pide parámetros (BD local + URL OMNI + sesión/roles/dev) → crea la BD y carga `schema.sql` → ejecuta `jp_run_seeders()` → escribe `config/config.php` → crea `config/installed.lock` (candado). Mientras no exista el lock, `bootstrap.php` redirige todo a `install/`. Tras instalar, eliminar/renombrar `public/install/`.

## Manuales

- `manual-tecnico.html` (raíz) — estático, autocontenido, con guía de despliegue; consultable desde el sistema de archivos sin servidor. Hay copia en `public/manual-tecnico.html`.
- `public/manual-usuario.php` — manual de usuario (empleado/administrador), público.
- Ambos deben seguir siendo **visibles aunque el proyecto no esté instalado** (no incluir `bootstrap.php`).

## Verificación y despliegue

- En este entorno **no hay PHP**; valida sintaxis con el chequeo heurístico de balance de llaves/paréntesis sobre los `.php` y, **al desplegar, ejecuta `php -l`** sobre los archivos.
- Setup local: copia de archivos, `DocumentRoot` → `public/`, abrir `install/`. Alternativa: `mysql -u root -p <bd> < schema.sql` + configurar `config/config.php` desde `config.example.php`.

## Qué NO hacer

- No añadir librerías externas, frameworks ni Composer.
- No usar rutas absolutas (`/...`) en enlaces, assets, `fetch()` ni redirecciones.
- No autenticar contra la BD local ni almacenar contraseñas; siempre vía OMNI SDK.
- No exponer carpetas sensibles ni servir PDFs sin pasar por `descargar.php` (verifica sesión, whitelist y `basename()` anti-traversal).
- No romper la independencia de los manuales respecto a la instalación.
