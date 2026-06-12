<?php
// core/ajax.php
if (!defined('ABSPATH')) exit;

/**
 * Core helper functions for uploads & downloads.
 */

function mrs_temp_dir() {
    $upload = wp_upload_dir();
    if (!empty($upload['error'])) {
        return '';
    }

    $dir = trailingslashit($upload['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        return '';
    }

    $ht = $dir . '.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
    }

    $index = $dir . 'index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }

    return $dir;
}

function mrs_verify_ajax_nonce() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
}

function mrs_is_temp_path($path) {
    $path = realpath($path);
    $temp_dir = realpath(mrs_temp_dir());

    if (!$path || !$temp_dir) {
        return false;
    }

    $path = wp_normalize_path($path);
    $temp_dir = trailingslashit(wp_normalize_path($temp_dir));

    return strpos($path, $temp_dir) === 0;
}

/**
 * Handle a single uploaded field.
 *
 * @param string $field $_FILES field name.
 * @param array  $allowed_ext Allowed extensions.
 * @param int    $max_bytes Max size or 0.
 * @return string|WP_Error Absolute path.
 */
function mrs_handle_single_upload($field, $allowed_ext = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'], $max_bytes = 0) {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['tmp_name'])) {
        return new WP_Error('no_file', 'Keine Datei gesendet');
    }

    if (!empty($_FILES[$field]['error'])) {
        return new WP_Error('upload_error', 'Upload-Fehler: ' . (int) $_FILES[$field]['error']);
    }

    if ($max_bytes > 0 && (int) $_FILES[$field]['size'] > $max_bytes) {
        return new WP_Error('file_too_large', 'Datei zu groß');
    }

    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return new WP_Error('invalid_upload', 'Ungültiger Upload');
    }

    $original_name = isset($_FILES[$field]['name']) ? wp_unslash($_FILES[$field]['name']) : '';
    $check = wp_check_filetype_and_ext($_FILES[$field]['tmp_name'], $original_name);
    $ext = isset($check['ext']) ? strtolower($check['ext']) : '';
    $allowed_ext = array_map('strtolower', (array) $allowed_ext);

    if (!$ext || !in_array($ext, $allowed_ext, true) || empty($check['type'])) {
        return new WP_Error('invalid_type', 'Ungültiger Dateityp');
    }

    $tmp_dir = mrs_temp_dir();
    if (!$tmp_dir) {
        return new WP_Error('temp_dir_failed', 'Temporärer Ordner konnte nicht erstellt werden');
    }

    $safe_name = sanitize_file_name($original_name);
    if (!$safe_name) {
        $safe_name = 'upload.' . $ext;
    }

    $dest_name = wp_unique_filename($tmp_dir, $safe_name);
    $dest = $tmp_dir . $dest_name;

    if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return new WP_Error('move_failed', 'Datei konnte nicht gespeichert werden');
    }

    @chmod($dest, 0644);
    return $dest;
}

function mrs_create_download_token($path, $seconds = 3600) {
    $path = realpath($path);
    if (!$path || !mrs_is_temp_path($path) || !is_file($path)) {
        return new WP_Error('invalid_download_path', 'Download-Datei ist ungültig');
    }

    $token = 'mrs_dl_' . wp_generate_password(16, false, false);
    set_transient($token, ['path' => $path, 'created' => time()], max(60, (int) $seconds));

    return $token;
}

function mrs_download_url($path, $seconds = 3600) {
    $token = mrs_create_download_token($path, $seconds);
    if (is_wp_error($token)) {
        return $token;
    }

    return add_query_arg('mrs_cs_download', rawurlencode($token), home_url('/'));
}

function mrs_resolve_download_token($token) {
    $token = sanitize_key($token);
    if (strpos($token, 'mrs_dl_') !== 0) {
        return new WP_Error('invalid_token', 'Ungültiges/abgelaufenes Token');
    }

    $payload = get_transient($token);
    if (empty($payload) || empty($payload['path'])) {
        return new WP_Error('invalid_token', 'Ungültiges/abgelaufenes Token');
    }

    $path = realpath($payload['path']);
    if (!$path || !mrs_is_temp_path($path)) {
        return new WP_Error('invalid_path', 'Download-Datei ist ungültig');
    }

    if (!file_exists($path) || !is_file($path) || !is_readable($path)) {
        return new WP_Error('not_found', 'Datei nicht gefunden');
    }

    return $path;
}

add_action('init', function() {
    if (empty($_GET['mrs_cs_download'])) {
        return;
    }

    $token = sanitize_key(wp_unslash($_GET['mrs_cs_download']));
    $path = mrs_resolve_download_token($token);
    if (is_wp_error($path)) {
        status_header(404);
        wp_die('Datei nicht verfügbar');
    }

    $ft = wp_check_filetype_and_ext($path, basename($path));
    $filename = sanitize_file_name(basename($path));

    nocache_headers();
    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($ft['type'] ?? 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
});
