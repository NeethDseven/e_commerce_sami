<?php
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/promotionModel.php';

try {
    $action = $_GET['action'] ?? '';

    if ($action === 'getPromotions') {
        try {
            $promotions = getActivePromotions($pdo);
            
            if (empty($promotions)) {
                echo json_encode([
                    'success' => true,
                    'promotions' => []
                ]);
                exit;
            }

            $formattedPromotions = array_map(function($promo) {
                return [
                    'id_article' => $promo['id_article'],
                    'nom' => $promo['nom'],
                    'description' => $promo['description'],
                    'image' => $promo['image'],
                    'prix' => $promo['prix'],
                    'prix_promotionnel' => $promo['prix_promotionnel'],
                    'pourcentage_reduction' => round($promo['pourcentage_reduction']),
                    'categorie_nom' => $promo['categorie_nom'],
                    'stock' => $promo['stock']
                ];
            }, $promotions);

            echo json_encode([
                'success' => true,
                'promotions' => $formattedPromotions
            ]);
            exit;
        } catch (Exception $e) {
            throw $e;
        }
    }

    throw new Exception('Action not recognized');

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue lors du traitement de la requête'
    ]);
}
exit;
