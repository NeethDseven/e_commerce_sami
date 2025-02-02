<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/articleModel.php';
require_once __DIR__ . '/../model/promotionModel.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getPromotions':
        header('Content-Type: application/json');
        try {
            $promotions = getActivePromotions($pdo);
            echo json_encode([
                'success' => true,
                'articles' => $promotions
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                $pdo->beginTransaction();

                $data = [
                    'nom' => $_POST['nom'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'prix' => floatval($_POST['prix'] ?? 0),
                    'stock' => intval($_POST['stock'] ?? 0),
                    'categorie' => intval($_POST['categorie'] ?? 0)
                ];

                if (empty($data['nom'])) throw new Exception('Le nom est requis');
                if (empty($data['description'])) throw new Exception('La description est requise');
                if ($data['prix'] <= 0) throw new Exception('Le prix doit être supérieur à 0');
                if ($data['stock'] < 0) throw new Exception('Le stock ne peut pas être négatif');
                if ($data['categorie'] <= 0) throw new Exception('La catégorie est requise');

                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'assets/images/articles/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                    $uploadFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                        $data['image'] = $fileName;
                    }
                }

                $success = createArticle($pdo, $data);
                $articleId = $pdo->lastInsertId();

                if ($success && isset($_POST['has_promotion']) && $_POST['has_promotion'] === 'on') {
                    $reduction = min(60, max(0, floatval($_POST['reduction_percent'])));
                    addPromotion($pdo, $articleId, $reduction, $_POST['date_debut'], $_POST['date_fin']);
                }

                $pdo->commit();

                $articleComplet = getArticleById($pdo, $articleId);

                echo json_encode([
                    'success' => true, 
                    'message' => 'Article créé avec succès',
                    'article' => $articleComplet
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean();
            header('Content-Type: application/json');
            error_reporting(E_ALL);
            ini_set('display_errors', 0);

            try {
                if (empty($_POST['id_article'])) {
                    throw new Exception('ID article requis pour la mise à jour');
                }

                error_log('UPDATE - Received POST data: ' . print_r($_POST, true));
                error_log('UPDATE - Received FILES data: ' . print_r($_FILES, true));

                $pdo->beginTransaction();
                $articleResult = updateArticle($pdo, $_POST);
                
                $hasPromotion = isset($_POST['has_promotion']) && $_POST['has_promotion'] === 'on';
                if ($hasPromotion) {
                    if (!empty($_POST['reduction_percent']) && !empty($_POST['date_debut']) && !empty($_POST['date_fin'])) {
                        updatePromotion($pdo, $_POST['id_article'], [
                            'reduction_percent' => floatval($_POST['reduction_percent']),
                            'date_debut' => $_POST['date_debut'],
                            'date_fin' => $_POST['date_fin']
                        ]);
                    }
                } else {
                    removePromotion($pdo, $_POST['id_article']);
                }

                $updatedArticle = getArticleById($pdo, $_POST['id_article']);

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Article mis à jour avec succès',
                    'article' => $updatedArticle
                ]);

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Error in adminPanel update: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                $pdo->beginTransaction();

                // Debug log pour voir les valeurs reçues
                error_log('POST data reçue: ' . print_r($_POST, true));
                
                // Mise à jour de l'article
                $success = updateArticle($pdo, $_POST);
                
                // Gestion de la promotion avec validation explicite
                $hasPromotion = isset($_POST['has_promotion']) && $_POST['has_promotion'] === 'on';
                if ($success && $hasPromotion) {
                    // S'assurer que la réduction est bien convertie en nombre
                    $reduction = filter_var($_POST['reduction_percent'], FILTER_VALIDATE_FLOAT);
                    error_log('Réduction reçue: ' . $reduction);

                    if ($reduction !== false) {
                        $promoData = [
                            'reduction_percent' => $reduction,
                            'date_debut' => $_POST['date_debut'],
                            'date_fin' => $_POST['date_fin']
                        ];
                        error_log('Données de promotion à envoyer: ' . print_r($promoData, true));
                        $success = updatePromotion($pdo, $_POST['id_article'], $promoData);
                    } else {
                        throw new Exception('Valeur de réduction invalide');
                    }
                } elseif ($success) {
                    // Si pas de promotion, on passe null pour supprimer
                    $success = updatePromotion($pdo, $_POST['id_article'], null);
                }
                
                if ($success) {
                    $pdo->commit();
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Article mis à jour avec succès',
                        'debug' => [
                            'reduction' => $reduction ?? null,
                            'hasPromotion' => $hasPromotion
                        ]
                    ]);
                } else {
                    throw new Exception('Échec de la mise à jour');
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Erreur dans update: " . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        break;

    case 'delete':
        if (isset($_POST['id'])) {
            header('Content-Type: application/json');
            try {
                $result = deleteArticle($pdo, $_POST['id']);
                if (!$result) {
                    throw new Exception('Échec de la suppression de l\'article');
                }
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            exit;
        }
        break;

    default:
        try {
            error_log('Début du traitement par défaut dans adminPanelController');
            
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $itemsPerPage = 15;

            $options = [
                'limit' => $itemsPerPage,
                'offset' => ($page - 1) * $itemsPerPage,
                'category' => $_GET['category'] ?? null,
                'search' => $_GET['search'] ?? '',
                'promos_only' => isset($_GET['promos_only']) && $_GET['promos_only'] === '1'
            ];

            error_log('Options de recherche: ' . print_r($options, true));
            
            [$articles, $totalCount] = getArticles($pdo, $options);
            $pagination = getPaginationData($page, $totalCount, $itemsPerPage);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'articles' => $articles,
                        'pagination' => $pagination
                    ]
                ]);
                exit;
            }

            $categories = getCategories($pdo);
            require_once __DIR__ . '/../views/adminPanelView.php';

        } catch (Exception $e) {
            error_log('Erreur dans adminPanelController: ' . $e->getMessage());
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'error' => $e->getMessage(),
                    'debug' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ]);
                exit;
            }
            $error = $e->getMessage();
            require_once __DIR__ . '/../views/adminPanelView.php';
        }
        break;
}
