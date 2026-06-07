<?php
/**
 * Configuración de la Academia JOSEPAN 360 (LAMP).
 * Cópialo como config.php o deja que lo genere el instalador (public/install/).
 * NUNCA subas config.php a control de versiones.
 */

// --- Base de datos LOCAL de la academia (PDO / MySQL · MariaDB) -------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'josepan_academia');
define('DB_USER', 'jp_academia');
define('DB_PASS', 'cambia_esta_clave');
define('DB_CHARSET', 'utf8mb4');

// --- OMNI API CORE [1001] (autenticación centralizada) ----------------------
define('API_CORE_BASE', 'https://omni.josepan.es'); // sin barra final
define('API_PREFIX', '/api/v1');                     // prefijo del cuaderno [1000]
define('API_TIMEOUT', 10);                           // segundos para cURL

// --- Seguridad de sesión ----------------------------------------------------
define('SESSION_NAME', 'JP360ACAD');
define('SESSION_SECURE', false); // true SOLO bajo HTTPS (producción)
define('SESSION_IDLE_MIN', 60);

// --- Roles con privilegios (perfil/permiso devuelto por OMNI) ---------------
$GLOBALS['ADMIN_ROLES']  = ['Director', 'RRHH', 'Recursos Humanos', 'Administrador', 'Coordinad'];
$GLOBALS['TECH_ROLES']   = ['Administrador', 'Tecnico', 'Técnico', 'Sistemas', 'Director'];
$GLOBALS['ADMIN_EMAILS'] = [];
// Permisos OMNI (resource.action) que conceden administración en la academia.
$GLOBALS['ADMIN_PERMS']  = ['academia.admin', '*'];

// --- Gamificación -----------------------------------------------------------
define('PUNTOS_POR_SESION', 25);
define('PUNTOS_APROBACION', 50);

// --- Rutas ------------------------------------------------------------------
define('DOWNLOADS_DIR', __DIR__ . '/../downloads');

// --- Modo desarrollo (acceso de prueba si OMNI no responde) -----------------
define('DEV_MODE', false);                 // DEJAR EN false EN PRODUCCIÓN
define('DEV_USER', 'admin@josepan360.com');
define('DEV_PASS', 'demo1234');
