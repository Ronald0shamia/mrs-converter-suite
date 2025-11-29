// assets/js/frontend.js
jQuery(function($){
    var ajaxUrl = (typeof mrs_converter_ajax !== 'undefined' && mrs_converter_ajax.ajax_url) ? mrs_converter_ajax.ajax_url : (typeof MRS_CSAjax !== 'undefined' ? MRS_CSAjax.ajax_url : '/wp-admin/admin-ajax.php');
    var ajaxNonce = (typeof MRS_CSAjax !== 'undefined') ? MRS_CSAjax.nonce : '';

    function initDropzone($root) {
        var $dz = $root.find('.mrs-dropzone');
        var $file = $root.find('input[type=file]');
        if (!$dz.length || !$file.length) return;
        $dz.on('click', function(){ $file.trigger('click'); });
        $dz.on('dragover', function(e){ e.preventDefault(); $(this).addClass('dragover'); });
        $dz.on('dragleave', function(){ $(this).removeClass('dragover'); });
        $dz.on('drop', function(e){
            e.preventDefault();
            $(this).removeClass('dragover');
            var files = e.originalEvent.dataTransfer.files;
            $file[0].files = files;
            $dz.find('p').text(files.length > 1 ? files.length + ' Dateien ausgewählt' : files[0].name);
        });
    }

    $('.mrs-form').each(function(){
        var $form = $(this);
        initDropzone($form);

        $form.on('submit', function(e){
            e.preventDefault();
            var $progress = $form.find('.mrs-progress');
            var $result = $form.closest('.mrs-tool-wrapper').find('.mrs-result').first();
            var action = $form.data('action') || $form.attr('id') || '';
            var fd = new FormData(this);
            fd.append('action', action);
            // append nonce if not present
            if (!fd.get('nonce') && ajaxNonce) fd.append('nonce', ajaxNonce);

            $result.html('<em>Bitte warten…</em>');
            $progress.css('width','0%');

            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                data: fd,
                contentType: false,
                processData: false,
                xhr: function(){
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e){
                        if (e.lengthComputable) {
                            var percent = Math.round(e.loaded / e.total * 100);
                            $progress.css('width', percent + '%');
                        }
                    });
                    return xhr;
                },
                success: function(res) {
                    if (res && res.success) {
                        var url = res.data.url || res.data;
                        $result.html('<a href="'+url+'" target="_blank" rel="noopener">Datei herunterladen</a>');
                    } else {
                        var msg = (res && res.data) ? res.data : (res && res.message) ? res.message : 'Unbekannter Fehler';
                        $result.html('<div class="mrs-error">Fehler: ' + msg + '</div>');
                    }
                },
                error: function(xhr) {
                    var msg = xhr.statusText || 'Netzwerkfehler';
                    $result.html('<div class="mrs-error">Fehler: ' + msg + '</div>');
                }
            });
        });
    });
});
