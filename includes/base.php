<?php
/**
 * Cálculo de la base web de la aplicación (sin dependencias).
 * Permite que el proyecto funcione tanto en la raíz del dominio como en una
 * subcarpeta, sin URLs absolutas codificadas.
 *
 * Detecta la carpeta del DocumentRoot (public/) a partir de SCRIPT_NAME,
 * subiendo un nivel cuando el script vive en admin/, api/ o install/.
 */
if (!function_exists('app_base_auto')) {
    function app_base_auto(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
        $dir = dirname($script);
        $leaf = basename($dir);
        if (in_array($leaf, ['admin', 'api', 'install'], true)) {
            $dir = dirname($dir);
        }
        $dir = str_replace('\\', '/', $dir);
        if ($dir === '/' || $dir === '.' || $dir === '') {
            return '/';
        }
        return rtrim($dir, '/') . '/';
    }
}
