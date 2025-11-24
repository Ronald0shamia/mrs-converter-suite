<?php
if (!defined('ABSPATH')) exit;

function parse_page_ranges($str) {
    $str = str_replace(' ', '', $str);
    $parts = array_filter(explode(',', $str));
    $pages = [];
    foreach ($parts as $p) {
        if (strpos($p, '-') !== false) {
            list($a,$b) = explode('-', $p);
            for ($i = max(1,(int)$a); $i <= (int)$b; $i++) $pages[] = $i;
        } else {
            $pages[] = (int)$p;
        }
    }
    return array_unique(array_filter($pages));
}

function mrs_pdf_splitter_process() {
    if (empty($_FILES['file'])) wp_send_json_error('Keine Datei',400);
    $file = $_FILES['file'];
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') wp_send_json_error('Nur PDF erlaubt',400);

    $upload_dir = wp_upload_dir();
    $tmp_dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($tmp_dir)) wp_mkdir_p($tmp_dir);

    $dest = $tmp_dir . wp_unique_filename($tmp_dir, sanitize_file_name($file['name']));
    if (!move_uploaded_file($file['tmp_name'], $dest)) wp_send_json_error('Upload fehlgeschlagen',500);

    if (!class_exists('\setasign\Fpdi\Fpdi')) wp_send_json_error('Fehlende Bibliothek: setasign/fpdi',500);

    $pagesInput = isset($_POST['pages']) ? sanitize_text_field($_POST['pages']) : '';
    $outFiles = [];

    try {
        $pdfCount = (new \setasign\Fpdi\Fpdi())->setSourceFile($dest);
    } catch (\Exception $e) {
        wp_send_json_error('PDF kann nicht gelesen werden',500);
    }

    if (empty($pagesInput)) {
        // split into single pages
        for ($p = 1; $p <= $pdfCount; $p++) {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pdf->AddPage();
            $tplId = $pdf->setSourceFile($dest);
            $imported = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($imported);
            $pdf = new \setasign\Fpdi\Fpdi();
            // re-instantiate for safe rendering
            $tplId = $pdf->setSourceFile($dest);
            $imported = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($imported);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($imported);
            $out = $tmp_dir . pathinfo($dest, PATHINFO_FILENAME) . '_page_' . $p . '.pdf';
            $pdf->Output($out, 'F');
            $outFiles[] = $out;
        }
    } else {
        $pages = parse_page_ranges($pagesInput);
        if (empty($pages)) wp_send_json_error('Ungültige Seitenangabe',400);
        foreach ($pages as $p) {
            if ($p < 1 || $p > $pdfCount) continue;
            $pdf = new \setasign\Fpdi\Fpdi();
            $tplId = $pdf->setSourceFile($dest);
            $imported = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($imported);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($imported);
            $out = $tmp_dir . pathinfo($dest, PATHINFO_FILENAME) . '_page_' . $p . '.pdf';
            $pdf->Output($out, 'F');
            $outFiles[] = $out;
        }
    }

    if (empty($outFiles)) wp_send_json_error('Keine gültigen Seiten extrahiert',400);

    if (count($outFiles) === 1) {
        $url = trailingslashit($upload_dir['baseurl']) . 'mrs_cs_temp/' . basename($outFiles[0]);
        wp_send_json_success(['url' => $url]);
    } else {
        $zip = new ZipArchive();
        $zipPath = $tmp_dir . 'split_' . time() . '.zip';
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            wp_send_json_error('ZIP Fehler',500);
        }
        foreach ($outFiles as $f) $zip->addFile($f, basename($f));
        $zip->close();
        wp_send_json_success(['url' => trailingslashit($upload_dir['baseurl']) . 'mrs_cs_temp/' . basename($zipPath)]);
    }
}
