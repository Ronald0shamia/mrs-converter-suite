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

class MRS_Converter_Suite {

    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    private function define_constants() {
        define('MRS_CONVERTER_PATH', plugin_dir_path(__FILE__));
        define('MRS_CONVERTER_URL', plugin_dir_url(__FILE__));
        define('MRS_CONVERTER_VERSION', '1.0.0');
    }

    private function includes() {
        // Core
        require_once MRS_CONVERTER_PATH . 'core/template.php';
        require_once MRS_CONVERTER_PATH . 'core/ajax.php';
        require_once MRS_CONVERTER_PATH . 'core/cleanup.php';

        // Admin
        require_once MRS_CONVERTER_PATH . 'admin/menu.php';
        require_once MRS_CONVERTER_PATH . 'admin/settings.php';

        // Modules (Tools)
        $this->load_modules();
    }

    private function load_modules() {
        $modules = [
            'word-pdf',
            'pdf-merger',
            'pdf-splitter',
            'png-webp'
        ];

        foreach ($modules as $tool) {
            $module_path = MRS_CONVERTER_PATH . "modules/$tool/";
            if (file_exists($module_path . 'controller.php')) {
                require_once $module_path . 'controller.php';
                require_once $module_path . 'view.php';
                require_once $module_path . 'processor.php';
            }
        }
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'load_assets']);
    }

    public function load_assets() {
        wp_enqueue_style('mrs-converter-style', MRS_CONVERTER_URL . 'assets/css/style.css', [], MRS_CONVERTER_VERSION);
        wp_enqueue_script('mrs-converter-script', MRS_CONVERTER_URL . 'assets/js/frontend.js', ['jquery'], MRS_CONVERTER_VERSION, true);
        
        wp_localize_script('mrs-converter-script', 'mrs_converter_ajax', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
}

new MRS_Converter_Suite();