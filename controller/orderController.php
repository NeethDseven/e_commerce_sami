<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/orderModel.php';

<<<<<<< HEAD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isAjaxRequest()) {
    if (ob_get_level()) ob_end_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $action = $_GET['action'] ?? '';
        $response = [];
        
        switch ($action) {
            case 'getList':
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 15;
                
                $result = getAllOrders($pdo, $page, $perPage);
                $response = [
                    'success' => true,
                    'orders' => $result['orders'],
                    'pagination' => [
                        'total' => $result['total'],
                        'pages' => $result['pages'],
                        'currentPage' => $result['currentPage']
                    ]
                ];
                break;
                
            case 'updateStatus':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['orderId']) || !isset($data['status'])) {
                    $response = ['success' => false, 'message' => 'Données manquantes'];
                    break;
                }
                
                $orderId = intval($data['orderId']);
                $newStatus = trim($data['status']);
                
                if (!$orderId || !isValidStatus($newStatus)) {
                    $response = ['success' => false, 'message' => 'Données invalides'];
                    break;
                }
                
                try {
                    if (updateOrderStatus($orderId, $newStatus)) {
                        $orderData = getOrderDetails($pdo, $orderId);
                        $response = [
                            'success' => true,
                            'message' => 'Statut mis à jour',
                            'order' => $orderData['order'],
                            'details' => $orderData['details'],
                            'total' => $orderData['total']
                        ];
                    } else {
                        throw new Exception('Échec de la mise à jour du statut');
                    }
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;
                
            case 'getDetails':
                $orderId = $_GET['order_id'] ?? null;
                if ($orderId) {
                    $response = [
                        'success' => true,
                        'order' => getOrderById($orderId),
                        'details' => getOrderDetails($pdo, $orderId),
                        'total' => calculateOrderTotal($orderId)
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'ID de commande manquant'];
                }
                break;
        }
        
        echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
    }
    exit;
} else {
    header('Content-Type: text/html; charset=utf-8');
    
    if (isset($_GET['id'])) {
        $orderId = intval($_GET['id']);
        $orderData = [
            'order' => getOrderById($orderId),
            'details' => getOrderDetails($pdo, $orderId),
            'total' => calculateOrderTotal($orderId)
        ];
        
        if (!$orderData['order']) {
            $_SESSION['error'] = "Commande non trouvée";
            header('Location: index.php?page=orderlist');
            exit;
        }
    }
}
=======
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
>>>>>>> origin/develop
