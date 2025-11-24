<div class="mrs-tool-wrapper">
    <h2>Word → PDF Converter</h2>
    <form id="mrs-word-pdf-form" enctype="multipart/form-data">
        <?php wp_nonce_field('mrs_cs_nonce','mrs_cs_field'); ?>
        <label>Datei (.doc, .docx):</label><br/>
        <input type="file" name="file" accept=".doc,.docx" required /><br/><br/>
        <label>PDF Qualität:
            <select name="quality">
                <option value="standard">Standard</option>
                <option value="high">Hoch</option>
            </select>
        </label><br/><br/>
        <button type="submit" class="mrs-btn">PDF generieren</button>
    </form>
    <div id="mrs-word-pdf-result"></div>
</div>
