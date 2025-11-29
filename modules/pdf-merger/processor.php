<?php
if (!defined('ABSPATH')) exit;

function mrs_pdf_merger_process() {
    if (empty($_FILES['files'])) wp_send_json_error('Keine Dateien gesendet', 400);

    $paths = [];
    // iterate and move each
    foreach ($_FILES['files']['name'] as $i => $name) {
        $_FILES['tmp_single'] = [
            'name' => $_FILES['files']['name'][$i],
            'type' => $_FILES['files']['type'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error' => $_FILES['files']['error'][$i],
            'size' => $_FILES['files']['size'][$i],
        ];
        $res = mrs_handle_single_upload('tmp_single', ['pdf'], 30 * 1024 * 1024);
        unset($_FILES['tmp_single']);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message(), 400);
        $paths[] = $res;
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
        $out = mrs_temp_dir() . 'merged_' . time() . '.pdf';
        $fpdi->Output($out, 'F');
        $token = mrs_create_download_token($out, 3600);
        $url = add_query_arg('mrs_cs_download', $token, home_url('/'));
        wp_send_json_success(['url' => $url]);
    } catch (Exception $e) {
        wp_send_json_error('Merge-Fehler: ' . $e->getMessage(), 500);
    }
}
