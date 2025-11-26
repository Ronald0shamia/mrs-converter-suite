<?php
namespace MRS_CS;
if (!defined('ABSPATH')) exit;

/**
 * Helper: ensure temp dir exists and protected
 */
function temp_dir(): string {
    $upload_dir = wp_upload_dir();
    $dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    // basic .htaccess to prevent indexes
    $ht = $dir . '.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all granted\n</IfModule>");
    }
    return $dir;
}

/**
 * Handle single file upload passed as $_FILES[$field] or array in $_FILES (helper supports "single" and "array")
 * Returns absolute path string on success or WP_Error on failure.
 */
function handle_upload_field(string $field_name, array $allowed_ext = ['pdf','doc','docx','png','jpg','jpeg'], int $max_bytes = 0) {
    if (!isset($_FILES[$field_name])) {
        return new \WP_Error('no_file', 'Keine Datei gesendet');
    }

    $file = $_FILES[$field_name];

    // check PHP upload errors
    if (!empty($file['error'])) {
        return new \WP_Error('upload_error', 'Upload-Error: ' . $file['error']);
    }

    // optional size limit
    if ($max_bytes > 0 && $file['size'] > $max_bytes) {
        return new \WP_Error('file_too_big', 'Datei zu groß');
    }

    // validate ext + mime
    $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    $ext = isset($check['ext']) ? strtolower($check['ext']) : '';
    if (empty($ext) || !in_array($ext, $allowed_ext, true)) {
        return new \WP_Error('invalid_type', 'Ungültiger Dateityp');
    }

    $tmp_dir = temp_dir();
    $safe_name = sanitize_file_name($file['name']);
    $unique = wp_unique_filename($tmp_dir, $safe_name);
    $dest = $tmp_dir . $unique;

    // move
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        // fallback copy if possible
        if (!@copy($file['tmp_name'], $dest)) {
            return new \WP_Error('move_failed', 'Datei konnte nicht gespeichert werden');
        }
    }

    @chmod($dest, 0644);
    return $dest;
}

/**
 * Create transient-based download token
 * returns token string
 */
function create_download_token(string $path, int $seconds = 3600): string {
    $token = 'mrs_cs_dl_' . wp_generate_password(16, false, false);
    $payload = [
        'path' => $path,
        'created' => time(),
    ];
    set_transient($token, $payload, $seconds);
    return $token;
}

/**
 * Resolve token -> path or WP_Error
 */
function resolve_download_token(string $token) {
    $data = get_transient($token);
    if (empty($data) || empty($data['path'])) {
        return new \WP_Error('invalid_token', 'Ungültiges oder abgelaufenes Token');
    }
    $path = $data['path'];
    if (!file_exists($path) || !is_file($path)) {
        return new \WP_Error('not_found', 'Datei nicht gefunden');
    }
    return $path;
}

/**
 * Init: download proxy endpoint on front (via query param)
 */
add_action('init', function(){
    if (!empty($_GET['mrs_cs_download'])) {
        $token = sanitize_text_field($_GET['mrs_cs_download']);
        $path = resolve_download_token($token);
        if (is_wp_error($path)) {
            status_header(404);
            wp_die('Datei nicht verfügbar');
        }
        // stream file
        $finfo = wp_check_filetype_and_ext($path, basename($path));
        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($finfo['type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        // flush and read
        readfile($path);
        exit;
    }
});
