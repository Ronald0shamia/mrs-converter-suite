<?php
// core/ajax.php
if (!defined('ABSPATH')) exit;

/**
 * Core helper functions for uploads & downloads
 */

/**
 * Ensure and return temp directory path (absolute)
 */
function mrs_temp_dir() {
    $upload = wp_upload_dir();
    $dir = trailingslashit($upload['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($dir)) wp_mkdir_p($dir);
    // basic .htaccess to avoid index listing
    $ht = $dir . '.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all granted\n</IfModule>");
    }
    return $dir;
}

/**
 * Handle a single uploaded field (uses wp_handle_upload fallback)
 * @param string $field $_FILES field name
 * @param array $allowed_ext allowed extensions
 * @param int $max_bytes max size or 0
 * @return string|WP_Error absolute path
 */
function mrs_handle_single_upload($field, $allowed_ext = ['pdf','doc','docx','png','jpg','jpeg'], $max_bytes = 0) {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['tmp_name'])) {
        return new WP_Error('no_file', 'Keine Datei gesendet');
    }

    if (!empty($_FILES[$field]['error'])) {
        return new WP_Error('upload_error', 'Upload Error: ' . $_FILES[$field]['error']);
    }

    if ($max_bytes > 0 && $_FILES[$field]['size'] > $max_bytes) {
        return new WP_Error('file_too_large', 'Datei zu groß');
    }

    // Validate type
    $check = wp_check_filetype_and_ext($_FILES[$field]['tmp_name'], $_FILES[$field]['name']);
    $ext = isset($check['ext']) ? strtolower($check['ext']) : '';
    if (!$ext || !in_array($ext, $allowed_ext, true)) {
        return new WP_Error('invalid_type', 'Ungültiger Dateityp');
    }

    // Move to temp dir with unique name
    $tmp_dir = mrs_temp_dir();
    $safe_name = sanitize_file_name($_FILES[$field]['name']);
    $dest_name = wp_unique_filename($tmp_dir, $safe_name);
    $dest = $tmp_dir . $dest_name;

    if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        // fallback copy
        if (!@copy($_FILES[$field]['tmp_name'], $dest)) {
            return new WP_Error('move_failed', 'Datei konnte nicht gespeichert werden');
        }
    }

    @chmod($dest, 0644);
    return $dest;
}

/**
 * Create transient-based download token (returns token)
 */
function mrs_create_download_token($path, $seconds = 3600) {
    $token = 'mrs_dl_' . wp_generate_password(16, false, false);
    $payload = ['path' => $path, 'created' => time()];
    set_transient($token, $payload, $seconds);
    return $token;
}

/**
 * Resolve token to path
 */
function mrs_resolve_download_token($token) {
    $payload = get_transient($token);
    if (empty($payload) || empty($payload['path'])) {
        return new WP_Error('invalid_token', 'Ungültiges/abgelaufenes Token');
    }
    $path = $payload['path'];
    if (!file_exists($path) || !is_file($path)) {
        return new WP_Error('not_found', 'Datei nicht gefunden');
    }
    return $path;
}

/**
 * Download proxy endpoint (init)
 */
add_action('init', function(){
    if (!empty($_GET['mrs_cs_download'])) {
        $token = sanitize_text_field($_GET['mrs_cs_download']);
        $path = mrs_resolve_download_token($token);
        if (is_wp_error($path)) {
            status_header(404);
            wp_die('Datei nicht verfügbar');
        }
        $ft = wp_check_filetype_and_ext($path, basename($path));
        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($ft['type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
});
