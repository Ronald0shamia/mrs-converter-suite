<?php
if (!defined('ABSPATH')) exit;

function mrs_word_pdf_process() {
    // use helper to handle upload
    $res = \MRS_CS\handle_upload_field('file', ['doc','docx'], 10 * 1024 * 1024);
    if (is_wp_error($res)) {
        wp_send_json_error($res->get_error_message(), 400);
    }
    $uploadedPath = $res;

    // require composer libs
    if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
        wp_send_json_error('Fehlende Bibliothek: phpoffice/phpword (via composer)', 500);
    }
    if (!class_exists('\Dompdf\Dompdf')) {
        wp_send_json_error('Fehlende Bibliothek: dompdf (via composer)', 500);
    }

    try {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($uploadedPath);
        $htmlFile = \MRS_CS\temp_dir() . pathinfo($uploadedPath, PATHINFO_FILENAME) . '.html';
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($htmlFile);

        $html = file_get_contents($htmlFile);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfPath = \MRS_CS\temp_dir() . pathinfo($uploadedPath, PATHINFO_FILENAME) . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());

        $token = \MRS_CS\create_download_token($pdfPath, 3600);
        $url = add_query_arg('mrs_cs_download', $token, home_url('/'));
        wp_send_json_success(['url' => $url]);
    } catch (\Exception $e) {
        wp_send_json_error('Konvertierungsfehler: ' . $e->getMessage(), 500);
    }
}
