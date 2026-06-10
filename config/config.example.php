<?php
/**
 * Configuración generada por el instalador · Academia JOSEPAN 360.
 * Generado el 2026-06-10 12:10:56. Edítalo si cambian los parámetros.
 */

// --- Base de datos ---
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'josepan_academy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- OMNI API CORE [1001] ---
define('API_CORE_BASE', 'https://api.omni.josepan.app');
define('API_PREFIX', '/api/v1');
define('API_TIMEOUT', 10);

// --- Sesión ---
define('SESSION_NAME', 'JP360ACAD');
define('SESSION_SECURE', false);
define('SESSION_IDLE_MIN', 60);

// --- Roles con privilegios ---
$GLOBALS['ADMIN_ROLES'] = ['Director', 'RRHH', 'Recursos Humanos', 'Administrador', 'Coordinad'];
$GLOBALS['TECH_ROLES']  = ['Administrador', 'Tecnico', 'Técnico', 'Sistemas', 'Director'];
$GLOBALS['ADMIN_EMAILS'] = [];
$GLOBALS['ADMIN_PERMS']  = ['academia.admin', '*'];

// --- Gamificación ---
define('PUNTOS_POR_SESION', 25);
define('PUNTOS_APROBACION', 50);

// --- Rutas ---
define('DOWNLOADS_DIR', __DIR__ . '/../downloads');

// --- Modo desarrollo ---
define('DEV_MODE', true);
define('DEV_USER', 'admin');
define('DEV_PASS', 'admin');
