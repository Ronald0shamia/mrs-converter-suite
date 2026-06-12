<?php
if (!defined('ABSPATH')) exit;

function mrs_word_pdf_process() {
    $res = mrs_handle_single_upload('file', ['doc', 'docx'], 12 * 1024 * 1024);
    if (is_wp_error($res)) {
        wp_send_json_error($res->get_error_message(), 400);
    }

    $uploaded = $res;

    if (!class_exists('\PhpOffice\PhpWord\IOFactory') || !class_exists('\Dompdf\Dompdf')) {
        wp_send_json_error('Konvertierung nicht verfügbar: benötigte PHP-Bibliotheken fehlen (phpword + dompdf).', 500);
    }

    try {
        $base_name = pathinfo($uploaded, PATHINFO_FILENAME);
        $htmlFile = mrs_temp_dir() . $base_name . '.html';
        $pdfPath = mrs_temp_dir() . $base_name . '.pdf';

        $phpWord = \PhpOffice\PhpWord\IOFactory::load($uploaded);
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($htmlFile);

        $html = file_get_contents($htmlFile);
        if ($html === false) {
            wp_send_json_error('Zwischendatei konnte nicht gelesen werden', 500);
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if (file_put_contents($pdfPath, $dompdf->output()) === false) {
            wp_send_json_error('PDF konnte nicht gespeichert werden', 500);
        }

        $url = mrs_download_url($pdfPath, 3600);
        if (is_wp_error($url)) {
            wp_send_json_error($url->get_error_message(), 500);
        }

        @unlink($htmlFile);
        wp_send_json_success(['url' => $url]);
    } catch (Throwable $e) {
        wp_send_json_error('Konvertierungsfehler: ' . $e->getMessage(), 500);
    }
}
