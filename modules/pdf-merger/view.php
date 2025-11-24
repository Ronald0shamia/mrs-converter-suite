<div class="mrs-tool-wrapper">
    <h2>PDF Merger</h2>
    <form id="mrs-pdf-merger-form" enctype="multipart/form-data">
        <?php wp_nonce_field('mrs_cs_nonce','mrs_cs_field'); ?>
        <label>PDF Dateien (mehrere):</label><br/>
        <input type="file" name="files[]" accept="application/pdf" multiple required /><br/><br/>
        <button type="submit" class="mrs-btn">Zusammenführen</button>
    </form>
    <div id="mrs-pdf-merger-result"></div>
</div>
