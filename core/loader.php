<?php
namespace MRS_CS;
if (!defined('ABSPATH')) exit;

class Loader {
    public static function init() {
        // template renderer
        require_once MRS_CS_PATH . 'core/template-renderer.php';

        // admin menu
        if (is_admin()) {
            require_once MRS_CS_PATH . 'admin/menu.php';
        }

        // Include all module controllers
        foreach (glob(MRS_CS_PATH . 'modules/*', GLOB_ONLYDIR) as $dir) {
            $controller = $dir . '/controller.php';
            if (file_exists($controller)) {
                include_once $controller;
            }
        }
    }
}
