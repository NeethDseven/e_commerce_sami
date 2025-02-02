<?php
require_once __DIR__ . '/../model/orderModel.php';

if (isAjaxRequest()) {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_GET['order_id'])) {
            throw new Exception('ID de commande manquant');
        }

        $orderId = (int)$_GET['order_id'];
        $orderData = getOrderDetails($pdo, $orderId);

        if (!$orderData) {
            throw new Exception('Commande non trouvée');
        }

        echo json_encode([
            'success' => true,
            'data' => $orderData
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

$orderId = (int)$_GET['order_id'];
$orderData = getOrderDetails($pdo, $orderId);
