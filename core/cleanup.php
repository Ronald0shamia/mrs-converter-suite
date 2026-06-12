<?php
if (!defined('ABSPATH')) exit;

class MRS_Cleanup {

    public function __construct() {
        add_action('mrs_cleanup_daily', [$this,'cleanup_temp']);
        if (!wp_next_scheduled('mrs_cleanup_daily')) {
            wp_schedule_event(time(), 'daily', 'mrs_cleanup_daily');
        }
    }

    public function cleanup_temp() {
        $dir = function_exists('mrs_temp_dir') ? mrs_temp_dir() : '';

        if (!is_dir($dir)) return;

        foreach (glob(trailingslashit($dir) . '*') as $file) {
            if (is_file($file) && filemtime($file) < (time() - DAY_IN_SECONDS)) {
                @unlink($file);
            }
        }
    }
}

new MRS_Cleanup();
