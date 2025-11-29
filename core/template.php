<?php
if (!defined('ABSPATH')) exit;

class MRS_Template {

    public static function load($template, $data = []) {
        $file = MRS_CONVERTER_PATH . 'templates/' . $template . '.php';
        
        if (file_exists($file)) {
            extract($data);
            include $file;
        } else {
            echo "Template not found: " . $template;
        }
    }

}
