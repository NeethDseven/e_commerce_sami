<?php

if (!defined('INCLUDED_FROM_INDEX')) {
    ob_start();
}

require_once 'model/loginModel.php';
include 'includes/database.php';

/**
 * @var PDO $pdo
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? ''; // Changé de username à identifier
    $password = $_POST['mot_de_passe'] ?? '';
    
    // Debug
    error_log("Tentative de connexion - Identifiant: $identifier");
    
    try {
        $user = connect($pdo, $identifier, $password);
        
        if ($user) {
            $_SESSION['auth'] = true;
            $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
            $_SESSION['username'] = $user['nom'];
            $_SESSION['Role'] = $user['role'];
            
            $redirect = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            
            if (ob_get_length()) ob_end_clean();
            header("Location: " . $redirect);
            exit();
        } else {
            error_log("Échec de connexion pour l'identifiant: $identifier");
            $_SESSION['errors'] = ["Identifiants incorrects"];
            if (ob_get_length()) ob_end_clean();
            header('Location: index.php?page=login');
            exit();
        }
    } catch (Exception $e) {
        error_log("Erreur de connexion : " . $e->getMessage());
        $_SESSION['errors'] = ["Erreur lors de la connexion"];
        if (ob_get_length()) ob_end_clean();
        header('Location: index.php?page=login');
        exit();
    }
}

// Si on arrive ici, c'est une requête GET normale
include 'views/loginView.php';