<?php
require_once __DIR__ . '/promotionModel.php';

function getArticles(PDO $pdo, array $options = []): array {
    try {
        $defaultOptions = [
            'offset' => 0,
            'limit' => 15,
            'category' => null,
            'search' => '',
            'promos_only' => false
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // First, get total count
        $countQuery = "SELECT COUNT(*) FROM article a";
        $whereConditions = [];
        $params = [];
        
        if ($options['category']) {
            $whereConditions[] = "a.id_categorie = :category";
            $params[':category'] = $options['category'];
        }

        if ($options['search']) {
            $whereConditions[] = "a.nom LIKE :search";
            $params[':search'] = '%' . $options['search'] . '%';
        }

        if ($options['promos_only']) {
            $countQuery = "SELECT COUNT(DISTINCT a.id_article) FROM article a 
                          INNER JOIN promotion p ON a.id_article = p.id_article 
                          WHERE CURRENT_DATE BETWEEN p.date_debut AND p.date_fin";
        }

        if (!empty($whereConditions)) {
            $countQuery .= ($options['promos_only'] ? " AND " : " WHERE ") . implode(" AND ", $whereConditions);
        }

        $stmt = $pdo->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalCount = (int)$stmt->fetchColumn();

        // Then get the articles
        $query = "
            SELECT 
                a.*,
                c.nom as categorie_nom,
                p.prix_promotionnel,
                p.date_debut as promo_date_debut,
                p.date_fin as promo_date_fin,
                CASE 
                    WHEN p.prix_promotionnel IS NOT NULL 
                    AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                    THEN ROUND(((a.prix - p.prix_promotionnel) / a.prix * 100))
                    ELSE NULL 
                END as pourcentage_reduction
            FROM article a 
            LEFT JOIN categorie c ON a.id_categorie = c.id_categorie
            LEFT JOIN promotion p ON a.id_article = p.id_article 
            AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin";

        if ($options['promos_only']) {
            $query = str_replace("LEFT JOIN promotion", "INNER JOIN promotion", $query);
            $query .= " WHERE CURRENT_DATE BETWEEN p.date_debut AND p.date_fin";
            if (!empty($whereConditions)) {
                $query .= " AND " . implode(" AND ", $whereConditions);
            }
        } else if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $query .= " ORDER BY a.id_article ASC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
        
        $stmt->execute();
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [$articles, $totalCount];
    } catch (PDOException $e) {
        error_log("Error in getArticles: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des articles");
    }
}

function getArticlesWithPagination($pdo, $page, $itemsPerPage, $category = null, $promotion = false) {
    return getArticles($pdo, [
        'page' => $page,
        'items_per_page' => $itemsPerPage,
        'category' => $category,
        'promotion' => $promotion
    ]);
}

function getArticleById(PDO $pdo, int $id): ?array {
    try {
        $query = "
            SELECT 
                a.*,
                c.nom as categorie_nom,
                p.prix_promotionnel,
                p.date_debut as promo_date_debut,
                p.date_fin as promo_date_fin,
                CASE 
                    WHEN p.prix_promotionnel IS NOT NULL 
                    AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                    AND a.prix > 0
                    AND p.prix_promotionnel < a.prix
                    THEN ABS(ROUND(((a.prix - p.prix_promotionnel) / a.prix * 100), 1))
                    ELSE NULL 
                END as pourcentage_reduction
            FROM article a 
            LEFT JOIN categorie c ON a.id_categorie = c.id_categorie
            LEFT JOIN promotion p ON a.id_article = p.id_article 
            AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin
            WHERE a.id_article = :id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Error in getArticleById: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération de l'article");
    }
}

// Autres fonctions liées aux articles uniquement
function createArticle(PDO $pdo, array $data): bool {
    try {
        $sql = "INSERT INTO article (nom, description, image, prix, stock, id_categorie) 
                VALUES (:nom, :description, :image, :prix, :stock, :id_categorie)";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':image' => $data['image'] ?? null,
            ':prix' => $data['prix'],
            ':stock' => $data['stock'],
            ':id_categorie' => $data['categorie']
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new Exception('Erreur lors de la création de l\'article');
    }
}

function updateArticle(PDO $pdo, array $data): array {
    try {
        error_log('Updating article with data: ' . print_r($data, true));

        // Validate required fields
        $requiredFields = ['nom', 'description', 'prix', 'stock', 'categorie', 'id_article'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Le champ $field est requis");
            }
        }

        // Prepare base SQL
        $sql = "UPDATE article SET 
                nom = :nom,
                description = :description,
                prix = :prix,
                stock = :stock,
                id_categorie = :id_categorie";
        
        $params = [
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':prix' => floatval($data['prix']),
            ':stock' => intval($data['stock']),
            ':id_categorie' => intval($data['categorie']),
            ':id_article' => intval($data['id_article'])
        ];

        // Handle image if present
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/images/articles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $sql .= ", image = :image";
                $params[':image'] = $fileName;
            }
        }

        $sql .= " WHERE id_article = :id_article";
        
        // Execute update
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if (!$success) {
            throw new Exception("Échec de la mise à jour de l'article");
        }

        // Get updated article
        $stmt = $pdo->prepare("SELECT a.*, c.nom as categorie_nom
                              FROM article a 
                              LEFT JOIN categorie c ON a.id_categorie = c.id_categorie 
                              WHERE a.id_article = :id");
        $stmt->execute([':id' => $data['id_article']]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) {
            throw new Exception("Article non trouvé après la mise à jour");
        }

        return [
            'success' => true,
            'article' => $article
        ];
    } catch (PDOException $e) {
        error_log('SQL Error in updateArticle: ' . $e->getMessage());
        throw new Exception('Erreur lors de la mise à jour de l\'article');
    } catch (Exception $e) {
        error_log('Error in updateArticle: ' . $e->getMessage());
        throw $e;
    }
}

