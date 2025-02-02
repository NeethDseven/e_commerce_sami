<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../model/cartOrderModel.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/orderModel.php';

ob_clean();

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

class CartOrderController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (!isset($_SESSION['guest_id'])) {
            $_SESSION['guest_id'] = uniqid('guest_', true);
        }
    }

    public function validate() {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data || !isset($data['paymentInfo'])) {
                throw new Exception("Données invalides ou informations de paiement manquantes");
            }

            if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                throw new Exception("Le panier est vide");
            }

            $cartItems = array_values($_SESSION['cart']);
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $guestId = $_SESSION['guest_id'];

            $requiredFields = ['nom', 'prenom', 'email', 'adresse'];
            foreach ($requiredFields as $field) {
                if (empty($data['paymentInfo'][$field])) {
                    throw new Exception("Le champ {$field} est requis");
                }
            }

            $result = createOrder(
                $this->pdo,
                $userId,
                $cartItems,
                $data['paymentInfo'],
                $guestId
            );

            if ($result['success']) {
                $_SESSION['cart'] = [];
                sendJsonResponse([
                    'success' => true,
                    'orderId' => $result['orderId'],
                    'message' => 'Commande créée avec succès'
                ]);
            } else {
                throw new Exception($result['error'] ?? "Erreur lors de la création de la commande");
            }

        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 200);
        }
    }
}

try {
    if (!isset($pdo)) {
        throw new Exception("Connexion à la base de données non disponible");
    }

    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'add':
            try {
                $rawData = file_get_contents('php://input');
                $data = json_decode($rawData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON data");
                }

                if (!isset($data['id_article']) || !isset($data['quantite'])) {
                    throw new Exception("Missing required fields");
                }

                $articleId = filter_var($data['id_article'], FILTER_VALIDATE_INT);
                $quantity = filter_var($data['quantite'], FILTER_VALIDATE_INT);

                if ($articleId === false || $articleId <= 0) {
                    throw new Exception("Invalid article ID");
                }
                if ($quantity === false || $quantity <= 0) {
                    throw new Exception("Invalid quantity");
                }

                $article = getArticleById($pdo, $articleId);
                if (!$article) {
                    throw new Exception("Article not found");
                }

                $result = addToCart($pdo, $articleId, $quantity);

                if (!$result) {
                    throw new Exception("Failed to add to cart");
                }

                $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

                sendJsonResponse([
                    'success' => true,
                    'message' => 'Article ajouté au panier',
                    'cartCount' => $cartCount
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
            break;
            
        case 'show':
            try {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $cart = isset($_SESSION['id_utilisateur']) 
                    ? getUserCart($pdo) 
                    : getGuestCart($pdo);

                sendJsonResponse([
                    'items' => $cart['items'] ?? [],
                    'total' => $cart['total'] ?? 0
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'error' => 'Erreur lors de la récupération du panier',
                    'details' => $e->getMessage()
                ], 500);
            }
            break;
            
        case 'remove':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['id_article'])) {
                    throw new Exception("ID article manquant");
                }

                $result = removeFromCart($pdo, $data['id_article']);
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Article supprimé avec succès',
                    'cartCount' => count($_SESSION['cart'] ?? [])
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
            break;
            
        case 'update':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['id_article']) || !isset($data['quantite'])) {
                    throw new Exception('Données manquantes');
                }

                $idArticle = filter_var($data['id_article'], FILTER_VALIDATE_INT);
                $quantite = filter_var($data['quantite'], FILTER_VALIDATE_INT);

                if ($idArticle === false || $quantite === false || $quantite < 1) {
                    throw new Exception('Données invalides');
                }

                $result = updateCartQuantity($pdo, $idArticle, $quantite);
                
                $cart = isset($_SESSION['id_utilisateur']) 
                    ? getUserCart($pdo) 
                    : getGuestCart($pdo);

                sendJsonResponse([
                    'success' => true,
                    'message' => 'Quantité mise à jour',
                    'cart' => $cart,
                    'new_quantity' => $result['new_quantity'],
                    'stock_restant' => $result['stock_restant']
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
            break;
            
        case 'clear':
            try {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $result = clearCart($pdo);
                
                if ($result['success']) {
                    $_SESSION['cart'] = [];
                    
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Panier vidé avec succès',
                        'cartCount' => 0
                    ]);
                } else {
                    throw new Exception('Erreur lors du vidage du panier');
                }
            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
            break;

        case 'validate':
            $controller = new CartOrderController($pdo);
            $controller->validate();
            break;
            
        default:
            sendJsonResponse(['error' => 'Action non reconnue'], 404);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => $e->getMessage()], 500);
}
?>