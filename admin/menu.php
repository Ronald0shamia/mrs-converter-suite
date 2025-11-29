<?php
if (!defined('ABSPATH')) exit;

class MRS_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
    }

    public function menu() {
        add_menu_page(
            'MRS Converter Suite',
            'MRS Converter',
            'manage_options',
            'mrs-converter-suite',
            [$this, 'dashboard_page'],
            'dashicons-admin-tools'
        );
    }

    public function dashboard_page() {
        echo '<div class="wrap"><h1>MRS Converter Suite</h1>';
        echo '<p>Hier kannst du Tools verwalten und Einstellungen anpassen.</p>';
        echo '</div>';
    }
}

new MRS_Admin_Menu();
