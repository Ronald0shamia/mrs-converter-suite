<?php
if (!defined('ABSPATH')) exit;

function mrs_word_pdf_process() {
    // handle upload
    $res = mrs_handle_single_upload('file', ['doc','docx'], 12 * 1024 * 1024);
    if (is_wp_error($res)) wp_send_json_error($res->get_error_message(), 400);
    $uploaded = $res;

    // conversion: try phpword+dompdf if installed, otherwise try to return error
    if (!class_exists('\PhpOffice\PhpWord\IOFactory') || !class_exists('\Dompdf\Dompdf')) {
        // Try to notify user to install libs
        wp_send_json_error('Konvertierung nicht verfügbar: benötigte PHP-Bibliotheken fehlen (phpword + dompdf).', 500);
    }

    try {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($uploaded);
        $htmlFile = mrs_temp_dir() . pathinfo($uploaded, PATHINFO_FILENAME) . '.html';
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($htmlFile);

        $html = file_get_contents($htmlFile);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfPath = mrs_temp_dir() . pathinfo($uploaded, PATHINFO_FILENAME) . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());

        $token = mrs_create_download_token($pdfPath, 3600);
        $url = add_query_arg('mrs_cs_download', $token, home_url('/'));
        wp_send_json_success(['url' => $url]);
    } catch (Exception $e) {
        wp_send_json_error('Konvertierungsfehler: ' . $e->getMessage(), 500);
    }
}
