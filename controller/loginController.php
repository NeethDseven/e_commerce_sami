<?php

require_once 'model/loginModel.php';
include 'includes/database.php';

/**
 * @var PDO $pdo
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['mot_de_passe'] ?? '';
    $errors = [];

    if (empty($username) || empty($password)) {
        $errors[] = "Tous les champs sont requis";
    }

    if (empty($errors)) {
        $user = connect($pdo, $username, $password);
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['auth'] = true;
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['username'] = $user['nom'];
            $_SESSION['Role'] = $user['role'];
            
            header('Location: index.php');
            exit();
        } else {
            $errors[] = "Identifiants incorrects";
            $_SESSION['errors'] = $errors;
            header('Location: index.php?page=login');
            exit();
        }
    } else {
        $_SESSION['errors'] = $errors;
        header('Location: index.php?page=login');
        exit();
    }
}

include 'views/loginView.php';