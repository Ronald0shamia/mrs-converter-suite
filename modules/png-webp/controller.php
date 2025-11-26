<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_png_webp', function(){
    return \MRS_CS\Template::render(MRS_CS_PATH . 'modules/png-webp/view.php');
});

add_action('wp_ajax_mrs_png_webp', 'mrs_png_webp_ajax');
add_action('wp_ajax_nopriv_mrs_png_webp', 'mrs_png_webp_ajax');

function mrs_png_webp_ajax() {
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
    require_once MRS_CS_PATH . 'modules/png-webp/processor.php';
    mrs_png_webp_process();
}
