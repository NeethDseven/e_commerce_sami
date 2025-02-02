<?php
if (!isset($_SESSION)) {
    session_start();
}

<<<<<<< HEAD
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
=======
// Assurez-vous que ces headers sont définis avant tout output
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Empêcher la mise en cache
>>>>>>> origin/develop
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/categoryModel.php';

<<<<<<< HEAD
=======
// Vérifier si la requête est une requête AJAX
>>>>>>> origin/develop
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$isAjax) {
    echo json_encode(['success' => false, 'error' => 'Requête non autorisée']);
    exit;
}

try {
    $action = $_GET['action'] ?? '';

<<<<<<< HEAD
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

=======
    // Pour l'action updateOrder
>>>>>>> origin/develop
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

<<<<<<< HEAD
=======
    if ($action === 'list') {
        try {
            $categories = getCategories($pdo);
            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des catégories: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des catégories'
            ]);
        }
        exit;
    }

    // Déplacer la vérification des permissions après l'action 'list'
    if (!isset($_SESSION['auth']) || $_SESSION['Role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
        exit;
    }

>>>>>>> origin/develop
    switch ($action) {
        case 'create':
            try {
                if (empty($_POST['nom'])) {
                    throw new Exception('Le nom est requis');
                }
                
                $nom = trim($_POST['nom']);
                $ordre = isset($_POST['ordre']) ? intval($_POST['ordre']) : 0;
                
<<<<<<< HEAD
=======
                // Trouver le plus grand ID
>>>>>>> origin/develop
                $stmt = $pdo->query('SELECT MAX(id_categorie) as max_id FROM categorie');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $nextId = ($result['max_id'] ?? 0) + 1;
                
<<<<<<< HEAD
=======
                error_log('DEBUG - Création catégorie - Nom: ' . $nom . ', Ordre: ' . $ordre . ', Next ID: ' . $nextId);
                
>>>>>>> origin/develop
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
<<<<<<< HEAD
=======
                error_log('Erreur création catégorie: ' . $e->getMessage());
>>>>>>> origin/develop
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
<<<<<<< HEAD
=======
                error_log('DEBUG - Données reçues: ' . $input);

>>>>>>> origin/develop
                $data = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
                }

                if (!isset($data['id_categorie'])) {
                    throw new Exception('ID de catégorie manquant');
                }

                $id = intval($data['id_categorie']);
<<<<<<< HEAD

                if (!deleteCategory($pdo, $id)) {
                    throw new Exception('Erreur lors de la suppression de la catégorie');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Catégorie et articles associés supprimés avec succès'
                ]);
            } catch (Exception $e) {
=======
                error_log('DEBUG - ID à supprimer: ' . $id);

                // Vérifier si des articles utilisent cette catégorie
                $checkArticles = $pdo->prepare('SELECT COUNT(*) FROM article WHERE id_categorie = ?');
                $checkArticles->execute([$id]);
                if ($checkArticles->fetchColumn() > 0) {
                    throw new Exception('Impossible de supprimer la catégorie : des articles y sont associés');
                }

                // Supprimer la catégorie
                $stmt = $pdo->prepare('DELETE FROM categorie WHERE id_categorie = ?');
                if (!$stmt->execute([$id])) {
                    throw new Exception('Erreur lors de la suppression de la catégorie');
                }

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Catégorie non trouvée');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Catégorie supprimée avec succès'
                ]);
            } catch (Exception $e) {
                error_log('Erreur suppression catégorie: ' . $e->getMessage());
>>>>>>> origin/develop
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
<<<<<<< HEAD
=======
            
>>>>>>> origin/develop
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
