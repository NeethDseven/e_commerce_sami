<?php
<<<<<<< HEAD
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

=======
>>>>>>> origin/develop
require_once __DIR__ . '/../model/cartOrderModel.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/orderModel.php';

<<<<<<< HEAD
ob_clean();
=======
// Supprimer tout output précédent et désactiver l'affichage des erreurs
ob_clean();
ini_set('display_errors', 0);
error_reporting(E_ALL);
>>>>>>> origin/develop

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

<<<<<<< HEAD
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
=======
try {
    // Vérifier que le PDO est disponible
>>>>>>> origin/develop
    if (!isset($pdo)) {
        throw new Exception("Connexion à la base de données non disponible");
    }

    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'add':
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/develop
            }
            break;
            
        case 'show':
            try {
<<<<<<< HEAD
=======
                // S'assurer que la session est active
>>>>>>> origin/develop
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

<<<<<<< HEAD
=======
                // Récupérer le panier selon le type d'utilisateur
>>>>>>> origin/develop
                $cart = isset($_SESSION['id_utilisateur']) 
                    ? getUserCart($pdo) 
                    : getGuestCart($pdo);

                sendJsonResponse([
                    'items' => $cart['items'] ?? [],
                    'total' => $cart['total'] ?? 0
                ]);
            } catch (Exception $e) {
<<<<<<< HEAD
=======
                error_log('Erreur cartOrder/show: ' . $e->getMessage());
>>>>>>> origin/develop
                sendJsonResponse([
                    'error' => 'Erreur lors de la récupération du panier',
                    'details' => $e->getMessage()
                ], 500);
            }
            break;
            
        case 'remove':
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/develop
            }
            break;
            
        case 'update':
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/develop
            }
            break;
            
        case 'clear':
            try {
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/develop
            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
            break;
<<<<<<< HEAD

        case 'validate':
            $controller = new CartOrderController($pdo);
            $controller->validate();
            break;
=======
>>>>>>> origin/develop
            
        default:
            sendJsonResponse(['error' => 'Action non reconnue'], 404);
    }
} catch (Exception $e) {
<<<<<<< HEAD
=======
    error_log('Cart error: ' . $e->getMessage());
>>>>>>> origin/develop
    sendJsonResponse(['error' => $e->getMessage()], 500);
}
?>