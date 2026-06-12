<?php
if (!defined('ABSPATH')) exit;

add_shortcode('mrs_pdf_splitter', function(){
    return MRS_Template::load('modules/pdf-splitter/view');
});

add_action('wp_ajax_mrs_pdf_splitter_action', 'mrs_pdf_splitter_action');
add_action('wp_ajax_nopriv_mrs_pdf_splitter_action', 'mrs_pdf_splitter_action');

function mrs_pdf_splitter_action() {
    mrs_verify_ajax_nonce();
    require_once __DIR__ . '/processor.php';
    mrs_pdf_splitter_process();
}
