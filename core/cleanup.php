<?php
namespace MRS_CS;
if (!defined('ABSPATH')) exit;

class Cleanup {
    public static function run() {
        $hours = intval(get_option('mrs_cs_cleanup_hours', 24 * 7));
        if ($hours <= 0) return;
        $tmp_dir = temp_dir();
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
