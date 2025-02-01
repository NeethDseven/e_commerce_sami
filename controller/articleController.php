<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../model/articleModel.php';
require_once __DIR__ . '/../model/categoryModel.php';

// Récupérer les catégories en premier
try {
    // Récupérer les catégories en utilisant le nouveau modèle
    $categories = getCategories($pdo);
    error_log('Catégories récupérées dans le contrôleur: ' . print_r($categories, true));

    if (empty($categories)) {
        error_log("Aucune catégorie trouvée");
        $categories = [];
    }

    // Traitement des articles
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 15; // Nombre d'articles par page
    $offset = ($page - 1) * $perPage;
    $category = isset($_GET['category']) ? intval($_GET['category']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Utiliser la nouvelle version de getArticles avec le tableau d'options
    $options = [
        'limit' => $perPage,
        'offset' => $offset,
        'category' => $category,
        'search' => $search,
        'withCount' => true
    ];

    [$articles, $totalCount] = getArticles($pdo, $options);

    // Générer les données de pagination avec le nombre total d'articles
    $paginationData = getPaginationData($page, intval($totalCount), $perPage);

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $articles,
            'pagination' => $paginationData
        ]);
        exit;
    }

    // Pour le rendu normal de la page
    include __DIR__ . '/../views/articleView.php';
    
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    $categories = [];
    $error = "Erreur lors du chargement des données";

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    $error = $e->getMessage();
    include __DIR__ . '/../views/articleView.php';
}
?>