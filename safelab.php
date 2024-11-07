<?php
/*
Plugin Name: SafeLab Monitor
Description: Plugin per monitorare file e database di siti WordPress per ragioni di sicurezza.
Version: 1.0
Author: Tuo Nome
*/

// Evita accessi diretti
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Aggiunge le voci di menu
function safelab_add_admin_menu() {
    // Voce principale di menu
    add_menu_page(
        'SafeLab',          // Titolo della pagina
        'SafeLab',          // Nome del menu
        'manage_options',   // Capacità necessaria
        'safelab_main',     // Slug
        'safelab_main_page',// Funzione per la pagina
        'dashicons-shield', // Icona del menu
        6                   // Posizione del menu
    );

    // Sottomenu: FTP Watcher
    add_submenu_page(
        'safelab_main',     // Slug del menu principale
        'FTP Watcher',      // Titolo della pagina
        'FTP Watcher',      // Nome della voce di menu
        'manage_options',   // Capacità necessaria
        'safelab_ftp',      // Slug della sottomenu
        'safelab_ftp_page'  // Funzione per la pagina
    );

    // Sottomenu: Settings
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
    echo '<div class="wrap"><h1>FTP Watcher</h1><p>In questa pagina sarà possibile monitorare i file di WordPress.</p></div>';
}

// Funzione per la pagina Settings
function safelab_settings_page() {
    echo '<div class="wrap"><h1>Impostazioni di SafeLab</h1><p>Configura qui le impostazioni del plugin.</p></div>';
}
