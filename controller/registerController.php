<?php
require_once 'model/registerModel.php';
require_once 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // Validations
    if (empty($nom) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Tous les champs sont obligatoires";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }

    if (empty($errors)) {
        $result = registerUser($pdo, $nom, $email, $password);
        
        if ($result === true) {
            $_SESSION['success'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            header('Location: index.php?page=login');
            exit();
        } else {
            $errors[] = is_string($result) ? $result : "Une erreur est survenue lors de l'inscription";
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
