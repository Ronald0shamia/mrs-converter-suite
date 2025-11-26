<?php if (!defined('ABSPATH')) exit; ?>
<div class="mrs-tool-wrapper">
    <h2>PDF Splitter</h2>

    <form class="mrs-form" data-action="mrs_pdf_splitter" enctype="multipart/form-data">
        <div class="mrs-dropzone"><p>Ziehe deine PDF hierher</p></div>
        <input type="file" name="file" accept="application/pdf" />
        <label>Seiten (z.B. 1-3,5). Leer = alle Seiten einzeln:</label>
        <input type="text" name="pages" placeholder="1-3,5" />
        <div class="mrs-progress-wrap"><div class="mrs-progress"></div></div>
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mrs_cs_nonce')); ?>">
        <button type="submit" class="mrs-btn">Splitten</button>
    </form>

    <div class="mrs-result"></div>
</div>