function deleteArticle($pdo, $id) {
    try {
        // Commencer une transaction
        $pdo->beginTransaction();

        // Supprimer d'abord les promotions associées
        $stmt = $pdo->prepare("DELETE FROM promotion WHERE id_article = ?");
        $stmt->execute([$id]);

        // Ensuite supprimer l'article
        $stmt = $pdo->prepare("DELETE FROM article WHERE id_article = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            $pdo->commit();
            return true;
        }

        $pdo->rollBack();
        return false;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur lors de la suppression de l'article: " . $e->getMessage());
        throw new Exception("Impossible de supprimer l'article");
    }
}

// Modifier la fonction getPaginationData pour inclure plus d'informations
function getPaginationData(int $page, int $totalItems, int $itemsPerPage): array {
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));
    $currentPage = min(max(1, $page), $totalPages);
    
    return [
        'currentPage' => (int)$currentPage,
        'totalPages' => (int)$totalPages,
        'itemsPerPage' => (int)$itemsPerPage,
        'totalItems' => (int)$totalItems,
        'offset' => ($currentPage - 1) * $itemsPerPage,
        'hasMorePages' => $currentPage < $totalPages,
        'firstItem' => ($currentPage - 1) * $itemsPerPage + 1,
        'lastItem' => min($currentPage * $itemsPerPage, $totalItems)
    ];
}

function getTotalArticles(PDO $pdo, ?int $category = null): int {
    try {
        $query = "SELECT COUNT(*) FROM article";
        $params = [];
        
        if ($category !== null) {
            $query .= " WHERE id_categorie = :category";
            $params[':category'] = $category;
        }
        
        $stmt = $pdo->prepare($query);
        
        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in getTotalArticles: " . $e->getMessage());
        throw new Exception("Erreur lors du comptage des articles");
    }
}


function getPrixFinal(array $article): float {
    if (isset($article['prix_promotionnel']) && 
        isset($article['promo_date_debut']) && 
        isset($article['promo_date_fin']) && 
        strtotime('now') >= strtotime($article['promo_date_debut']) && 
        strtotime('now') <= strtotime($article['promo_date_fin'])) {
        return floatval($article['prix_promotionnel']);
    }
    return floatval($article['prix']);
}

function regroupeArticlesPanier(array $articles): array {
    $regrouped = [];
    foreach ($articles as $article) {
        $id = $article['id_article'];
        if (!isset($regrouped[$id])) {
            $regrouped[$id] = $article;
        } else {
            $regrouped[$id]['quantite'] += $article['quantite'];
        }
    }
    return array_values($regrouped);
}

function calculeTotalPanier(array $articles): float {
    $total = 0;
    foreach ($articles as $article) {
        $prixFinal = isset($article['prix_final']) ? 
            floatval($article['prix_final']) : 
            floatval($article['prix']);
        $total += $prixFinal * $article['quantite'];
    }
    return round($total, 2);
}