<?php if (!defined('ABSPATH')) exit; ?>
<div class="mrs-tool-wrapper">
    <h2>PNG → WEBP Converter</h2>

    <form class="mrs-form" data-action="mrs_png_webp" enctype="multipart/form-data">
        <div class="mrs-dropzone"><p>Ziehe PNG/JPG hierher (mehrere möglich)</p></div>
        <input type="file" name="files[]" accept="image/png,image/jpeg" multiple />
        <label>Qualität (0-100): <input type="number" name="quality" min="0" max="100" value="80" /></label>
        <div class="mrs-progress-wrap"><div class="mrs-progress"></div></div>
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mrs_cs_nonce')); ?>">
        <button type="submit" class="mrs-btn">Konvertieren → WEBP</button>
    </form>

    <div class="mrs-result"></div>
</div>
