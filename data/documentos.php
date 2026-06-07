<?php
/**
 * Índice de la biblioteca documental.
 * 'archivo' apunta al PDF real dentro de DOWNLOADS_DIR. La descarga se sirve
 * siempre vía public/descargar.php, que verifica la sesión antes de entregarlo.
 */

return [
    'MN-RRHH-001' => [
        'codigo' => 'MN-RRHH-001', 'categoria' => 'Manuales de Funciones',
        'titulo' => 'Manual de Funciones · Encargado de Tienda', 'cargo' => 'Encargado de Tienda',
        'version' => 'V002', 'archivo' => 'MN-RRHH-001_Encargado_de_Tienda.pdf',
    ],
    'MN-RRHH-003' => [
        'codigo' => 'MN-RRHH-003', 'categoria' => 'Manuales de Funciones',
        'titulo' => 'Manual de Funciones · Responsable de Caja', 'cargo' => 'Responsable de Caja',
        'version' => 'V002', 'archivo' => 'MN-RRHH-003_Responsable_de_Caja.pdf',
    ],
    'MN-RRHH-005' => [
        'codigo' => 'MN-RRHH-005', 'categoria' => 'Manuales de Funciones',
        'titulo' => 'Manual de Funciones · Auxiliar de Panadería', 'cargo' => 'Auxiliar de Panadería',
        'version' => 'V001', 'archivo' => 'MN-RRHH-005_Auxiliar_de_Panaderia.pdf',
    ],
    'RIT-V003' => [
        'codigo' => 'RIT-V003', 'categoria' => 'Reglamento Interno',
        'titulo' => 'Reglamento Interno de Trabajo', 'cargo' => null,
        'version' => 'V003', 'archivo' => 'Reglamento_Interno_de_Trabajo_V003.pdf',
    ],
    'FR-SGI-042' => [
        'codigo' => 'FR-SGI-042', 'categoria' => 'Material de Apoyo',
        'titulo' => 'Checklist de Autoauditoría', 'cargo' => null,
        'version' => 'V001', 'archivo' => 'FR-SGI-042_Checklist_Autoauditoria.pdf',
    ],
    'FR-RRHH-025' => [
        'codigo' => 'FR-RRHH-025', 'categoria' => 'Material de Apoyo',
        'titulo' => 'Formato de Incidencias', 'cargo' => null,
        'version' => 'V001', 'archivo' => 'FR-RRHH-025_Formato_Incidencias.pdf',
    ],
    'FR-FIN-012' => [
        'codigo' => 'FR-FIN-012', 'categoria' => 'Material de Apoyo',
        'titulo' => 'Informe de Caja', 'cargo' => null,
        'version' => 'V001', 'archivo' => 'FR-FIN-012_Informe_de_Caja.pdf',
    ],
];
