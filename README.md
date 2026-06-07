# Academia JOSEPAN 360 — Plataforma de capacitaciones (LAMP)

Plataforma web interna de e-learning para JOSEPAN 360. Stack **LAMP puro**: PHP 8 nativo (sin frameworks, sin Composer, sin Docker), MySQL/MariaDB con PDO, HTML5 + CSS3 + JavaScript Vanilla.

La **autenticación es centralizada en el OMNI API CORE [1001]**: el login instancia el SDK del cuaderno **[1000] JOSEPAN 360 OMNI** y consume `POST /api/v1/auth/login`. La base de datos local solo persiste la lógica de la academia (progreso, asistencia, telemetría, puntos, certificación), referenciando al empleado por su **ID de OMNI**.

## Requisitos

- PHP 8.0+ con `pdo_mysql`, `curl`, `mbstring`.
- MySQL 5.7+ / MariaDB 10.3+.
- Apache con `mod_rewrite` y `mod_headers`; HTTPS en producción.

## Estructura

```
josepan-academia/
├── config/        config.php (lo genera el instalador), database.php   (fuera del webroot)
├── sdk_omni/      OmniCoreClient.php — SDK PHP de autenticación OMNI [1001]  (fuera del webroot)
├── includes/      bootstrap, auth, helpers, gamification, models         (fuera del webroot)
├── templates/     header/footer de la app + layout de manuales           (fuera del webroot)
├── data/          cursos.php, documentos.php, seeders.php                (fuera del webroot)
├── lib/           PdfBuilder (generador PDF nativo)                       (fuera del webroot)
├── downloads/     PDFs reales servidos vía descargar.php                  (fuera del webroot)
├── schema.sql     Esquema de la base de datos local
└── public/        DocumentRoot de Apache
    ├── install/index.php   Autoinstaller (BD + seeders + config + candado)
    ├── manual-tecnico.php  manual-usuario.php   ← documentación PÚBLICA (siempre visible)
    ├── index.php login.php logout.php curso.php perfil.php biblioteca.php
    ├── descargar.php  certificado.php
    ├── assets/   css/app.css · js/telemetria.js · js/app.js
    ├── api/      track_open.php · track_ping.php · complete_session.php
    └── admin/    index.php (reportes) · asistencia.php · aprobacion.php
```

Solo `public/` se expone por web; el resto de carpetas quedan fuera del DocumentRoot y, además, llevan un `.htaccess` que niega el acceso.

## Instalación automática

1. Apunta el VirtualHost de Apache a `public/`:
   ```apache
   <VirtualHost *:443>
       ServerName academia.josepan360.com
       DocumentRoot /var/www/josepan-academia/public
       <Directory /var/www/josepan-academia/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
2. Abre `https://tu-dominio/` (o `/install/`). Mientras no esté instalado, todo redirige al **asistente**, que: verifica el entorno, **solicita los parámetros** (BD local, URL del OMNI API CORE, sesión, roles, modo desarrollo), **escribe `config/config.php`**, **crea la base de datos, carga `schema.sql` y ejecuta los seeders** (cursos, 4 sesiones, niveles y parámetros) automáticamente, y deja la plataforma operativa.
3. Sube los PDF de la biblioteca a `downloads/` con los nombres de `data/documentos.php`.
4. **Elimina o renombra el directorio `public/install/`** tras instalar. El asistente se autobloquea con `config/installed.lock`.

> Los manuales **técnico** y **de usuario** son páginas públicas (`/manual-tecnico.php`, `/manual-usuario.php`): están visibles **antes y después** de instalar, sin login y sin base de datos.

## Integración OMNI API CORE [1001]

`POST {API_CORE_BASE}{API_PREFIX}/auth/login` (por defecto `/api/v1`):

```json
// Petición
{ "usuario": "herman.torres", "password": "********" }

// Respuesta (OMNI envuelve en "data")
{ "data": {
    "token": "JWT-HS256...",
    "user": { "id": 123, "nombre": "...", "rol": "Encargado de Tienda", "tienda": "Vía 18", "email": "..." },
    "permissions": ["academia.admin", "inventory.read", "..."]
} }
```

El SDK (`sdk_omni/OmniCoreClient.php`) tolera alias de campos. El JWT, el perfil y los `permissions[]` se guardan en la sesión PHP (`HttpOnly`, `SameSite=Lax`, `Secure` si `SESSION_SECURE=true`). La administración se concede por rol del perfil OMNI (configurable) o por el permiso `academia.admin`.

### Modo desarrollo

Con `DEV_MODE = true`, si OMNI no responde se admite un acceso de prueba (`admin@josepan360.com` / `demo1234`, rol Director) para evaluar la plataforma. **Desactívalo en producción** (el instalador lo deja desactivado salvo que lo marques).

## Base de datos local

Tablas InnoDB `utf8mb4`. Maestras (pobladas por el seeder): `cursos`, `cursos_sesiones`, `niveles`, `parametros`. Transaccionales: `empleados` (cache del perfil; PK = ID de OMNI), `usuarios_progreso`, `asistencias`, `telemetria_tiempos`, `cursos_puntuacion`. Todas referencian a `empleados` con FK `ON DELETE CASCADE`. Ver `schema.sql`.

## Gamificación

- Cada sesión leída suma **25 pts**; aprobar el módulo añade **50 pts**.
- Niveles: Aprendiz (0) · Operativo (100) · Referente (250) · Líder (500) · Embajador JOSEPAN (1000).
- El **certificado** se habilita con el 100% de sesiones completadas **y** `estado_aprobacion = aprobado`.

## Seguridad

- PDO con sentencias preparadas; `htmlspecialchars()` en toda salida; CSRF en formularios y AJAX.
- `session_regenerate_id()` tras login y cierre por inactividad.
- Descargas con whitelist y `basename()` anti-traversal; PDFs fuera del webroot.
- El generador de PDF (`lib/PdfBuilder.php`) es nativo: no requiere FPDF/TCPDF ni Composer.

## URLs relativas (raíz o subcarpeta)

Todas las rutas internas son relativas y se resuelven contra una `<base href>` calculada dinámicamente desde `SCRIPT_NAME` (helper `includes/base.php` → `APP_BASE` y `url()`). El proyecto funciona sin cambios tanto en la raíz del dominio como en una subcarpeta (p. ej. `/academia/`).

## Manual técnico estático

`manual-tecnico.html` (en la raíz del proyecto) es un documento autocontenido —sin dependencias externas— con la guía completa de despliegue y configuración. Es consultable directamente desde el sistema de archivos, esté o no desplegado el proyecto, y también vía web (copia en `public/manual-tecnico.html`).
