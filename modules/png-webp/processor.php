<?php
if (!defined('ABSPATH')) exit;

function mrs_png_webp_process() {
    if (empty($_FILES['files'])) wp_send_json_error('Keine Dateien gesendet', 400);

    $tmp_dir = mrs_temp_dir();
    $outFiles = [];
    $quality = isset($_POST['quality']) ? intval($_POST['quality']) : 80;

    foreach ($_FILES['files']['tmp_name'] as $i => $tmpname) {
        $_FILES['tmp_single'] = [
            'name' => $_FILES['files']['name'][$i],
            'type' => $_FILES['files']['type'][$i],
            'tmp_name' => $tmpname,
            'error' => $_FILES['files']['error'][$i],
            'size' => $_FILES['files']['size'][$i],
        ];
        $res = mrs_handle_single_upload('tmp_single', ['png','jpg','jpeg'], 10 * 1024 * 1024);
        unset($_FILES['tmp_single']);
        if (is_wp_error($res)) continue;
        $src = $res;
        $dest = $tmp_dir . pathinfo($src, PATHINFO_FILENAME) . '.webp';
        $converted = false;

        if (class_exists('Imagick')) {
            try {
                $im = new Imagick($src);
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality($quality);
                if (method_exists($im,'stripImage')) $im->stripImage();
                $im->writeImage($dest);
                $im->clear(); $im->destroy();
                $converted = file_exists($dest);
            } catch (Exception $e) {
                $converted = false;
            }
        }

        if (!$converted && function_exists('imagewebp')) {
            $mime = mime_content_type($src);
            if (strpos($mime, 'png') !== false) {
                $img = imagecreatefrompng($src);
                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    $converted = imagewebp($img, $dest, $quality);
                    imagedestroy($img);
                }
            } else {
                $img = imagecreatefromjpeg($src);
                if ($img) {
                    $converted = imagewebp($img, $dest, $quality);
                    imagedestroy($img);
                }
            }
        }

        if ($converted) $outFiles[] = $dest;
    }

    if (empty($outFiles)) wp_send_json_error('Keine Dateien konvertiert (fehlende Extensions?)', 500);

    if (count($outFiles) === 1) {
        $token = mrs_create_download_token($outFiles[0], 3600);
        $url = add_query_arg('mrs_cs_download', $token, home_url('/'));
        wp_send_json_success(['url' => $url]);
    } else {
        $zip = new ZipArchive();
        $zipPath = mrs_temp_dir() . 'webp_' . time() . '.zip';
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
