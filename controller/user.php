<?php
// Désactiver l'affichage des erreurs dans la sortie
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Configurer le gestionnaire d'erreurs personnalisé
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Erreur PHP : $errstr dans $errfile ligne $errline");
    return true;
});

session_start();

// Définir l'en-tête JSON avant tout
header('Content-Type: application/json; charset=utf-8');

// Ajout des headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header("Content-Security-Policy: default-src 'self'");

try {
    require __DIR__ . '/../model/user.php';
    require __DIR__ . '/../includes/database.php';
    require __DIR__ . '/../includes/functions.php';

    /** @var PDO $pdo */
    $action = $_GET['action'] ?? 'list';
    $errors = [];

    if ($action === 'list') {
        $page = isset($_GET['currentPage']) ? (int)$_GET['currentPage'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $limit = 15;
        $offset = max(0, ($page - 1) * $limit);

        try {
            $users = getUsersByPage($pdo, $offset, $limit, $search);
            $totalUsers = getUserCount($pdo, $search);
            $totalPages = ceil($totalUsers / $limit);

            echo json_encode([
                'users' => $users,
                'total' => $totalUsers,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'success' => true
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }

    if ($action === 'add') {
        try {
            // Vérification de la méthode HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée', 405);
            }

            // Vérification des droits admin
            if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
                throw new Exception('Accès non autorisé', 403);
            }

            // Débogage
            error_log('POST data: ' . print_r($_POST, true));

            // Validation des données requises
            if (empty($_POST['nom']) || empty($_POST['email']) || empty($_POST['mot_de_passe']) || empty($_POST['role'])) {
                throw new Exception('Données manquantes');
            }

            // Nettoyage et validation des données
            $input = [
                'nom' => filter_var($_POST['nom'] ?? '', FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
                'role' => filter_var($_POST['role'] ?? '', FILTER_SANITIZE_STRING)
            ];

            // Validation stricte
            if (!preg_match('/^[A-Za-z0-9À-ÿ\s-]{2,50}$/', $input['nom'])) {
                throw new Exception('Format de nom invalide');
            }

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format d\'email invalide');
            }

            if (strlen($input['mot_de_passe']) < 6) {
                throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
            }

            if (!in_array($input['role'], ['utilisateur', 'admin'])) {
                throw new Exception('Rôle invalide');
            }

            // Hachage du mot de passe
            $input['mot_de_passe'] = password_hash($input['mot_de_passe'], PASSWORD_DEFAULT);

            // Insertion de l'utilisateur
            $result = insertUser(
                $pdo,
                trim($input['nom']),
                $input['mot_de_passe'],
                trim($input['email']),
                trim($input['role'])
            );

            if ($result === false) {
                throw new Exception('Erreur lors de l\'insertion');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur créé avec succès'
            ]);
            exit;

        } catch (Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    if ($action === 'update' && !empty($_GET['id_utilisateur'])) {
        $id_utilisateur = cleanString($_GET['id_utilisateur']);
        $nom = !empty($_POST['nom']) ? cleanString($_POST['nom']) : null;
        $email = !empty($_POST['email']) ? cleanString($_POST['email']) : null;
        $role = !empty($_POST['role']) ? cleanString($_POST['role']) : null;
        $password = !empty($_POST['mot_de_passe']) ? cleanString($_POST['mot_de_passe']) : null;

        if (!empty($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            // Si pas de nouveau mot de passe, on le retire pour ne pas écraser l'ancien
            unset($_POST['mot_de_passe']);
        }

        $updatedUser = updateUser($pdo, $id_utilisateur, $nom, $email, $role, $password);
        if (!is_bool($updatedUser)) {
            throw new Exception($updatedUser);
        }

        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'delete' && !empty($_GET['id_utilisateur'])) {
        $id_utilisateur = cleanString($_GET['id_utilisateur']);
        $deletedUser = deleteUser($pdo, $id_utilisateur);
        if (!is_bool($deletedUser)) {
            throw new Exception($deletedUser);
        }

        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'checkDependencies') {
        $id = $_GET['id_utilisateur'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_utilisateur = ?");
            $stmt->execute([$id]);
            $hasCommandes = $stmt->fetchColumn() > 0;
            echo json_encode(['hasCommandes' => $hasCommandes]);
        }
        exit();
    }

    if ($action === 'delete') {
        $id = $_GET['id_utilisateur'] ?? null;
        $force = isset($_GET['force']) && $_GET['force'] === 'true';
        
        if ($id) {
            try {
                $pdo->beginTransaction();
                
                // Vérifie si l'utilisateur a des commandes
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_utilisateur = ?");
                $stmt->execute([$id]);
                $hasCommandes = $stmt->fetchColumn() > 0;
                
                if ($hasCommandes && !$force) {
                    $pdo->rollBack();
                    echo json_encode([
                        'success' => false,
                        'hasCommandes' => true,
                        'message' => 'Cet utilisateur a des commandes associées.'
                    ]);
                    exit();
                }
                
                // Si force=true ou pas de commandes, procéder à la suppression
                if ($force) {
                    // Supprimer d'abord les commandes
                    $stmt = $pdo->prepare("DELETE FROM commande WHERE id_utilisateur = ?");
                    $stmt->execute([$id]);
                }
                
                // Supprimer l'utilisateur
                $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
                $stmt->execute([$id]);
                
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            exit();
        }
    }

    throw new Exception('Invalid action or missing user ID');
} catch (Throwable $e) {
    error_log('Erreur critique: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur inattendue est survenue'
    ]);
    exit;
}