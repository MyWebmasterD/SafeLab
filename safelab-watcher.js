jQuery(document).ready(function($) {

    const startScanButton = $('#start-scan');
    const stopScanButton = $('#stop-scan');
    const changesTable = $('#changes-table tbody');
    const statusMessage = $('#scan-status'); // Elemento per i messaggi di stato

    // Avvio scansione
    startScanButton.on('click', function() {
        statusMessage.text("Scansione in corso...");
        statusMessage.css('color', 'green');
        console.log('Scansione avviata');

        // Disabilita il pulsante di avvio e abilita il pulsante di stop
        startScanButton.prop('disabled', true);
        stopScanButton.prop('disabled', false);

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'safelab_scan_ftp'
            },
            timeout: 120000, // Imposta il timeout a 2 minuti (120000 millisecondi)
            success: function(response) {
                console.log(response); // Debug: Log del risultato della richiesta AJAX
                if (response.success) {
                    displayChanges(response.data);
                    statusMessage.text("Scansione completata.");
                } else {
                    logError(response.data || "Errore sconosciuto");
                    statusMessage.text("Errore durante la scansione.");
                    statusMessage.css('color', 'red');
                }
                // Riabilita il pulsante di avvio e disabilita il pulsante di stop
                startScanButton.prop('disabled', false);
                stopScanButton.prop('disabled', true);
            },
            error: function(xhr, status, error) {
                // Dettagli per errori AJAX, utile per debug
                console.error("Errore xhr:", xhr);
                console.error("Errore status:", status);
                console.error("Errore error:", error);
                logError("Errore AJAX: " + (error || "Errore sconosciuto"));
                statusMessage.text("Errore sconosciuto.");
                statusMessage.css('color', 'red');

                // Riabilita il pulsante di avvio e disabilita il pulsante di stop
                startScanButton.prop('disabled', false);
                stopScanButton.prop('disabled', true);
            }
        });
    });

    // Interruzione scansione
    stopScanButton.on('click', function() {
        stopScanButton.prop('disabled', true); // Disabilita il pulsante di stop
        startScanButton.prop('disabled', false); // Riabilita il pulsante di avvio
        statusMessage.text("Scansione fermata."); // Messaggio di stato
        statusMessage.css('color', 'red');
    });


    // Funzione per visualizzare le modifiche
    function displayChanges(fileList) {
        changesTable.empty(); // Svuota la tabella prima di aggiornarla
        if (fileList.length > 0) {
            fileList.forEach(file => {
                const row = `<tr><td>${file}</td><td>Modificato</td></tr>`;
                changesTable.append(row);
            });
        } else {
            changesTable.append("<tr><td colspan='2'>Nessuna modifica rilevata.</td></tr>");
        }
    }

    // Funzione per loggare errori
    function logError(message) {
        alert("Errore: " + message);
    }

    // Pulsante per cancellare il log
    $('#safelab-clear-log').on('click', function() {
        // Cambia il testo del pulsante per indicare che l'azione è in corso
        $('#safelab-clear-log-status').text("Cancellazione in corso...");

        // Esegui la richiesta Ajax
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'safelab_clear_log'
            },
            success: function(response) {
                if (response.success) {
                    $('#safelab-clear-log-status').text("Log cancellato con successo.");
                } else {
                    $('#safelab-clear-log-status').text("Errore: " + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#safelab-clear-log-status').text("Errore Ajax: " + error);
            }
        });
    });


});
