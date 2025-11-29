<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_png_webp', function(){
    return MRS_Template::load('modules/png-webp/view');
});

add_action('wp_ajax_mrs_png_webp_action', 'mrs_png_webp_action');
add_action('wp_ajax_nopriv_mrs_png_webp_action', 'mrs_png_webp_action');

function mrs_png_webp_action() {
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
    require_once __DIR__ . '/processor.php';
    mrs_png_webp_process();
}
