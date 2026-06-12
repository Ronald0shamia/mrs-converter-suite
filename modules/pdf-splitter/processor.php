<?php
if (!defined('ABSPATH')) exit;

function mrs_parse_ranges($str, $total_pages = 0) {
    $str = preg_replace('/\s+/', '', trim((string) $str));
    if ($str === '') {
        return [];
    }

    if (!preg_match('/^\d+(-\d+)?(,\d+(-\d+)?)*$/', $str)) {
        return new WP_Error('invalid_range', 'Ungültige Seitenangabe');
    }

    $pages = [];
    foreach (explode(',', $str) as $part) {
        if (strpos($part, '-') !== false) {
            [$start, $end] = array_map('intval', explode('-', $part, 2));
            if ($start < 1 || $end < $start) {
                return new WP_Error('invalid_range', 'Ungültige Seitenangabe');
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($total_pages > 0 && $i > $total_pages) {
                    return new WP_Error('range_out_of_bounds', 'Seitenangabe liegt außerhalb des PDFs');
                }
                $pages[] = $i;
            }
            continue;
        }

        $page = (int) $part;
        if ($page < 1 || ($total_pages > 0 && $page > $total_pages)) {
            return new WP_Error('range_out_of_bounds', 'Seitenangabe liegt außerhalb des PDFs');
        }
        $pages[] = $page;
    }

    return array_values(array_unique($pages));
}

function mrs_pdf_splitter_process() {
    if (empty($_FILES['file'])) {
        wp_send_json_error('Keine Datei gesendet', 400);
    }

    $res = mrs_handle_single_upload('file', ['pdf'], 50 * 1024 * 1024);
    if (is_wp_error($res)) {
        wp_send_json_error($res->get_error_message(), 400);
    }

    $uploaded = $res;

    if (!class_exists('\setasign\Fpdi\Fpdi')) {
        wp_send_json_error('Split nicht verfügbar: setasign/fpdi fehlt (composer).', 500);
    }

    try {
        $fpdi = new \setasign\Fpdi\Fpdi();
        $total = $fpdi->setSourceFile($uploaded);
    } catch (Throwable $e) {
        wp_send_json_error('PDF kann nicht gelesen werden', 500);
    }

    $pagesInput = isset($_POST['pages']) ? sanitize_text_field(wp_unslash($_POST['pages'])) : '';
    $pages = $pagesInput === '' ? range(1, $total) : mrs_parse_ranges($pagesInput, $total);
    if (is_wp_error($pages)) {
        wp_send_json_error($pages->get_error_message(), 400);
    }

    if (empty($pages)) {
        wp_send_json_error('Keine Seiten extrahiert', 400);
    }

    $outFiles = [];
    $base_name = pathinfo($uploaded, PATHINFO_FILENAME);

    try {
        foreach ($pages as $p) {
            $pdfOut = new \setasign\Fpdi\Fpdi();
            $pdfOut->setSourceFile($uploaded);
            $imported = $pdfOut->importPage($p);
            $size = $pdfOut->getTemplateSize($imported);
            $pdfOut->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdfOut->useTemplate($imported);

            $out = mrs_temp_dir() . $base_name . '_page_' . $p . '.pdf';
            $pdfOut->Output($out, 'F');
            $outFiles[] = $out;
        }
    } catch (Throwable $e) {
        wp_send_json_error('Split-Fehler: ' . $e->getMessage(), 500);
    }

    if (count($outFiles) === 1) {
        $url = mrs_download_url($outFiles[0], 3600);
        if (is_wp_error($url)) {
            wp_send_json_error($url->get_error_message(), 500);
        }

        wp_send_json_success(['url' => $url]);
    }

    if (!class_exists('ZipArchive')) {
        wp_send_json_error('ZIP-Erstellung nicht verfügbar', 500);
    }

    $zip = new ZipArchive();
    $zipPath = mrs_temp_dir() . 'split_' . wp_generate_password(8, false, false) . '.zip';
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        wp_send_json_error('ZIP Fehler', 500);
    }

    foreach ($outFiles as $f) {
        $zip->addFile($f, basename($f));
    }
    $zip->close();

    $url = mrs_download_url($zipPath, 3600);
    if (is_wp_error($url)) {
        wp_send_json_error($url->get_error_message(), 500);
    }

    wp_send_json_success(['url' => $url]);
}
