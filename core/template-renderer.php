<?php
namespace MRS_CS;
if (!defined('ABSPATH')) exit;

class Template {
    /**
     * Renders PHP file and returns content
     * @param string $path absolute path
     * @param array $vars variables to extract into template
     * @return string
     */
    public static function render(string $path, array $vars = []) : string {
        if (!file_exists($path)) return '';
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
