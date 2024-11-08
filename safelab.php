<?php
/*
Plugin Name: SafeLab Monitor
Description: Plugin per monitorare file e database di siti WordPress per ragioni di sicurezza.
Version: 1.0
Author: Ocean Digitals
*/

// Evita accessi diretti
if (!defined('ABSPATH')) {
    exit;
}

/* Enqueue Script */
function safelab_enqueue_scripts($hook)
{
    if ($hook !== 'safelab_page_safelab_ftp' && $hook !== 'safelab_page_safelab_settings')
        return;
    wp_enqueue_script('safelab-watcher', plugin_dir_url(__FILE__) . 'safelab-watcher.js', ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'safelab_enqueue_scripts');

/* Aggiunge le voci di menu WP */
function safelab_add_admin_menu()
{
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
add_action('admin_menu', 'safelab_add_admin_menu');

/* Pagina Principale */
function safelab_main_page()
{
    echo '<div class="wrap"><h1>SafeLab Monitor</h1><p>Benvenuto nel pannello di controllo di SafeLab. Usa il menu per navigare.</p></div>';
}

/*  Pagina FTP Watcher
    HTML con pulsanti di chiamata routine
 */
function safelab_ftp_page()
{
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
                <tr>
                    <td colspan="2">Nessuna modifica rilevata.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php
}

/*  Pagina di Settings 
    Definisce connessione e test ftp
    Visualizzazione File di Log
*/
function safelab_settings_page()
{
    // Salva le impostazioni se il form è stato inviato
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['safelab_save_settings'])) {
        $ftp_host = sanitize_text_field($_POST['ftp_host']);
        $ftp_user = sanitize_text_field($_POST['ftp_user']);
        $ftp_pass = sanitize_text_field($_POST['ftp_pass']);
        $ftp_port = sanitize_text_field($_POST['ftp_port']);
        $scan_directory = sanitize_text_field($_POST['scan_directory']);
        //$scan_interval = isset($_POST['scan_interval']) ? absint($_POST['scan_interval']) : 5;

        // Salva i dati nel database
        update_option('safelab_ftp_host', $ftp_host);
        update_option('safelab_ftp_user', $ftp_user);
        update_option('safelab_ftp_pass', $ftp_pass);
        update_option('safelab_ftp_port', $ftp_port);
        update_option('scan_directory', $scan_directory);
        //update_option('safelab_scan_interval', $scan_interval);

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
    $scan_directory = get_option('scan_directory', '');
    //$scan_interval = get_option('safelab_scan_interval', 5); // Default a 5 minuti

    // Leggi il contenuto del file di log
    $log_content = file_exists(SAFELAB_LOG_FILE) ? file_get_contents(SAFELAB_LOG_FILE) : 'Il file di log è vuoto.';

    // Form HTML per le impostazioni
    ?>
    <div class="wrap">
        <h1>Impostazioni di SafeLab</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ftp_host">FTP Host</label></th>
                    <td><input name="ftp_host" type="text" id="ftp_host" value="<?php echo esc_attr($ftp_host); ?>"
                            class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ftp_user">FTP Username</label></th>
                    <td><input name="ftp_user" type="text" id="ftp_user" value="<?php echo esc_attr($ftp_user); ?>"
                            class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ftp_pass">FTP Password</label></th>
                    <td><input name="ftp_pass" type="password" id="ftp_pass" value="<?php echo esc_attr($ftp_pass); ?>"
                            class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ftp_port">FTP Port</label></th>
                    <td><input name="ftp_port" type="number" id="ftp_port" value="<?php echo esc_attr($ftp_port); ?>"
                            class="small-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Percorso Directory Iniziale</th>
                    <td>
                        <input type="text" name="scan_directory" value="<?php echo esc_attr(get_option('scan_directory', '/')); ?>" />
                        <p class="description">Inserisci la directory di partenza per la scansione (default: directory di root).</p>
                    </td>
                </tr>
            </table>
            <p class="submit"><button type="submit" name="safelab_save_settings" class="button button-primary">Salva
                    Impostazioni</button></p>
        </form>

        <h2>File di Log</h2>
        <textarea readonly rows="10" class="large-text"><?php echo esc_textarea($log_content); ?></textarea>
    </div>
    <button id="safelab-clear-log" class="button button-secondary">Cancella Log</button>
    <div id="safelab-clear-log-status"></div>

    <?php

}


// Definisci il percorso del file di log
define('SAFELAB_LOG_FILE', plugin_dir_path(__FILE__) . 'safelab_log.txt');

// Crea il file di log se non esiste
function safelab_create_log_file()
{
    if (!file_exists(SAFELAB_LOG_FILE)) {
        file_put_contents(SAFELAB_LOG_FILE, "Log di SafeLab Monitor\n");
    }
}
register_activation_hook(__FILE__, 'safelab_create_log_file');

// Funzione per cancellare il file di log
function safelab_clear_log()
{
    $log_file = SAFELAB_LOG_FILE;

    if (file_exists($log_file)) {
        file_put_contents($log_file, "");  // Cancella il contenuto del file di log
        wp_send_json_success("Log cancellato con successo.");
    } else {
        wp_send_json_error("Il file di log non esiste.");
    }
}

// Aggiungi l'azione Ajax per gli utenti autenticati
add_action('wp_ajax_safelab_clear_log', 'safelab_clear_log');


function safelab_scan_ftp()
{

    $log_file = SAFELAB_LOG_FILE;

    // Crea il file di log se non esiste
    if (!file_exists($log_file)) {
        file_put_contents($log_file, "Log creato\n"); // Questo creerà il file di log vuoto
    }

    // Connessione FTP
    $ftp_host = get_option('safelab_ftp_host');
    $ftp_user = get_option('safelab_ftp_user');
    $ftp_pass = get_option('safelab_ftp_pass');
    $ftp_port = get_option('safelab_ftp_port', 21);

    $directory = get_option('scan_directory', '');


    $conn_id = ftp_connect($ftp_host, $ftp_port);
    if (!$conn_id) {
        file_put_contents($log_file, "Errore: impossibile connettersi a $ftp_host\n", FILE_APPEND);
        wp_send_json_error('Errore di connessione FTP');
        return;
    }

    // Modalità passiva (il server lascia che il client stabilisca la connessione e si limita a confermarla)
    ftp_pasv($conn_id, true);

    if (!ftp_login($conn_id, $ftp_user, $ftp_pass)) {
        file_put_contents($log_file, "Errore: login FTP fallito\n", FILE_APPEND);
        ftp_close($conn_id);
        wp_send_json_error('Errore di login FTP');
        return;
    }

    $directory = get_option('scan_directory', '');
    $result = [];
    $stack = [];
    $stack = [$directory]; // Inizializziamo con la directory di partenza

    file_put_contents($log_file, "Inizio della scansione su " . $directory . "...\n", FILE_APPEND);

    while (!empty($stack)) {
        $current_dir = array_pop($stack);

        // Usa ftp_rawlist invece di ftp_nlist
        $files = ftp_rawlist($conn_id, $current_dir);
        if ($files === false) {
            continue; // Se non riesce a elencare la directory, passa alla successiva
        }

        foreach ($files as $fileinfo) {
            // Analizza i dettagli del file
            $info = preg_split("/\s+/", $fileinfo);
            $file_type = $info[0][0]; // Prendi il primo carattere (- per file, d per directory)
            $file_name = end($info); // Nome del file o directory

            $filepath = $current_dir . '/' . $file_name;

            if ($file_name === '.' || $file_name === '..') {
                continue; // Ignora corrente e padre
            }

            if ($file_type === 'd') { // Directory
                $stack[] = $filepath;
            } else { // File
                $result[] = $filepath;
            }
        }
    }

    ftp_close($conn_id);
    file_put_contents($log_file, "Scansione completata\n", FILE_APPEND);
    wp_send_json_success($result);
}
add_action('wp_ajax_safelab_scan_ftp', 'safelab_scan_ftp');

// Funzione ricorsiva per ottenere la lista dei file
function safelab_list_files($ftp_conn, $dir, $log_file)
{

    $files = [];
    $contents = ftp_nlist($ftp_conn, $dir);

    $filteredContents = array_filter($contents, function ($item) {
        return !preg_match('/\/\.\.?$/', $item);
    });
    return $filteredContents;
}

function ftp_mlsd_non_recursive($ftp_stream, $directory)
{
    $result = [];
    $stack = [$directory]; // Inizializziamo lo stack con la directory di partenza

    while (!empty($stack)) {
        $current_dir = array_pop($stack);

        $files = ftp_mlsd($ftp_stream, $current_dir);
        if ($files === false) {
            die("Cannot list $current_dir");
        }

        foreach ($files as $file) {
            $name = $file["name"];
            $filepath = $current_dir . "/" . $name;

            if ($file["type"] == "cdir" || $file["type"] == "pdir") {
                // Ignora la directory corrente e la directory padre
                continue;
            }

            if ($file["type"] == "dir") {
                // Se è una directory, aggiungila allo stack per la scansione successiva
                $stack[] = $filepath;
            } else {
                // Se è un file, aggiungilo al risultato
                $result[] = $filepath;
            }
        }
    }

    return $result;
}

function ftp_nlist_non_recursive($ftp_stream, $directory)
{
    $result = [];
    $stack = [$directory];

    while (!empty($stack)) {
        $current_dir = array_pop($stack);

        $files = ftp_nlist($ftp_stream, $current_dir);
        if ($files === false) {
            continue; // Salta se non riesce a elencare i file
        }

        foreach ($files as $filepath) {
            // Verifica se è una directory
            if (ftp_size($ftp_stream, $filepath) == -1) {
                // È una directory, quindi aggiungila allo stack
                $stack[] = $filepath;
            } else {
                // È un file, aggiungilo al risultato
                $result[] = $filepath;
            }
        }
    }

    return $result;
}

function ftp_mlsd_recursive($ftp_stream, $directory)
{
    $result = [];

    $files = ftp_mlsd($ftp_stream, $directory);
    if ($files === false) {
        die("Cannot list $directory");
    }

    foreach ($files as $file) {
        $name = $file["name"];
        $filepath = $directory . "/" . $name;
        if (($file["type"] == "cdir") || ($file["type"] == "pdir")) {
            // noop
        } else if ($file["type"] == "dir") {
            $temp = ftp_mlsd_recursive($ftp_stream, $filepath);
            $result = array_merge($result, $temp);
        } else {
            $result[] = $filepath;
        }
    }
    return $result;
}

?>