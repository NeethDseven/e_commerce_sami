<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/orderModel.php';

class OrderController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // S'assurer que la session est démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function show() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['id_utilisateur'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = $_SESSION['id_utilisateur'];
        try {
            $orderData = [
                'orders' => getOrdersByUser($this->pdo, $userId),
                'user_id' => $userId
            ];
            
            require_once __DIR__ . '/../views/orderView.php';
        } catch (Exception $e) {
            error_log('Erreur dans OrderController::show : ' . $e->getMessage());
            // Gérer l'erreur de manière appropriée
            require_once __DIR__ . '/../views/errorView.php';
        }
    }

    // Autres méthodes...
}

// Vérifier si la requête est AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
    
    // Vérification des droits admin
    if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'message' => 'Action non reconnue'];

    switch ($action) {
        case 'getList':
            $response = [
                'success' => true,
                'orders' => getAllOrders($pdo)
            ];
            break;
            
        case 'updateStatus':
            $data = json_decode(file_get_contents('php://input'), true);
            $orderId = $data['orderId'] ?? null;
            $newStatus = $data['status'] ?? null;
            
            if ($orderId && isValidStatus($newStatus)) {
                $success = updateOrderStatus($orderId, $newStatus);
                $response = [
                    'success' => $success,
                    'order' => getOrderById($orderId),
                    'details' => getOrderDetails($orderId),
                    'total' => calculateOrderTotal($orderId)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Données invalides'];
            }
            break;
            
        case 'getDetails':
            $orderId = $_GET['order_id'] ?? null;
            if ($orderId) {
                $response = [
                    'success' => true,
                    'order' => getOrderById($orderId),
                    'details' => getOrderDetails($orderId),
                    'total' => calculateOrderTotal($orderId)
                ];
            } else {
                $response = ['success' => false, 'message' => 'ID de commande manquant'];
            }
            break;
    }

    echo json_encode($response);
    exit;
} else {
    // Vérification des droits admin pour l'affichage de la page
    if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
        header('Location: index.php?page=login');
        exit;
    }
    
    // Affichage initial de la page
    include __DIR__ . '/../views/orderListView.php';
}
