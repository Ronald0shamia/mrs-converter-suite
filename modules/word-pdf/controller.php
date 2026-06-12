<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_word_pdf', function(){
    return MRS_Template::load('modules/word-pdf/view');
});

add_action('wp_ajax_mrs_word_pdf_action', 'mrs_word_pdf_action');
add_action('wp_ajax_nopriv_mrs_word_pdf_action', 'mrs_word_pdf_action');

function mrs_word_pdf_action() {
    mrs_verify_ajax_nonce();
    require_once __DIR__ . '/processor.php';
    mrs_word_pdf_process();
}
