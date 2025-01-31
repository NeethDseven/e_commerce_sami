<?php
require_once __DIR__ . '/../model/cartOrderModel.php';
require_once __DIR__ . '/../includes/database.php';

function jsonResponse($data, $statusCode = 200) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

try {
    if (!isset($pdo)) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['id_article']) || !isset($data['quantite'])) {
                jsonResponse(['error' => 'Données invalides'], 400);
                break;
            }
            
            try {
                // Utilisation directe de addToCart qui utilise maintenant verifyArticle()
                $result = addToCart($pdo, intval($data['id_article']), intval($data['quantite']));
                jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'show':
            $cart = getCart($pdo);
            jsonResponse($cart);
            break;
            
        case 'remove':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id_article'])) {
                jsonResponse(['error' => 'ID article manquant'], 400);
            }
            
            try {
                $result = removeFromCart($pdo, $data['id_article']);
                if ($result === true) {
                    jsonResponse([
                        'success' => true, 
                        'message' => 'Article supprimé avec succès'
                    ]);
                } else {
                    jsonResponse(['error' => 'Erreur lors de la suppression'], 400);
                }
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;
            
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id_article']) || !isset($data['quantite'])) {
                jsonResponse(['error' => 'Données manquantes'], 400);
            }
            
            try {
                $result = updateCartQuantity($pdo, $data['id_article'], intval($data['quantite']));
                jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'clear':
            try {
                $result = clearCart($pdo);
                jsonResponse([
                    'success' => true,
                    'message' => 'Panier vidé avec succès'
                ]);
            } catch (Exception $e) {
                jsonResponse([
                    'error' => 'Erreur lors du vidage du panier'
                ], 500);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action non reconnue'], 404);
    }
} catch (Exception $e) {
    error_log('Cart error: ' . $e->getMessage());
    jsonResponse(['error' => $e->getMessage()], 500);
}
?>