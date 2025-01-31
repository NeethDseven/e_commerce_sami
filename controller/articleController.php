<?php
include_once __DIR__ . '/../includes/database.php';
include_once __DIR__ . '/../model/articleModel.php';

   
// Vérifier si c'est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Log pour debugging
error_log('Request received: ' . ($isAjax ? 'AJAX' : 'Normal'));
error_log('Parameters: ' . json_encode($_GET));

try {
    $itemPerPage = 15; // Assurez-vous que cette valeur est cohérente
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $offset = ($page - 1) * $itemPerPage;
    $category = isset($_GET['category']) ? (int) $_GET['category'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;

    $options = [
        'limit' => $itemPerPage,
        'offset' => $offset,
        'category' => $category,
        'search' => $search,
        'withCount' => true,
        'orderBy' => 'id_article',
        'orderDirection' => 'ASC'  // Changé de DESC à ASC
    ];

    [$articles, $totalArticles] = getArticles($pdo, $options);

    
    $pagination = getPaginationData($page, $totalArticles, $itemPerPage);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $articles,
            'pagination' => $pagination
        ]);
        exit();
    }

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
    $error = $e->getMessage();
    
}

include __DIR__ . '/../views/articleView.php';
?>