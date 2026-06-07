<?php
/** Generación del Certificado de Finalización en PDF. */
class Certificado
{
    /**
     * Devuelve los bytes del PDF del certificado.
     * @param array $empleado ['nombre','rol','tienda']
     * @param array $curso    ['titulo']
     */
    public static function generar(array $empleado, array $curso, ?int $puntos = null): string
    {
        require_once dirname(__DIR__, 2) . '/lib/PdfBuilder.php';

        $pdf = new PdfBuilder(842, 595); // A4 horizontal
        $cx = $pdf->width() / 2;

        // Paleta JOSEPAN
        $purpura = [0.392, 0.165, 0.447]; // #642a72
        $horno   = [0.851, 0.541, 0.227]; // #d98a3a
        $tinta   = [0.110, 0.075, 0.125];

        // Marco doble
        $pdf->setStrokeColor(...$purpura);
        $pdf->rect(28, 28, 786, 539, 3);
        $pdf->setStrokeColor(...$horno);
        $pdf->rect(38, 38, 766, 519, 1);

        // Encabezado de marca
        $pdf->setFillColor(...$purpura);
        $pdf->textCenter($cx, 95, 'JOSEPAN 360', 26, true);
        $pdf->setFillColor(0.4, 0.4, 0.45);
        $pdf->textCenter($cx, 120, 'ACADEMIA INTERNA · FORMACIÓN CORPORATIVA', 11, false);

        // Título
        $pdf->setFillColor(...$tinta);
        $pdf->textCenter($cx, 195, 'CERTIFICADO DE FINALIZACIÓN', 30, true);

        // Cuerpo
        $pdf->setFillColor(0.3, 0.3, 0.35);
        $pdf->textCenter($cx, 250, 'Se certifica que', 14, false);

        $pdf->setFillColor(...$purpura);
        $pdf->textCenter($cx, 300, $empleado['nombre'] ?? 'Empleado', 32, true);

        // Línea bajo el nombre
        $pdf->setStrokeColor(...$horno);
        $pdf->rect($cx - 200, 312, 400, 0, 1.2);

        $pdf->setFillColor(0.3, 0.3, 0.35);
        $cargo = trim(($empleado['rol'] ?? '') . ' · ' . ($empleado['tienda'] ?? ''), ' ·');
        if ($cargo !== '') $pdf->textCenter($cx, 335, $cargo, 12, false);

        $pdf->textCenter($cx, 375, 'ha completado satisfactoriamente el programa formativo', 14, false);
        $pdf->setFillColor(...$tinta);
        $pdf->textCenter($cx, 405, $curso['titulo'] ?? 'Curso', 16, true);

        // Pie: fecha y puntuación
        $pdf->setFillColor(0.4, 0.4, 0.45);
        $fecha = date('d/m/Y');
        $pdf->text(120, 500, 'Fecha de emisión: ' . $fecha, 11, false);
        if ($puntos !== null) {
            $txt = 'Puntuación obtenida: ' . $puntos . ' pts';
            $pdf->text(722 - $pdf->textWidth($txt, 11), 500, $txt, 11, false);
        }

        $pdf->setStrokeColor(0.6, 0.6, 0.65);
        $pdf->rect($cx - 110, 505, 220, 0, 0.8);
        $pdf->setFillColor(0.3, 0.3, 0.35);
        $pdf->textCenter($cx, 525, 'Coordinación de Operaciones · JOSEPAN 360', 10, false);

        return $pdf->output();
    }
}
