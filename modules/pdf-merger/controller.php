<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_pdf_merger', function(){
    return \MRS_CS\Template::render(MRS_CS_PATH . 'modules/pdf-merger/view.php');
});

add_action('wp_ajax_mrs_pdf_merger', 'mrs_pdf_merger_ajax');
add_action('wp_ajax_nopriv_mrs_pdf_merger', 'mrs_pdf_merger_ajax');

function mrs_pdf_merger_ajax() {
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
    require_once MRS_CS_PATH . 'modules/pdf-merger/processor.php';
    mrs_pdf_merger_process();
}
