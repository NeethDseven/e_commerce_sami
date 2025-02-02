<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error handling for AJAX requests
if (isAjaxRequest()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/user.php';

// Handle AJAX requests
if (isAjaxRequest()) {
    try {
        header('Content-Type: application/json; charset=utf-8');
        
        // Verify authentication
        if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
            throw new Exception('Accès non autorisé');
        }

        // Get and validate action parameter
        if (!isset($_GET['action'])) {
            throw new Exception('Paramètre action manquant');
        }

        $action = strtolower(trim($_GET['action']));
        
        switch($action) {
            case 'list':
                $page = isset($_GET['currentPage']) ? max(1, intval($_GET['currentPage'])) : 1;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $limit = 15;
                $offset = ($page - 1) * $limit;
                
                $users = $search ? 
                    searchUsers($pdo, $search, $offset, $limit) : 
                    getUsersByPage($pdo, $offset, $limit);
                    
                if ($users === false) {
                    throw new Exception('Erreur lors de la récupération des utilisateurs');
                }
                
                $totalUsers = $search ? 
                    getSearchUsersCount($pdo, $search) : 
                    getUserCount($pdo);
                    
                echo json_encode([
                    'success' => true,
                    'users' => $users,
                    'total' => $totalUsers,
                    'totalPages' => ceil($totalUsers / $limit),
                    'currentPage' => $page
                ]);
                exit;
                break;

            case 'add':
                // Validate and sanitize input
                $userData = [
                    'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                    'role' => htmlspecialchars(trim($_POST['role'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'mot_de_passe' => $_POST['mot_de_passe'] ?? ''
                ];

                // Validate required fields
                if (empty($userData['nom']) || empty($userData['email']) || 
                    empty($userData['role']) || empty($userData['mot_de_passe'])) {
                    throw new Exception('Tous les champs sont requis');
                }

                // Validate email
                if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Format d\'email invalide');
                }

                // Check if email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = :email");
                $stmt->execute([':email' => $userData['email']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cet email est déjà utilisé');
                }

                // Hash password
                $userData['mot_de_passe'] = password_hash($userData['mot_de_passe'], PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role) 
                                     VALUES (:nom, :email, :mot_de_passe, :role)");
                
                if (!$stmt->execute([
                    ':nom' => $userData['nom'],
                    ':email' => $userData['email'],
                    ':mot_de_passe' => $userData['mot_de_passe'],
                    ':role' => $userData['role']
                ])) {
                    throw new Exception('Erreur lors de la création de l\'utilisateur');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Utilisateur créé avec succès',
                    'userId' => $pdo->lastInsertId()
                ]);
                exit;
                break;

            case 'update':
                if (!isset($_GET['id_utilisateur'])) {
                    throw new Exception('ID utilisateur manquant');
                }

                $userId = filter_var($_GET['id_utilisateur'], FILTER_VALIDATE_INT);
                if ($userId === false) {
                    throw new Exception('ID utilisateur invalide');
                }

                $updateData = [
                    'nom' => trim($_POST['nom'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'role' => trim($_POST['role'] ?? '')
                ];

                // Validate required fields
                foreach ($updateData as $key => $value) {
                    if (empty($value)) {
                        throw new Exception("Le champ {$key} est requis");
                    }
                }

                // Validate email
                if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Format d\'email invalide');
                }

                // Sanitize data
                $updateData['nom'] = htmlspecialchars($updateData['nom'], ENT_QUOTES, 'UTF-8');
                $updateData['role'] = htmlspecialchars($updateData['role'], ENT_QUOTES, 'UTF-8');

                // Handle password update
                if (!empty($_POST['password'])) {
                    $updateData['mot_de_passe'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }

                // Build SQL query
                $sql = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role";
                if (isset($updateData['mot_de_passe'])) {
                    $sql .= ", mot_de_passe = :mot_de_passe";
                }
                $sql .= " WHERE id_utilisateur = :id";

                // Execute update
                $stmt = $pdo->prepare($sql);
                $params = [
                    ':nom' => $updateData['nom'],
                    ':email' => $updateData['email'],
                    ':role' => $updateData['role'],
                    ':id' => $userId
                ];
                
                if (isset($updateData['mot_de_passe'])) {
                    $params[':mot_de_passe'] = $updateData['mot_de_passe'];
                }

                if (!$stmt->execute($params)) {
                    throw new Exception('Erreur lors de la mise à jour de l\'utilisateur');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Utilisateur mis à jour avec succès'
                ]);
                exit;

            case 'delete':
                if (!isset($_GET['id_utilisateur'])) {
                    throw new Exception('ID utilisateur manquant');
                }

                $userId = filter_var($_GET['id_utilisateur'], FILTER_VALIDATE_INT);
                if ($userId === false) {
                    throw new Exception('ID utilisateur invalide');
                }

                // Check if user has orders
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_utilisateur = :id");
                $stmt->execute([':id' => $userId]);
                $hasOrders = $stmt->fetchColumn() > 0;

                if ($hasOrders && !isset($_GET['force'])) {
                    echo json_encode([
                        'success' => false,
                        'hasCommandes' => true,
                        'message' => 'L\'utilisateur a des commandes associées'
                    ]);
                    exit;
                }

                // Start transaction
                $pdo->beginTransaction();
                try {
                    // Delete orders if they exist and force is true
                    if ($hasOrders && isset($_GET['force'])) {
                        $stmt = $pdo->prepare("DELETE FROM commande WHERE id_utilisateur = :id");
                        $stmt->execute([':id' => $userId]);
                    }

                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = :id");
                    if (!$stmt->execute([':id' => $userId])) {
                        throw new Exception('Erreur lors de la suppression');
                    }

                    $pdo->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Utilisateur supprimé avec succès'
                    ]);
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw new Exception('Erreur lors de la suppression: ' . $e->getMessage());
                }
                break;

            default:
                throw new Exception('Action non valide');
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    }
    exit;
}

// Ne charger que l'interface initiale sans données
$roles = getEnumValues($pdo, 'utilisateur', 'role');
$initialPage = isset($_GET['currentPage']) ? max(1, intval($_GET['currentPage'])) : 1;

