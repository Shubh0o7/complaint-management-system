<?php
/** Minimal text PDF writer for deployment without Composer dependencies. */
function output_text_pdf(string $filename, string $title, array $lines): never
{
    $lines = array_merge([$title, ''], $lines);
    $chunks = array_chunk($lines, 42);
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $pageRefs = [];
    $next = 4;
    foreach ($chunks as $pageIndex => $pageLines) {
        $pageRefs[] = $next . ' 0 R'; $next += 2;
    }
    $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';
    $fontObject = $next . ' 0 R';
    $objects[] = '<< /Length 0 >> stream endstream';
    foreach ($chunks as $pageLines) {
        $content = "BT\n/F1 11 Tf\n50 780 Td\n";
        foreach ($pageLines as $index => $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], preg_replace('/[^\x20-\x7E]/', '?', (string)$line));
            if ($index > 0) $content .= "0 -18 Td\n";
            $content .= '(' . $safe . ') Tj\n';
        }
        $content .= "ET";
        $contentObject = count($objects) + 1;
        $objects[] = '<< /Length ' . strlen($content) . ' >> stream\n' . $content . '\nendstream';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 ' . $fontObject . ' >> >> /Contents ' . $contentObject . ' 0 R >>';
    }
    $objects[$fontObject - 1] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $pdf = "%PDF-1.4\n"; $offsets = [0];
    foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n"; }
    $xref = strlen($pdf); $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n \n', $offsets[$i]);
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"'); header('Content-Length: ' . strlen($pdf)); echo $pdf; exit;
}
?>
