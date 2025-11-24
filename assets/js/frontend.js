jQuery(document).ready(function($){
    $('form[id^="mrs-"]').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var id = $form.attr('id'); // e.g. mrs-word-pdf-form
        var action = id.replace(/^mrs-/, '').replace(/-form$/, '').replace(/-/g, '_'); // word_pdf
        var formData = new FormData(this);
        formData.append('action', 'mrs_' + action);
        formData.append('nonce', MRS_CSAjax.nonce);

        var resultSelector = '#mrs-' + action.replace(/_/g,'-') + '-result';
        var $result = $(resultSelector);
        if ($result.length === 0) {
            $result = $form.find('div[id$="-result"]').first();
        }

        $result.html('<em>Bitte warten…</em>');

        $.ajax({
            url: MRS_CSAjax.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res){
                if (res && res.success) {
                    $result.html('<a href="'+res.data.url+'" target="_blank" rel="noopener">Datei herunterladen</a>');
                } else {
                    var msg = (res && res.data) ? res.data : 'Unbekannter Fehler';
                    $result.html('<span style="color:#a00">Fehler: ' + msg + '</span>');
                }
            },
            error: function(xhr){
                $result.html('<span style="color:#a00">Fehler: ' + xhr.statusText + '</span>');
            }
        });
    });
});
