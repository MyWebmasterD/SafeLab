<?php
/*
Plugin Name: SafeLab Monitor
Description: Plugin per monitorare file e database di siti WordPress per ragioni di sicurezza.
Version: 1.0
Author: Ocean Digitals
*/

// Evita accessi diretti
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

function safelab_enqueue_scripts($hook) {
    if ($hook !== 'safelab_page_safelab_ftp') return;
    wp_enqueue_script('safelab-watcher', plugin_dir_url(__FILE__) . 'safelab-watcher.js', ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'safelab_enqueue_scripts');

// Aggiunge le voci di menu
function safelab_add_admin_menu() {
    add_menu_page(
        'SafeLab',
        'SafeLab',
        'manage_options',
        'safelab_main',
        'safelab_main_page',
        'dashicons-shield',
        6
    );

    add_submenu_page(
        'safelab_main',
        'FTP Watcher',
        'FTP Watcher',
        'manage_options',
        'safelab_ftp',
        'safelab_ftp_page'
    );

    add_submenu_page(
        'safelab_main',
        'Settings',
        'Settings',
        'manage_options',
        'safelab_settings',
        'safelab_settings_page'
    );
}
add_action( 'admin_menu', 'safelab_add_admin_menu' );

// Funzione per la pagina principale
function safelab_main_page() {
    echo '<div class="wrap"><h1>SafeLab Monitor</h1><p>Benvenuto nel pannello di controllo di SafeLab. Usa il menu per navigare.</p></div>';
}

// Funzione per la pagina FTP Watcher
function safelab_ftp_page() {
    ?>
    <div class="wrap">
    <h1>FTP Watcher</h1>
    <button id="start-scan" class="button button-primary">Avvia Scansione</button>
    <button id="stop-scan" class="button button-secondary" disabled>Ferma Scansione</button>
    <p id="scan-status"></p> <!-- Aggiungi questo paragrafo per i messaggi di stato -->
    <h2>Modifiche Rilevate</h2>
    <table id="changes-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>File</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="2">Nessuna modifica rilevata.</td></tr>
        </tbody>
    </table>
</div>

    <?php
}

// Definisci il percorso del file di log
define('SAFELAB_LOG_FILE', plugin_dir_path(__FILE__) . 'safelab_log.txt');

// Crea il file di log se non esiste
function safelab_create_log_file() {
    if (!file_exists(SAFELAB_LOG_FILE)) {
        file_put_contents(SAFELAB_LOG_FILE, "Log di SafeLab Monitor\n");
    }
}
register_activation_hook(__FILE__, 'safelab_create_log_file');

// Funzione per la pagina Settings
// Funzione per la pagina delle impostazioni
function safelab_settings_page() {
    // Salva le impostazioni se il form è stato inviato
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['safelab_save_settings'])) {
        $ftp_host = sanitize_text_field($_POST['ftp_host']);
        $ftp_user = sanitize_text_field($_POST['ftp_user']);
        $ftp_pass = sanitize_text_field($_POST['ftp_pass']);
        $ftp_port = sanitize_text_field($_POST['ftp_port']);
        $scan_interval = isset($_POST['scan_interval']) ? absint($_POST['scan_interval']) : 5;

        // Salva i dati nel database
        update_option('safelab_ftp_host', $ftp_host);
        update_option('safelab_ftp_user', $ftp_user);
        update_option('safelab_ftp_pass', $ftp_pass);
        update_option('safelab_ftp_port', $ftp_port);
        update_option('safelab_scan_interval', $scan_interval);

        // Test della connessione FTP
        $conn_id = ftp_connect($ftp_host, $ftp_port);
        if ($conn_id && ftp_login($conn_id, $ftp_user, $ftp_pass)) {
            echo '<div class="notice notice-success is-dismissible"><p>Connessione FTP riuscita.</p></div>';
            ftp_close($conn_id);
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Connessione FTP fallita. Verifica le credenziali.</p></div>';
        }
    }

    // Leggi le impostazioni attuali
    $ftp_host = get_option('safelab_ftp_host', '');
    $ftp_user = get_option('safelab_ftp_user', '');
    $ftp_pass = get_option('safelab_ftp_pass', '');
    $ftp_port = get_option('safelab_ftp_port', '21'); // Porta di default 21
    $scan_interval = get_option('safelab_scan_interval', 5); // Default a 5 minuti

    // Leggi il contenuto del file di log
    $log_content = file_exists(SAFELAB_LOG_FILE) ? file_get_contents(SAFELAB_LOG_FILE) : 'Il file di log è vuoto.';

    // Form HTML per le impostazioni
    echo '<div class="wrap">';
    echo '<h1>Impostazioni di SafeLab</h1>';
    echo '<form method="POST">';
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="ftp_host">FTP Host</label></th>';
    echo '<td><input name="ftp_host" type="text" id="ftp_host" value="' . esc_attr($ftp_host) . '" class="regular-text"></td></tr>';
    echo '<tr><th scope="row"><label for="ftp_user">FTP Username</label></th>';
    echo '<td><input name="ftp_user" type="text" id="ftp_user" value="' . esc_attr($ftp_user) . '" class="regular-text"></td></tr>';
    echo '<tr><th scope="row"><label for="ftp_pass">FTP Password</label></th>';
    echo '<td><input name="ftp_pass" type="password" id="ftp_pass" value="' . esc_attr($ftp_pass) . '" class="regular-text"></td></tr>';
    echo '<tr><th scope="row"><label for="ftp_port">FTP Port</label></th>';
    echo '<td><input name="ftp_port" type="number" id="ftp_port" value="' . esc_attr($ftp_port) . '" class="small-text"></td></tr>';

    // Aggiungi l'input per l'intervallo di scansione
    echo '<tr><th scope="row"><label for="scan_interval">Intervallo di Scansione (minuti)</label></th>';
    echo '<td><input name="scan_interval" type="number" id="scan_interval" value="' . esc_attr($scan_interval) . '" class="small-text" min="1"></td></tr>';

    echo '</table>';
    echo '<p class="submit"><button type="submit" name="safelab_save_settings" class="button button-primary">Salva Impostazioni</button></p>';
    echo '</form>';

    // Testo per visualizzare il contenuto del file di log
    echo '<h2>File di Log</h2>';
    echo '<textarea readonly rows="10" class="large-text">' . esc_textarea($log_content) . '</textarea>';
    echo '</div>';
}

// Aggiungi una funzione per restituire l'intervallo di scansione
function safelab_get_scan_interval() {
    $scan_interval = get_option('safelab_scan_interval', 5); // Ottieni il valore salvato
    wp_send_json_success($scan_interval); // Invia la risposta AJAX con il valore
}
add_action('wp_ajax_get_scan_interval', 'safelab_get_scan_interval');


?>
