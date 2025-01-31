// Ajouter au début du fichier
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Gérer les erreurs pour qu'elles ne perturbent pas la sortie JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error ($errno): $errstr in $errfile on line $errline");
    return true;
});

// ...existing code...
