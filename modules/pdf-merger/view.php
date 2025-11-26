<?php if (!defined('ABSPATH')) exit; ?>
<div class="mrs-tool-wrapper">
    <h2>PDF Merger</h2>

    <form class="mrs-form" data-action="mrs_pdf_merger" enctype="multipart/form-data">
        <div class="mrs-dropzone"><p>Ziehe mehrere PDFs hierher</p></div>
        <input type="file" name="files[]" accept="application/pdf" multiple />
        <div class="mrs-progress-wrap"><div class="mrs-progress"></div></div>
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mrs_cs_nonce')); ?>">
        <button type="submit" class="mrs-btn">Zusammenführen</button>
    </form>

    <div class="mrs-result"></div>
</div>
