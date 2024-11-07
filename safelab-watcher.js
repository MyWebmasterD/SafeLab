jQuery(document).ready(function($) {
    let scanInterval;
    const startScanButton = $('#start-scan');
    const stopScanButton = $('#stop-scan');
    const changesTable = $('#changes-table tbody');
    const statusMessage = $('#scan-status'); // Elemento per i messaggi di stato

    // Avvia la scansione
    startScanButton.on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_scan_interval' // Aggiungi un'azione per recuperare l'intervallo
            },
            success: function(response) {
                if (response.success) {
                    const intervalMinutes = parseInt(response.data) || 5; // Usa l'intervallo salvato
                    startScan(intervalMinutes * 60000); // Avvia la scansione
                } else {
                    alert('Errore nel recupero dell\'intervallo di scansione');
                }
            },
            error: function() {
                alert('Errore durante il recupero dell\'intervallo');
            }
        });
    });

    // Ferma la scansione
    stopScanButton.on('click', function() {
        clearInterval(scanInterval);
        stopScanButton.prop('disabled', true);
        startScanButton.prop('disabled', false);
        statusMessage.text("Scansione fermata."); // Messaggio di stato
        statusMessage.css('color', 'red');
    });

    // Funzione per avviare la scansione a intervalli regolari
    function startScan(interval) {
        statusMessage.text("Scansione in corso...");
        statusMessage.css('color', 'green');
        
        scanInterval = setInterval(function() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'safelab_scan_ftp'
                },
                success: function(response) {
                    console.log(response); // Debug: Log del risultato della richiesta AJAX
                    if (response.success) {
                        displayChanges(response.data);
                        statusMessage.text("Scansione completata.");
                    } else {
                        logError(response.data || "Errore sconosciuto");
                        clearInterval(scanInterval);
                        statusMessage.text("Errore durante la scansione.");
                        statusMessage.css('color', 'red');
                    }
                },
                error: function(xhr, status, error) {
                    // Dettagli per errori AJAX, utile per debug
                    console.error("Errore AJAX:", error, xhr);
                    logError("Errore AJAX: " + (error || "Errore sconosciuto"));
                    clearInterval(scanInterval);
                    statusMessage.text("Errore sconosciuto.");
                    statusMessage.css('color', 'red');
                }
            });
        }, interval);

        stopScanButton.prop('disabled', false);
        startScanButton.prop('disabled', true);
    }

    // Funzione per visualizzare i cambiamenti nella tabella
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

    // Funzione per loggare gli errori e mostrarli all'utente
    function logError(message) {
        alert("Errore: " + message);
    }
});
