<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_png_webp', function(){
    return MRS_Template::load('modules/png-webp/view');
});

add_action('wp_ajax_mrs_png_webp_action', 'mrs_png_webp_action');
add_action('wp_ajax_nopriv_mrs_png_webp_action', 'mrs_png_webp_action');

function mrs_png_webp_action() {
    mrs_verify_ajax_nonce();
    require_once __DIR__ . '/processor.php';
    mrs_png_webp_process();
}
