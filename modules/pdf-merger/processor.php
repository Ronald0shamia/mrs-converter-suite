<?php
if (!defined('ABSPATH')) exit;

function mrs_pdf_merger_process() {
    if (empty($_FILES['files']) || empty($_FILES['files']['name']) || !is_array($_FILES['files']['name'])) {
        wp_send_json_error('Keine Dateien gesendet', 400);
    }

    $paths = [];
    foreach ($_FILES['files']['name'] as $i => $name) {
        $_FILES['tmp_single'] = [
            'name' => $_FILES['files']['name'][$i] ?? '',
            'type' => $_FILES['files']['type'][$i] ?? '',
            'tmp_name' => $_FILES['files']['tmp_name'][$i] ?? '',
            'error' => $_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES['files']['size'][$i] ?? 0,
        ];

        $res = mrs_handle_single_upload('tmp_single', ['pdf'], 30 * 1024 * 1024);
        unset($_FILES['tmp_single']);

        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message(), 400);
        }

        $paths[] = $res;
    }

    if (count($paths) < 2) {
        wp_send_json_error('Bitte mindestens zwei PDF-Dateien auswählen', 400);
    }

    if (!class_exists('\setasign\Fpdi\Fpdi')) {
        wp_send_json_error('Merge nicht verfügbar: setasign/fpdi fehlt (composer).', 500);
    }

    try {
        $fpdi = new \setasign\Fpdi\Fpdi();
        foreach ($paths as $p) {
            $pageCount = $fpdi->setSourceFile($p);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($tpl);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }
        }

        $out = mrs_temp_dir() . 'merged_' . wp_generate_password(8, false, false) . '.pdf';
        $fpdi->Output($out, 'F');

        $url = mrs_download_url($out, 3600);
        if (is_wp_error($url)) {
            wp_send_json_error($url->get_error_message(), 500);
        }

        wp_send_json_success(['url' => $url]);
    } catch (Throwable $e) {
        wp_send_json_error('Merge-Fehler: ' . $e->getMessage(), 500);
    }
}
