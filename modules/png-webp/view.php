<div class="mrs-tool-wrapper">
    <h2>PNG → WEBP Converter</h2>
    <form id="mrs-png-webp-form" enctype="multipart/form-data">
        <?php wp_nonce_field('mrs_cs_nonce','mrs_cs_field'); ?>
        <label>Dateien (PNG, JPG) hochladen (mehrere möglich):</label><br/>
        <input type="file" name="files[]" accept="image/png,image/jpeg" multiple required /><br/><br/>
        <label>Qualität (0-100): <input type="number" name="quality" min="0" max="100" value="80" /></label><br/><br/>
        <label><input type="checkbox" name="keep_original_name" value="1" checked /> Original-Dateiname behalten (Suffix .webp)</label><br/><br/>
        <button type="submit" class="mrs-btn">Konvertieren → WEBP</button>
    </form>
    <div id="mrs-png-webp-result"></div>
</div>
