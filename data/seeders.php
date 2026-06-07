<?php
/**
 * Seeder de datos maestros. Lo ejecuta el autoinstaller tras crear el esquema.
 * Idempotente: usa INSERT ... ON DUPLICATE KEY UPDATE.
 */
function jp_run_seeders(PDO $pdo): array
{
    $root = dirname(__DIR__);
    require_once $root . '/includes/gamification.php';
    $cursos = require $root . '/data/cursos.php';

    $stats = ['cursos' => 0, 'sesiones' => 0, 'niveles' => 0, 'parametros' => 0];

    // Niveles de gamificación
    $stn = $pdo->prepare(
        "INSERT INTO niveles (nivel, nombre, min_puntos) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), min_puntos = VALUES(min_puntos)"
    );
    foreach (Gamification::NIVELES as $n) {
        $stn->execute([$n['nivel'], $n['nombre'], $n['min']]);
        $stats['niveles']++;
    }

    // Parametrización por defecto
    $stp = $pdo->prepare(
        "INSERT INTO parametros (clave, valor, descripcion) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor), descripcion = VALUES(descripcion)"
    );
    foreach ([
        ['puntos_por_sesion', '25', 'Puntos otorgados por cada sesión leída'],
        ['puntos_aprobacion', '50', 'Bonus al aprobar el módulo completo'],
    ] as $p) {
        $stp->execute($p);
        $stats['parametros']++;
    }

    // Cursos y sesiones
    $stc = $pdo->prepare(
        "INSERT INTO cursos (curso_id, titulo, resumen, categoria, horas, obligatorio, orden)
         VALUES (:id, :t, :r, :c, :h, :o, :ord)
         ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), resumen = VALUES(resumen),
            categoria = VALUES(categoria), horas = VALUES(horas),
            obligatorio = VALUES(obligatorio), orden = VALUES(orden)"
    );
    $sts = $pdo->prepare(
        "INSERT INTO cursos_sesiones (curso_id, sesion_num, titulo, subtitulo, video, guia, materiales)
         VALUES (:c, :n, :t, :s, :v, :g, :m)
         ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), subtitulo = VALUES(subtitulo),
            video = VALUES(video), guia = VALUES(guia), materiales = VALUES(materiales)"
    );
    $orden = 0;
    foreach ($cursos as $c) {
        $stc->execute([
            ':id' => $c['id'], ':t' => $c['titulo'], ':r' => $c['resumen'] ?? '',
            ':c' => $c['categoria'] ?? '', ':h' => (int)($c['horas'] ?? 0),
            ':o' => !empty($c['obligatorio']) ? 1 : 0, ':ord' => $orden++,
        ]);
        $stats['cursos']++;
        foreach ($c['sesiones'] as $s) {
            $sts->execute([
                ':c' => $c['id'], ':n' => (int)$s['id'], ':t' => $s['titulo'],
                ':s' => $s['subtitulo'] ?? '', ':v' => $s['video'] ?? '',
                ':g' => $s['guia'] ?? '',
                ':m' => json_encode($s['materiales'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
            $stats['sesiones']++;
        }
    }

    return $stats;
}
