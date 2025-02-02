<?php
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/categoryModel.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$isAjax) {
    echo json_encode(['success' => false, 'error' => 'Requête non autorisée']);
    exit;
}

try {
    $action = $_GET['action'] ?? '';

    if ($action === 'list') {
        try {
            $categories = getCategories($pdo);
            if (!$categories) {
                $categories = [];
            }
            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des catégories'
            ]);
            exit;
        }
    }

    if (!isset($_SESSION['auth']) || $_SESSION['Role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
        exit;
    }

    if ($action === 'updateOrder') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Données JSON invalides');
        }

        if (!isset($data['categories']) || !is_array($data['categories'])) {
            throw new Exception('Format de données incorrect');
        }

        $pdo->beginTransaction();
        
        try {
            $stmt = $pdo->prepare('UPDATE categorie SET ordre = :ordre WHERE id_categorie = :id');
            foreach ($data['categories'] as $category) {
                $stmt->execute([
                    ':ordre' => $category['ordre'],
                    ':id' => $category['id_categorie']
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

    switch ($action) {
        case 'create':
            try {
                if (empty($_POST['nom'])) {
                    throw new Exception('Le nom est requis');
                }
                
                $nom = trim($_POST['nom']);
                $ordre = isset($_POST['ordre']) ? intval($_POST['ordre']) : 0;
                
                $stmt = $pdo->query('SELECT MAX(id_categorie) as max_id FROM categorie');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $nextId = ($result['max_id'] ?? 0) + 1;
                
                $stmt = $pdo->prepare('INSERT INTO categorie (nom, ordre) VALUES (?, ?)');
                if ($stmt->execute([$nom, $ordre])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Catégorie créée avec succès',
                        'id' => $pdo->lastInsertId()
                    ]);
                } else {
                    throw new Exception('Erreur lors de l\'insertion');
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;

        case 'update':
            $data = $_POST;
            $id = $data['id_categorie'] ?? null;
            
            try {
                $stmt = $pdo->prepare('UPDATE categorie SET nom = ?, ordre = ? WHERE id_categorie = ?');
                $stmt->execute([$data['nom'], $data['ordre'], $id]);
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'delete':
            try {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
                }

                if (!isset($data['id_categorie'])) {
                    throw new Exception('ID de catégorie manquant');
                }

                $id = intval($data['id_categorie']);

                if (!deleteCategory($pdo, $id)) {
                    throw new Exception('Erreur lors de la suppression de la catégorie');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Catégorie et articles associés supprimés avec succès'
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;

        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                break;
            }

            try {
                $stmt = $pdo->prepare('SELECT * FROM categorie WHERE id_categorie = ?');
                $stmt->execute([$id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'category' => $category]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
exit;
?>
