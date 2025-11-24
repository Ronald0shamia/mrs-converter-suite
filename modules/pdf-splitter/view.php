<div class="mrs-tool-wrapper">
    <h2>PDF Splitter</h2>
    <form id="mrs-pdf-splitter-form" enctype="multipart/form-data">
        <?php wp_nonce_field('mrs_cs_nonce','mrs_cs_field'); ?>
        <label>PDF hochladen:</label><br/>
        <input type="file" name="file" accept="application/pdf" required /><br/><br/>
        <label>Seiten (z.B. 1-3,5,7-9). Leer = jede Seite einzeln:</label><br/>
        <input type="text" name="pages" placeholder="1-3,5" /><br/><br/>
        <button type="submit" class="mrs-btn">Splitten</button>
    </form>
    <div id="mrs-pdf-splitter-result"></div>
</div>
