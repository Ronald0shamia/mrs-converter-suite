<?php
if (!defined('ABSPATH')) exit;

function mrs_pdf_merger_process() {
    if (empty($_FILES['files'])) wp_send_json_error('Keine Dateien',400);

    $files = $_FILES['files'];
    $upload_dir = wp_upload_dir();
    $tmp_dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($tmp_dir)) wp_mkdir_p($tmp_dir);

    $paths = [];
    foreach ($files['tmp_name'] as $i => $tmpname) {
        $name = sanitize_file_name($files['name'][$i]);
        $dest = $tmp_dir . wp_unique_filename($tmp_dir, $name);
        if (!move_uploaded_file($tmpname, $dest)) {
            wp_send_json_error('Fehler beim Upload',500);
        }
        $paths[] = $dest;
    }

    if (!class_exists('\setasign\Fpdi\Fpdi')) {
        wp_send_json_error('Fehlende Bibliothek: setasign/fpdi (install via composer)', 500);
    }

    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        foreach ($paths as $p) {
            $pageCount = $pdf->setSourceFile($p);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);
            }
        }
        $out = $tmp_dir . 'merged_' . time() . '.pdf';
        $pdf->Output($out, 'F');

        $url = trailingslashit($upload_dir['baseurl']) . 'mrs_cs_temp/' . basename($out);
        wp_send_json_success(['url' => $url]);
    } catch (\Exception $e) {
        wp_send_json_error('Merge-Fehler: ' . $e->getMessage(),500);
    }
}
