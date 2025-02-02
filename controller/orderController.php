<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/orderModel.php';

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