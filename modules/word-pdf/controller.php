<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_word_pdf', function(){
    return \MRS_CS\Template::render(MRS_CS_PATH . 'modules/word-pdf/view.php');
});

add_action('wp_ajax_mrs_word_pdf', 'mrs_word_pdf_ajax');
add_action('wp_ajax_nopriv_mrs_word_pdf', 'mrs_word_pdf_ajax');

function mrs_word_pdf_ajax() {
    // nonce
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
    require_once MRS_CS_PATH . 'modules/word-pdf/processor.php';
    mrs_word_pdf_process();
}
