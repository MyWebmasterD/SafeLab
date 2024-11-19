<?php
/*
Plugin Name: SafeLab Monitor
Description: Plugin per monitorare file e database di siti WordPress per ragioni di sicurezza.
Version: 2.0 (SSH Key Edition)
Author: Ocean Digitals
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/phpseclib_autoload.php';

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

/* Enqueue Script */
function safelab_enqueue_scripts($hook)
{
    if ($hook !== 'safelab_page_safelab_ssh' && $hook !== 'safelab_page_safelab_settings')
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
        'SSH Watcher',
        'SSH Watcher',
        'manage_options',
        'safelab_ssh',
        'safelab_ssh_page'
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

/* Pagina SSH Watcher */
function safelab_ssh_page()
{
    ?>
    <div class="wrap">
        <h1>SSH Watcher</h1>
        <button id="start-scan" class="button button-primary">Avvia Scansione</button>
        <button id="stop-scan" class="button button-secondary" disabled>Ferma Scansione</button>
        <p id="scan-status"></p>
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

/* Pagina di Settings */
function safelab_settings_page()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['safelab_save_settings'])) {
        $ssh_host = sanitize_text_field($_POST['ssh_host']);
        $ssh_user = sanitize_text_field($_POST['ssh_user']);
        $private_key = sanitize_textarea_field($_POST['private_key']);
        $scan_directory = sanitize_text_field($_POST['scan_directory']);

        update_option('safelab_ssh_host', $ssh_host);
        update_option('safelab_ssh_user', $ssh_user);
        update_option('safelab_private_key', $private_key);
        update_option('scan_directory', $scan_directory);

        // Test connessione SSH
        $ssh = new SSH2($ssh_host);
        $key = PublicKeyLoader::load($private_key);

        if ($ssh->login($ssh_user, $key)) {
            echo '<div class="notice notice-success is-dismissible"><p>Connessione SSH riuscita.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Connessione SSH fallita. Verifica le credenziali.</p></div>';
        }
    }

    $ssh_host = get_option('safelab_ssh_host', '');
    $ssh_user = get_option('safelab_ssh_user', '');
    $private_key = get_option('safelab_private_key', '');
    $scan_directory = get_option('scan_directory', '');

    ?>
    <div class="wrap">
        <h1>Impostazioni di SafeLab</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ssh_host">SSH Host</label></th>
                    <td><input name="ssh_host" type="text" id="ssh_host" value="<?php echo esc_attr($ssh_host); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ssh_user">SSH Username</label></th>
                    <td><input name="ssh_user" type="text" id="ssh_user" value="<?php echo esc_attr($ssh_user); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="private_key">Chiave Privata</label></th>
                    <td>
                        <textarea name="private_key" id="private_key" rows="10" cols="50" class="large-text"><?php echo esc_textarea($private_key); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="scan_directory">Directory di Scansione</label></th>
                    <td><input name="scan_directory" type="text" id="scan_directory" value="<?php echo esc_attr($scan_directory); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit"><button type="submit" name="safelab_save_settings" class="button button-primary">Salva Impostazioni</button></p>
        </form>
    </div>
    <?php
}

/* Scansione SSH */
function safelab_scan_ssh()
{
    $ssh_host = get_option('safelab_ssh_host');
    $ssh_user = get_option('safelab_ssh_user');
    $private_key = get_option('safelab_private_key');
    $scan_directory = get_option('scan_directory', '/');

    $ssh = new SSH2($ssh_host);
    $key = PublicKeyLoader::load($private_key);

    if (!$ssh->login($ssh_user, $key)) {
        wp_send_json_error('Errore di login SSH');
        return;
    }

    // Comando per elencare i file in modo ricorsivo
    $command = "find " . escapeshellarg($scan_directory) . " -type f";
    $file_list = $ssh->exec($command);

    if ($file_list === false) {
        wp_send_json_error('Errore durante la scansione SSH');
        return;
    }

    $files = explode("\n", trim($file_list));
    wp_send_json_success($files);
}
add_action('wp_ajax_safelab_scan_ssh', 'safelab_scan_ssh');
