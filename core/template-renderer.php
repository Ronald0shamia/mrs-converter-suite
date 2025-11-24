<?php
namespace MRS_CS;
if (!defined('ABSPATH')) exit;

class Template {
    public static function render($path, $vars = []) {
        if (!file_exists($path)) return '';
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
