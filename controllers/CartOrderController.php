<?php
require_once 'models/CartOrder.php';

class CartOrderController {
    private $cartOrder;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->cartOrder = new CartOrder($this->db);
    }

    public function show() {
        header('Content-Type: application/json');
        try {
            $cartDetails = $this->cartOrder->getCartDetails();
            echo json_encode([
                'success' => true,
                'data' => $cartDetails
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}
