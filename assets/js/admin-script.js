jQuery(document).ready(function($) {
    // Seleziona tutti gli articoli
    $('#eas-select-all-posts').on('change', function() {
        var checked = this.checked;
        $('input[name="selected_posts[]"]').each(function() {
            this.checked = checked;
        });
    });

    // Seleziona tutte le liste
    $('#eas-select-all-lists').on('change', function() {
        var checked = this.checked;
        $('input[name="list_ids[]"]').each(function() {
            this.checked = checked;
        });
    });

    $('#eas-send-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var formData = form.serialize();

        $('#eas-progress-wrap').show();

        $.ajax({
            url: eas_ajax_object.ajax_url,
            type: 'POST',
            data: formData + '&security=' + eas_ajax_object.security,
            success: function(response) {
                if (response.success) {
                    var totalBatches = response.data.total_batches;
                    var completedBatches = 0;

                    // Simula l'avanzamento
                    var progressInterval = setInterval(function() {
                        completedBatches++;
                        var progress = (completedBatches / totalBatches) * 100;
                        $('#eas-progress').css('width', progress + '%');
                        $('#eas-progress-text').text(Math.round(progress) + '% completato');

                        if (completedBatches >= totalBatches) {
                            clearInterval(progressInterval);
                            $('#eas-progress-text').text('Invio completato!');
                        }
                    }, 1000); // Aggiorna ogni secondo
                } else {
                    alert('Errore durante l\'invio degli articoli.');
                }
            },
            error: function() {
                alert('Errore durante la richiesta Ajax.');
            }
        });
    });
});
