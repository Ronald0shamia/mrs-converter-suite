<?php
/*
Plugin Name: MRS Converter Suite
Description: Sammlung von Converter-Tools (Word→PDF, PDF Merger, PDF Splitter, PNG→WEBP ...). Jedes Tool hat einen Shortcode für eigene Frontend-Seiten.
Version: 0.1.0
Author: Raeed Shamia
Author URI: https://mrs-dev.com
Text Domain: mrs-converter-suite
*/

if (!defined('ABSPATH')) exit;

define('MRS_CS_PATH', plugin_dir_path(__FILE__));
define('MRS_CS_URL', plugin_dir_url(__FILE__));
define('MRS_CS_VERSION', '0.2.0');

// Activation / Deactivation
register_activation_hook(__FILE__, function(){
    // create temp dir
    $upload_dir = wp_upload_dir();
    $tmp_dir = trailingslashit($upload_dir['basedir']) . 'mrs_cs_temp/';
    if (!is_dir($tmp_dir)) wp_mkdir_p($tmp_dir);

    // schedule cleanup if not scheduled
    if (!wp_next_scheduled('mrs_cs_cleanup_event')) {
        wp_schedule_event(time(), 'daily', 'mrs_cs_cleanup_event');
    }

    if (false === get_option('mrs_cs_cleanup_hours')) {
        update_option('mrs_cs_cleanup_hours', 24 * 7); // default 7 days
    }
});

register_deactivation_hook(__FILE__, function(){
    $timestamp = wp_next_scheduled('mrs_cs_cleanup_event');
    if ($timestamp) wp_unschedule_event($timestamp, 'mrs_cs_cleanup_event');
});

// Include core pieces
require_once MRS_CS_PATH . 'core/template.php';
require_once MRS_CS_PATH . 'core/ajax.php';
require_once MRS_CS_PATH . 'core/cleanup.php';

// admin pages
require_once MRS_CS_PATH . 'admin/menu.php';

// load modules (controllers)
foreach (glob(MRS_CS_PATH . 'modules/*', GLOB_ONLYDIR) as $dir) {
    $controller = $dir . '/controller.php';
    if (file_exists($controller)) include_once $controller;
}

// assets
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('mrs-cs-frontend', MRS_CS_URL . 'assets/css/style.css', [], MRS_CS_VERSION);
    wp_enqueue_script('mrs-cs-frontend', MRS_CS_URL . 'assets/js/frontend.js', ['jquery'], MRS_CS_VERSION, true);
    wp_localize_script('mrs-cs-frontend', 'MRS_CSAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mrs_cs_nonce')
    ]);
});

// cleanup hook
add_action('mrs_cs_cleanup_event', function(){
    MRS_CS\Cleanup::run();
});

// download proxy is handled in core/ajax.php (init hook)
