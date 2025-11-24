<?php
if (!defined('ABSPATH')) exit;

function mrs_png_webp_process() {
    if (empty($_FILES['files'])) wp_send_json_error('Keine Dateien gesendet',400);

    $files = $_FILES['files'];
    $upload_dir = wp_upload_dir();
    $tmp_dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($tmp_dir)) wp_mkdir_p($tmp_dir);

    $quality = isset($_POST['quality']) ? intval($_POST['quality']) : 80;
    $keepName = isset($_POST['keep_original_name']) && $_POST['keep_original_name'] == '1';

    $outFiles = [];

    foreach ($files['tmp_name'] as $i => $tmpname) {
        $origName = sanitize_file_name($files['name'][$i]);
        $mime = mime_content_type($tmpname) ?: $files['type'][$i];

        if (!in_array($mime, ['image/png','image/jpeg','image/jpg'])) {
            continue;
        }

        $base = $keepName ? pathinfo($origName, PATHINFO_FILENAME) : 'converted_' . time() . '_' . $i;
        $destFilename = $base . '.webp';
        $destPath = $tmp_dir . wp_unique_filename($tmp_dir, $destFilename);

        $converted = false;

        if (class_exists('Imagick')) {
            try {
                $im = new Imagick($tmpname);
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality($quality);
                if (method_exists($im,'stripImage')) $im->stripImage();
                $im->writeImage($destPath);
                $im->clear(); $im->destroy();
                $converted = file_exists($destPath);
            } catch (\Exception $e) {
                $converted = false;
            }
        }

        if (!$converted && function_exists('imagewebp')) {
            if ($mime === 'image/png') {
                $img = imagecreatefrompng($tmpname);
                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    $converted = imagewebp($img, $destPath, $quality);
                    imagedestroy($img);
                }
            } else {
                $img = imagecreatefromjpeg($tmpname);
                if ($img) {
                    $converted = imagewebp($img, $destPath, $quality);
                    imagedestroy($img);
                }
            }
        }

        if (!$converted) continue;

        @chmod($destPath, 0644);
        $outFiles[] = $destPath;
    }

    if (empty($outFiles)) {
        wp_send_json_error('Keine Dateien konvertiert (fehlende PHP-Extensions oder ungültige Dateien).', 500);
    }

    if (count($outFiles) === 1) {
        $url = trailingslashit($upload_dir['baseurl']) . 'mrs_cs_temp/' . basename($outFiles[0]);
        wp_send_json_success(['url' => $url]);
    } else {
        $zip = new ZipArchive();
        $zipPath = $tmp_dir . 'webp_' . time() . '.zip';
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            wp_send_json_error('ZIP Fehler',500);
        }
        foreach ($outFiles as $f) $zip->addFile($f, basename($f));
        $zip->close();
        wp_send_json_success(['url' => trailingslashit($upload_dir['baseurl']) . 'mrs_cs_temp/' . basename($zipPath)]);
    }
}
