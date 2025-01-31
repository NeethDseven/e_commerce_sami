<?php
require_once __DIR__ . '/../model/cartOrderModel.php';

// Gestionnaire de fin de session
session_set_save_handler(
    null,
    function() {
        global $pdo;
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Nettoyer les paniers invités expirés
            cleanupGuestCarts($pdo);
        }
    },
    null,
    null,
    null,
    null
);
