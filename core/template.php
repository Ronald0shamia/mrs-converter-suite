<?php
if (!defined('ABSPATH')) exit;

class MRS_Template {

    public static function load($template, $data = []) {
        $template = ltrim((string) $template, '/\\');
        $file = MRS_CONVERTER_PATH . $template . '.php';

        if (!file_exists($file)) {
            return '<p class="mrs-error">' . esc_html__('Template nicht gefunden.', 'mrs-converter-suite') . '</p>';
        }

        ob_start();
        if (!empty($data) && is_array($data)) {
            extract($data, EXTR_SKIP);
        }
        include $file;

        return ob_get_clean();
    }

}
