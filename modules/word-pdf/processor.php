<?php
if (!defined('ABSPATH')) exit;

function mrs_word_pdf_process() {
    if (!isset($_FILES['file'])) {
        wp_send_json_error('Keine Datei gesendet', 400);
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['doc','docx'])) {
        wp_send_json_error('Ungültiges Dateiformat', 400);
    }

    $upload_dir = wp_upload_dir();
    $tmp_dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($tmp_dir)) wp_mkdir_p($tmp_dir);

    $nameSafe = sanitize_file_name($file['name']);
    $dest = $tmp_dir . wp_unique_filename($tmp_dir, $nameSafe);

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        wp_send_json_error('Upload fehlgeschlagen', 500);
    }

    $quality = isset($_POST['quality']) ? sanitize_text_field($_POST['quality']) : 'standard';

    try {
        // Use PhpOffice\PhpWord to read docx -> HTML, then Dompdf to PDF.
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            wp_send_json_error('Fehlende Bibliothek: phpword (install via composer)', 500);
        }

        $phpWord = \PhpOffice\PhpWord\IOFactory::load($dest);
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        $htmlFile = $tmp_dir . pathinfo($dest, PATHINFO_FILENAME) . '.html';
        $htmlWriter->save($htmlFile);

        if (!class_exists('\Dompdf\Dompdf')) {
            wp_send_json_error('Fehlende Bibliothek: dompdf (install via composer)', 500);
        }

        $html = file_get_contents($htmlFile);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfPath = $tmp_dir . pathinfo($dest, PATHINFO_FILENAME) . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());

        $pdfUrl = trailingslashit($upload_dir['baseurl']) . 'mrs_cs_temp/' . basename($pdfPath);
        wp_send_json_success(['url' => $pdfUrl]);

    } catch (\Exception $e) {
        wp_send_json_error('Konvertierungsfehler: ' . $e->getMessage(), 500);
    }
}
