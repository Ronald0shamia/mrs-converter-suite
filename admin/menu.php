<?php
if (!defined('ABSPATH')) exit;

// add menu + settings link
add_action('admin_menu', function(){
    add_menu_page('MRS Converter Suite', 'MRS Converter', 'manage_options', 'mrs-cs', 'mrs_cs_admin_page', 'dashicons-admin-tools', 56);
    add_submenu_page('mrs-cs', 'Settings', 'Settings', 'manage_options', 'mrs-cs-settings', 'mrs_cs_settings_page');
});

function mrs_cs_admin_page() {
    ?>
    <div class="wrap">
        <h1>MRS Converter Suite</h1>
        <p>Shortcodes:</p>
        <ul>
            <li>Word → PDF: <code>[mrs_word_pdf]</code></li>
            <li>PDF Merger: <code>[mrs_pdf_merger]</code></li>
            <li>PDF Splitter: <code>[mrs_pdf_splitter]</code></li>
            <li>PNG → WEBP: <code>[mrs_png_webp]</code></li>
        </ul>
        <p>Weitere Einstellungen unter Settings → MRS Converter</p>
    </div>
    <?php
}

function mrs_cs_settings_page() {
    if (!current_user_can('manage_options')) wp_die('No');
    if (isset($_POST['mrs_cs_save_settings'])) {
        check_admin_referer('mrs_cs_settings_save','mrs_cs_settings_nonce');
        $hours = intval($_POST['mrs_cs_cleanup_hours']);
        update_option('mrs_cs_cleanup_hours', $hours);
        echo '<div class="updated"><p>Einstellungen gespeichert.</p></div>';
    }
    $hours = intval(get_option('mrs_cs_cleanup_hours', 24*7));
    ?>
    <div class="wrap">
        <h1>MRS Converter Settings</h1>
        <form method="post">
            <?php wp_nonce_field('mrs_cs_settings_save','mrs_cs_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Cleanup: lösche temp-Dateien älter als (Stunden)</th>
                    <td><input type="number" name="mrs_cs_cleanup_hours" value="<?php echo esc_attr($hours); ?>" /></td>
                </tr>
            </table>
            <p><input type="submit" name="mrs_cs_save_settings" class="button button-primary" value="Save Settings" /></p>
        </form>
    </div>
    <?php
}
