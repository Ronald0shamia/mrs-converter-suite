<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_pdf_merger', function(){
    return MRS_Template::load('modules/pdf-merger/view');
});

add_action('wp_ajax_mrs_pdf_merger_action', 'mrs_pdf_merger_action');
add_action('wp_ajax_nopriv_mrs_pdf_merger_action', 'mrs_pdf_merger_action');

function mrs_pdf_merger_action() {
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
    require_once __DIR__ . '/processor.php';
    mrs_pdf_merger_process();
}
