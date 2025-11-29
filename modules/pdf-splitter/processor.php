<?php
if (!defined('ABSPATH')) exit;

function mrs_parse_ranges($str) {
    $str = str_replace(' ', '', trim($str));
    if ($str === '') return [];
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
    if (empty($_FILES['file'])) wp_send_json_error('Keine Datei gesendet', 400);
    $res = mrs_handle_single_upload('file', ['pdf'], 50 * 1024 * 1024);
    if (is_wp_error($res)) wp_send_json_error($res->get_error_message(), 400);
    $uploaded = $res;

    if (!class_exists('\setasign\Fpdi\Fpdi')) wp_send_json_error('Split nicht verfügbar: setasign/fpdi fehlt (composer).', 500);

    try {
        $fpdi = new \setasign\Fpdi\Fpdi();
        $total = $fpdi->setSourceFile($uploaded);
    } catch (Exception $e) {
        wp_send_json_error('PDF kann nicht gelesen werden', 500);
    }

    $pagesInput = sanitize_text_field($_POST['pages'] ?? '');
    $outFiles = [];

    if ($pagesInput === '') {
        for ($p = 1; $p <= $total; $p++) {
            $pdfOut = new \setasign\Fpdi\Fpdi();
            $tpl = $pdfOut->setSourceFile($uploaded);
            $imported = $pdfOut->importPage($p);
            $size = $pdfOut->getTemplateSize($imported);
            $pdfOut->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdfOut->useTemplate($imported);
            $out = mrs_temp_dir() . pathinfo($uploaded, PATHINFO_FILENAME) . '_page_' . $p . '.pdf';
            $pdfOut->Output($out, 'F');
            $outFiles[] = $out;
        }
    } else {
        $pages = mrs_parse_ranges($pagesInput);
        if (empty($pages)) wp_send_json_error('Ungültige Seitenangabe', 400);
        foreach ($pages as $p) {
            if ($p < 1 || $p > $total) continue;
            $pdfOut = new \setasign\Fpdi\Fpdi();
            $tpl = $pdfOut->setSourceFile($uploaded);
            $imported = $pdfOut->importPage($p);
            $size = $pdfOut->getTemplateSize($imported);
            $pdfOut->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdfOut->useTemplate($imported);
            $out = mrs_temp_dir() . pathinfo($uploaded, PATHINFO_FILENAME) . '_page_' . $p . '.pdf';
            $pdfOut->Output($out, 'F');
            $outFiles[] = $out;
        }
    }

    if (empty($outFiles)) wp_send_json_error('Keine Seiten extrahiert', 400);

    if (count($outFiles) === 1) {
        $token = mrs_create_download_token($outFiles[0], 3600);
        $url = add_query_arg('mrs_cs_download', $token, home_url('/'));
        wp_send_json_success(['url' => $url]);
    } else {
        $zip = new ZipArchive();
        $zipPath = mrs_temp_dir() . 'split_' . time() . '.zip';
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            wp_send_json_error('ZIP Fehler', 500);
        }
        foreach ($outFiles as $f) $zip->addFile($f, basename($f));
        $zip->close();
        $token = mrs_create_download_token($zipPath, 3600);
        $url = add_query_arg('mrs_cs_download', $token, home_url('/'));
        wp_send_json_success(['url' => $url]);
    }
}
