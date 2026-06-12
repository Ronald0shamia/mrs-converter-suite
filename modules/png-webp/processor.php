<?php
if (!defined('ABSPATH')) exit;

function mrs_png_webp_process() {
    if (empty($_FILES['files']) || empty($_FILES['files']['name']) || !is_array($_FILES['files']['name'])) {
        wp_send_json_error('Keine Dateien gesendet', 400);
    }

    $tmp_dir = mrs_temp_dir();
    if (!$tmp_dir) {
        wp_send_json_error('Temporärer Ordner konnte nicht erstellt werden', 500);
    }

    $outFiles = [];
    $quality = isset($_POST['quality']) ? absint($_POST['quality']) : 80;
    $quality = min(100, max(0, $quality));

    foreach ($_FILES['files']['tmp_name'] as $i => $tmpname) {
        $_FILES['tmp_single'] = [
            'name' => $_FILES['files']['name'][$i] ?? '',
            'type' => $_FILES['files']['type'][$i] ?? '',
            'tmp_name' => $tmpname,
            'error' => $_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES['files']['size'][$i] ?? 0,
        ];

        $res = mrs_handle_single_upload('tmp_single', ['png', 'jpg', 'jpeg'], 10 * 1024 * 1024);
        unset($_FILES['tmp_single']);

        if (is_wp_error($res)) {
            continue;
        }

        $src = $res;
        $dest = $tmp_dir . wp_unique_filename($tmp_dir, pathinfo($src, PATHINFO_FILENAME) . '.webp');
        $converted = false;

        if (class_exists('Imagick')) {
            try {
                $im = new Imagick($src);
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality($quality);
                if (method_exists($im, 'stripImage')) {
                    $im->stripImage();
                }
                $im->writeImage($dest);
                $im->clear();
                $im->destroy();
                $converted = file_exists($dest) && filesize($dest) > 0;
            } catch (Throwable $e) {
                $converted = false;
            }
        }

        if (!$converted && function_exists('imagewebp')) {
            $mime = mime_content_type($src);
            $img = false;

            if ($mime && strpos($mime, 'png') !== false) {
                $img = imagecreatefrompng($src);
                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
            } elseif ($mime && strpos($mime, 'jpeg') !== false) {
                $img = imagecreatefromjpeg($src);
            }

            if ($img) {
                $converted = imagewebp($img, $dest, $quality);
                imagedestroy($img);
            }
        }

        if ($converted && file_exists($dest)) {
            $outFiles[] = $dest;
        }
    }

    if (empty($outFiles)) {
        wp_send_json_error('Keine Dateien konvertiert (fehlende Extensions?)', 500);
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
    $zipPath = $tmp_dir . 'webp_' . wp_generate_password(8, false, false) . '.zip';
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
