jQuery(document).ready(function($) {
    let scanInterval;
    const startScanButton = $('#start-scan');
    const stopScanButton = $('#stop-scan');
    const changesTable = $('#changes-table tbody');
    const statusMessage = $('#scan-status'); // Elemento per i messaggi di stato

    startScanButton.on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'safelab_scan_ftp'
            },
            timeout: 300000, // Imposta il timeout a 5 minuti (300.000 millisecondi)
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
                console.error("Errore xhr:", xhr);
                console.error("Errore status:", status);
                console.error("Errore error:", error);
                logError("Errore AJAX: " + (error || "Errore sconosciuto"));
                clearInterval(scanInterval);
                statusMessage.text("Errore sconosciuto.");
                statusMessage.css('color', 'red');
            }
        });
    });

    stopScanButton.on('click', function() {
        clearInterval(scanInterval);
        stopScanButton.prop('disabled', true);
        startScanButton.prop('disabled', false);
        statusMessage.text("Scansione fermata."); // Messaggio di stato
        statusMessage.css('color', 'red');
    });

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

    function logError(message) {
        alert("Errore: " + message);
    }
});
