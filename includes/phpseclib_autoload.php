<?php
// Autoload manuale per phpseclib
spl_autoload_register(function ($class) {
    $prefix = 'phpseclib3\\';
    $base_dir = __DIR__ . '/phpseclib/';
    
    // Controlla se la classe utilizza il namespace phpseclib
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Ottieni il nome relativo della classe
    $relative_class = substr($class, $len);

    // Genera il percorso del file
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Include il file se esiste
    if (file_exists($file)) {
        require $file;
    }
});
