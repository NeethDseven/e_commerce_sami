<?php
require_once __DIR__ . '/../model/cartOrderModel.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/orderModel.php';

// Supprimer tout output précédent et désactiver l'affichage des erreurs
ob_clean();
ini_set('display_errors', 0);
error_reporting(E_ALL);

function sendJsonResponse($data, $statusCode = 200) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

try {
    // Vérifier que le PDO est disponible
    if (!isset($pdo)) {
        throw new Exception("Connexion à la base de données non disponible");
    }

    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['id_article']) || !isset($data['quantite'])) {
                sendJsonResponse(['error' => 'Données invalides'], 400);
                break;
            }
            
            try {
                // Utilisation directe de addToCart qui utilise maintenant verifyArticle()
                $result = addToCart($pdo, intval($data['id_article']), intval($data['quantite']));
                sendJsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'show':
            try {
                // S'assurer que la session est active
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // Récupérer le panier selon le type d'utilisateur
                $cart = isset($_SESSION['id_utilisateur']) 
                    ? getUserCart($pdo) 
                    : getGuestCart($pdo);

                sendJsonResponse([
                    'items' => $cart['items'] ?? [],
                    'total' => $cart['total'] ?? 0
                ]);
            } catch (Exception $e) {
                error_log('Erreur cartOrder/show: ' . $e->getMessage());
                sendJsonResponse([
                    'error' => 'Erreur lors de la récupération du panier',
                    'details' => $e->getMessage()
                ], 500);
            }
            break;
            
        case 'remove':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id_article'])) {
                sendJsonResponse(['error' => 'ID article manquant'], 400);
            }
            
            try {
                $result = removeFromCart($pdo, $data['id_article']);
                if ($result === true) {
                    sendJsonResponse([
                        'success' => true, 
                        'message' => 'Article supprimé avec succès'
                    ]);
                } else {
                    sendJsonResponse(['error' => 'Erreur lors de la suppression'], 400);
                }
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], 500);
            }
            break;
            
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id_article']) || !isset($data['quantite'])) {
                sendJsonResponse(['error' => 'Données manquantes'], 400);
            }
            
            try {
                $result = updateCartQuantity($pdo, $data['id_article'], intval($data['quantite']));
                sendJsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'clear':
            try {
                $result = clearCart($pdo);
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Panier vidé avec succès'
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'error' => 'Erreur lors du vidage du panier'
                ], 500);
            }
            break;

        case 'validate':
            try {
                if (!isset($_SESSION['id_utilisateur'])) {
                    throw new Exception('Utilisateur non authentifié');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Données invalides');
                }

                // Récupérer le panier actuel
                $cart = getCart($pdo);
                if (empty($cart['items'])) {
                    throw new Exception('Panier vide');
                }

                // Créer la commande avec la nouvelle fonction
                $orderId = createCartOrder($pdo, $_SESSION['id_utilisateur']);

                sendJsonResponse([
                    'success' => true,
                    'orderId' => $orderId,
                    'message' => 'Commande validée avec succès',
                    'redirect' => 'index.php'
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Action non reconnue'], 404);
    }
} catch (Exception $e) {
    error_log('Cart error: ' . $e->getMessage());
    sendJsonResponse(['error' => $e->getMessage()], 500);
}
?>