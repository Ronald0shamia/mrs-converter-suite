<?php
namespace MRS_CS;
if (!defined('ABSPATH')) exit;

class Cleanup {
    // delete files older than X hours in mrs_cs_temp
    public static function run() {
        $hours = intval(get_option('mrs_cs_cleanup_hours', 24 * 7));
        if ($hours <= 0) return;

        $upload_dir = wp_upload_dir();
        $tmp_dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
        if (!is_dir($tmp_dir)) return;

        $now = time();
        $files = glob($tmp_dir . '*');
        if (!$files) return;

        foreach ($files as $f) {
            if (!is_file($f)) continue;
            $ageHours = ($now - filemtime($f)) / 3600;
            if ($ageHours > $hours) {
                @unlink($f);
            }
        }
    }
}
