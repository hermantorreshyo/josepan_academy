<?php
/**
 * Generador de PDF mínimo en PHP puro — sin Composer, sin FPDF/TCPDF.
 * Soporta una página, fuentes núcleo (Helvetica / Helvetica-Bold) con
 * codificación WinAnsi (acentos y ñ del español), texto, texto centrado,
 * rectángulos y líneas. Suficiente para certificados de una página.
 *
 * Coordenadas de la API: origen arriba-izquierda (yTop), se convierte al
 * sistema PDF (origen abajo-izquierda) internamente.
 */
class PdfBuilder
{
    private float $w;
    private float $h;
    private array $ops = [];

    // Anchos Helvetica (unidades/1000) para estimar ancho de texto y centrar.
    private const WIDTHS = [
        32=>278,33=>278,34=>355,35=>556,36=>556,37=>889,38=>667,39=>191,40=>333,41=>333,
        42=>389,43=>584,44=>278,45=>333,46=>278,47=>278,48=>556,49=>556,50=>556,51=>556,
        52=>556,53=>556,54=>556,55=>556,56=>556,57=>556,58=>278,59=>278,60=>584,61=>584,
        62=>584,63=>556,64=>1015,65=>667,66=>667,67=>722,68=>722,69=>667,70=>611,71=>778,
        72=>722,73=>278,74=>500,75=>667,76=>556,77=>833,78=>722,79=>778,80=>667,81=>778,
        82=>722,83=>667,84=>611,85=>722,86=>667,87=>944,88=>667,89=>667,90=>611,91=>278,
        92=>278,93=>278,94=>469,95=>556,96=>333,97=>556,98=>556,99=>500,100=>556,101=>556,
        102=>278,103=>556,104=>556,105=>222,106=>222,107=>500,108=>222,109=>833,110=>556,
        111=>556,112=>556,113=>556,114=>333,115=>500,116=>278,117=>556,118=>500,119=>722,
        120=>500,121=>500,122=>500,123=>334,124=>260,125=>334,126=>584,
    ];

    public function __construct(float $w = 842, float $h = 595) // A4 horizontal
    {
        $this->w = $w;
        $this->h = $h;
    }

    public function width(): float { return $this->w; }

    private static function toWinAnsi(string $text): string
    {
        $c = @iconv('UTF-8', 'CP1252//TRANSLIT', $text);
        return $c === false ? $text : $c;
    }

    public function setFillColor(float $r, float $g, float $b): void
    {
        $this->ops[] = sprintf('%.3f %.3f %.3f rg', $r, $g, $b);
    }

    public function setStrokeColor(float $r, float $g, float $b): void
    {
        $this->ops[] = sprintf('%.3f %.3f %.3f RG', $r, $g, $b);
    }

    public function rect(float $x, float $yTop, float $w, float $h, float $lineWidth = 1, bool $fill = false): void
    {
        $y = $this->h - $yTop - $h; // esquina inferior izquierda
        $this->ops[] = sprintf('%.2f w', $lineWidth);
        $this->ops[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $y, $w, $h, $fill ? 'f' : 'S');
    }

    /** Ancho del texto en puntos para un tamaño de fuente. */
    public function textWidth(string $text, float $size, bool $bold = false): float
    {
        $win = self::toWinAnsi($text);
        $total = 0;
        $len = strlen($win);
        for ($i = 0; $i < $len; $i++) {
            $code = ord($win[$i]);
            $w = self::WIDTHS[$code] ?? 556; // acentos/ñ usan ancho medio
            $total += $w;
        }
        $factor = $bold ? 1.06 : 1.0; // la negrita es algo más ancha
        return $total / 1000 * $size * $factor;
    }

    public function text(float $x, float $yTop, string $text, float $size = 12, bool $bold = false): void
    {
        $y = $this->h - $yTop;
        $font = $bold ? '/F2' : '/F1';
        $esc = self::escape(self::toWinAnsi($text));
        $this->ops[] = sprintf('BT %s %.2f Tf %.2f %.2f Td (%s) Tj ET', $font, $size, $x, $y, $esc);
    }

    public function textCenter(float $centerX, float $yTop, string $text, float $size = 12, bool $bold = false): void
    {
        $x = $centerX - $this->textWidth($text, $size, $bold) / 2;
        $this->text($x, $yTop, $text, $size, $bold);
    }

    private static function escape(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s);
    }

    /** Construye y devuelve los bytes del PDF. */
    public function output(): string
    {
        $content = "q\n" . implode("\n", $this->ops) . "\nQ";
        $objects = [];

        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] "
            . "/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>",
            $this->w, $this->h
        );
        $objects[4] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}
