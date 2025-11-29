<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_word_pdf', function(){
    return MRS_Template::load('modules/word-pdf/view');
});

add_action('wp_ajax_mrs_word_pdf_action', 'mrs_word_pdf_action');
add_action('wp_ajax_nopriv_mrs_word_pdf_action', 'mrs_word_pdf_action');

function mrs_word_pdf_action() {
    // nonce
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mrs_cs_nonce')) {
        wp_send_json_error('Nonce ungültig', 403);
    }
    require_once __DIR__ . '/processor.php';
    mrs_word_pdf_process();
}
