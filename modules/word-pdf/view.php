<?php if (!defined('ABSPATH')) exit; ?>
<div class="mrs-tool-wrapper">
    <h2>Word → PDF Converter</h2>

    <form class="mrs-form" data-action="mrs_word_pdf" enctype="multipart/form-data">
        <div class="mrs-dropzone"><p>Ziehe hier dein .doc/.docx</p></div>
        <input type="file" name="file" accept=".doc,.docx" />
        <div class="mrs-settings">
            <label>Qualität:
                <select name="quality">
                    <option value="standard">Standard</option>
                    <option value="high">Hoch</option>
                </select>
            </label>
        </div>
        <div class="mrs-progress-wrap"><div class="mrs-progress"></div></div>
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mrs_cs_nonce')); ?>">
        <button type="submit" class="mrs-btn">PDF generieren</button>
    </form>

    <div class="mrs-result"></div>
</div>
