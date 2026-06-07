<?php
/**
 * Catálogo de cursos. En un despliegue mayor esto vendría de la BD o del API
 * CORE (/api/courses); aquí vive como contenido versionable y editable.
 */

return [
    'lideres-encargados-2026' => [
        'id'         => 'lideres-encargados-2026',
        'titulo'     => 'Programa de Formación Líderes JOSEPAN 360: Encargados de Tienda',
        'obligatorio'=> true,
        'categoria'  => 'Liderazgo y Gestión',
        'resumen'    => 'Itinerario formativo para Encargados de Tienda: liderazgo, gestión humana, blindaje financiero y excelencia operativa bajo el SGI de JOSEPAN 360.',
        'horas'      => 12,
        'sesiones'   => [
            [
                'id' => 1,
                'titulo' => 'Mi Tienda, Mi Empresa',
                'subtitulo' => 'Empoderamiento, Autoridad y Estructura Organizacional',
                'video' => '',
                'guia' => <<<TXT
## Propósito de la sesión
El Encargado de Tienda representa a JOSEPAN 360 ante el equipo y el cliente. Esta sesión instala el marco mental de "Mi Tienda, Mi Empresa": gestionar el punto de venta con la responsabilidad de un propietario, dentro de las políticas corporativas.

## 1. La estructura organizacional
- JOSEPAN 360 opera con un CEDI/Fábrica central que abastece a la red de tiendas.
- Cadena de mando: Dirección → Coordinación de Operaciones → Encargado de Tienda → Equipo.
- El Encargado es el nexo entre las decisiones corporativas y la ejecución diaria.

## 2. Autoridad y empoderamiento
- Autoridad delegada para decisiones operativas: turnos, asignación de tareas, incidencias de primer nivel.
- Empoderamiento con responsabilidad: cada decisión se justifica con un procedimiento, un dato o una política.
- Escalamiento: qué se resuelve en tienda y qué se eleva a Coordinación.

## 3. Liderazgo de proximidad
- Presencia en sala: se dirige desde el ejemplo.
- Comunicación clara de expectativas al inicio del turno.
- Reconocimiento del buen desempeño y corrección oportuna.

## Actividad de cierre
Completa el organigrama de tu tienda y define, para tres situaciones cotidianas, si las resuelves o las escalas.
TXT,
                'materiales' => ['MN-RRHH-001', 'FR-SGI-042'],
            ],
            [
                'id' => 2,
                'titulo' => 'Gestión Humana y Aplicación Rigurosa del Reglamento Interno',
                'subtitulo' => 'Equipos, normativa y disciplina justa',
                'video' => '',
                'guia' => <<<TXT
## Propósito de la sesión
Dotar al Encargado de criterios para gestionar al equipo de forma justa, consistente y conforme al Reglamento Interno de Trabajo (V003).

## 1. El Reglamento Interno como marco
- Lectura aplicada del Reglamento (V003): derechos, deberes, jornada, descansos.
- Igualdad de trato: las mismas reglas para todo el equipo.

## 2. Ciclo del colaborador
- Acogida e inducción del personal nuevo.
- Asignación de funciones según el Manual de cada puesto.
- Evaluación con criterios observables, no impresiones.

## 3. Aplicación rigurosa y proporcional
- Régimen disciplinario: faltas leves, graves y muy graves.
- Procedimiento ante incidencia: documentar en el Formato de Incidencias (FR-RRHH-025) antes de actuar.
- Proporcionalidad: la medida se ajusta a la gravedad y a los antecedentes.

## 4. Conversaciones difíciles
- Estructura: hechos, impacto, expectativa, acuerdo, seguimiento.
- Separar la persona del comportamiento; corregir sin humillar.

## Actividad de cierre
Redacta un Formato de Incidencias (FR-RRHH-025) sobre un caso simulado aplicando la proporcionalidad.
TXT,
                'materiales' => ['MN-RRHH-001', 'MN-RRHH-003', 'RIT-V003', 'FR-RRHH-025'],
            ],
            [
                'id' => 3,
                'titulo' => 'Control Financiero y Blindaje Antifraude en Terminales de Cobro',
                'subtitulo' => 'Caja, arqueos y prevención de pérdidas',
                'video' => '',
                'guia' => <<<TXT
## Propósito de la sesión
El control del efectivo y de las transacciones en el TPV es responsabilidad directa del Encargado. Aquí se fijan los controles que blindan la caja frente a errores y fraude.

## 1. El ciclo de caja
- Apertura: fondo de caja contado y registrado.
- Operación: toda venta pasa por el TPV; nada "fuera de sistema".
- Cierre: arqueo y conciliación contra el informe del TPV; diferencias en el Informe de Caja (FR-FIN-012).

## 2. Señales de alerta
- Anulaciones y devoluciones por encima de lo normal o concentradas en un operador.
- Descuadres recurrentes en un turno o terminal.
- Operaciones "sin cliente": no-ventas, cajón abierto sin transacción.
- Ventas no registradas: cobrar y no tiquetear.

## 3. Controles antifraude
- Segregación de funciones cuando el equipo lo permita.
- Arqueos sorpresa además del cierre diario.
- Trazabilidad: cada operador con su usuario; sin claves compartidas.
- Conciliación diaria de efectivo y medios electrónicos contra el TPV.

## 4. Actuación ante un descuadre
- No acusar: investigar y reconstruir el turno con el detalle del TPV.
- Documentar siempre en FR-FIN-012 y escalar según el monto.

## Actividad de cierre
Realiza un arqueo simulado, detecta el descuadre y documenta el Informe de Caja (FR-FIN-012).
TXT,
                'materiales' => ['MN-RRHH-003', 'FR-FIN-012', 'FR-SGI-042'],
            ],
            [
                'id' => 4,
                'titulo' => 'Excelencia Operativa, Gestión de Stock y Calidad SGI',
                'subtitulo' => 'Producto, inventario e ISO 9001/22000/14001',
                'video' => '',
                'guia' => <<<TXT
## Propósito de la sesión
Integrar la operación diaria con el Sistema de Gestión Integrado (SGI) de JOSEPAN 360: calidad, seguridad alimentaria y control de stock.

## 1. Gestión de stock
- Recepción del CEDI: verificación de cantidades, lote y caducidad.
- Movimientos atómicos: cada entrada, venta, merma o traslado se registra; nunca se "ajusta a ojo".
- Rotación FIFO/FEFO, crítica en panadería y frescos.
- Control de mermas: registrar la merma es tan importante como registrar la venta.

## 2. Calidad y seguridad alimentaria (SGI)
- ISO 9001 (calidad), 22000 (seguridad alimentaria), 14001 (medio ambiente) en el día a día.
- Control de temperaturas, higiene y trazabilidad.
- Autoauditoría con el Checklist (FR-SGI-042) como herramienta de mejora.

## 3. Excelencia operativa
- Estándares de exhibición y reposición.
- Disponibilidad de productos estrella sin sobre-stock.
- Cierre operativo: limpieza, equipos, seguridad.

## Actividad de cierre
Ejecuta una autoauditoría con el FR-SGI-042, identifica dos no conformidades y define un plan de acción.
TXT,
                'materiales' => ['MN-RRHH-001', 'FR-SGI-042'],
            ],
        ],
    ],
];
