<?php
if (!isset($_SESSION)) {
    session_start();
}

ob_start();

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../model/articleModel.php';
    
    try {
        if (!file_exists(__DIR__ . '/../model/articleModel.php')) {
            throw new Exception('Required model file not found');
        }
        
        $action = $_GET['action'] ?? 'list';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $category = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_VALIDATE_INT) : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        switch ($action) {
            case 'promotions':
                $promotions = getActivePromotions($pdo);
                echo json_encode([
                    'success' => true,
                    'promotions' => $promotions
                ]);
                break;

            case 'list':
                $limit = 15;
                $offset = ($page - 1) * $limit;
                
                $options = [
                    'offset' => $offset,
                    'limit' => $limit,
                    'category' => $category,
                    'search' => $search
                ];
                
                $articles = getArticles($pdo, $options);
                $totalArticles = getTotalArticles($pdo, $category, $search);
                $totalPages = ceil($totalArticles / $limit);
                
                echo json_encode([
                    'success' => true,
                    'articles' => $articles,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => $totalPages,
                        'totalItems' => $totalArticles,
                        'itemsPerPage' => $limit,
                        'hasPreviousPage' => $page > 1,
                        'hasNextPage' => $page < $totalPages,
                        'previousPage' => $page > 1 ? $page - 1 : null,
                        'nextPage' => $page < $totalPages ? $page + 1 : null
                    ]
                ]);
                break;
            
            default:
                throw new Exception('Action non valide');
        }
        
    } catch (Exception $e) {
        ob_clean();
        error_log("Article Controller Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors du chargement des articles: ' . $e->getMessage()
        ]);
    }
    exit;
} else {
    include __DIR__ . '/../views/articleView.php';
}
?>