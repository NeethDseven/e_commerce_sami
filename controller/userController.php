<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/user.php';

// Vérifier si l'utilisateur est admin en utilisant 'Role' au lieu de 'role'
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer et valider les données
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['nom']) || !isset($data['email']) || !isset($data['password'])) {
            throw new Exception('Données invalides');
        }

        // Valider l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email invalide');
        }

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cet email est déjà utilisé');
        }

        // Hasher le mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, email, password, role) VALUES (?, ?, ?, 'user')");
        if ($stmt->execute([$data['nom'], $data['email'], $hashedPassword])) {
            $userId = $pdo->lastInsertId();
            
            // Retourner uniquement les informations non sensibles
            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'user' => [
                    'id' => $userId,
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'role' => 'user'
                ]
            ]);
        } else {
            throw new Exception('Erreur lors de la création de l\'utilisateur');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
