<?php
if (!defined('ABSPATH')) exit;

class MRS_Settings {

    public function __construct() {
        add_action('admin_init', [$this, 'register']);
    }

    public function register() {
        register_setting('mrs_settings_group', 'mrs_tools_enabled');
    }
}

new MRS_Settings();
