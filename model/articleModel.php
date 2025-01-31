<?php

// Fonction unifiée pour la pagination et recherche d'articles
function getArticles(PDO $pdo, array $options = []): array {
    try {
        // Calcul du nombre total d'articles d'abord
        $countSql = "SELECT COUNT(*) FROM article";
        $totalCount = (int)$pdo->query($countSql)->fetchColumn();

        $defaultOptions = [
            'limit' => 15,
            'offset' => 0,
            'category' => null,
            'search' => null,
            'orderBy' => 'id_article',
            'orderDirection' => 'ASC',  // Changé de DESC à ASC
            'withCount' => true // Toujours true pour avoir le compte total
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $sql = "SELECT DISTINCT 
                a.*,
                c.nom as categorie_nom,
                CASE 
                    WHEN p.prix_promotionnel IS NOT NULL 
                        AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                        AND p.prix_promotionnel < a.prix
                    THEN p.prix_promotionnel
                    ELSE NULL
                END as prix_final,
                CASE 
                    WHEN p.prix_promotionnel IS NOT NULL 
                        AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                        AND p.prix_promotionnel < a.prix
                    THEN ROUND(((a.prix - p.prix_promotionnel) / a.prix * 100), 0)
                    ELSE 0
                END as pourcentage_reduction
                FROM article a
                LEFT JOIN categorie c ON a.id_categorie = c.id_categorie
                LEFT JOIN promotion p ON a.id_article = p.id_article 
                    AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin
                    AND p.prix_promotionnel < a.prix
                WHERE 1=1";

        $params = [];

        // Gestion de la recherche
        if (!empty($options['search'])) {
            $searchTerms = explode(' ', $options['search']);
            $searchConditions = [];
            foreach ($searchTerms as $index => $term) {
                $paramName = ':search' . $index;
                $searchConditions[] = "(LOWER(a.nom) LIKE LOWER($paramName) OR LOWER(a.description) LIKE LOWER($paramName))";
                $params[$paramName] = '%' . trim($term) . '%';
            }
            $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
        }

        // Gestion de la catégorie
        if (!empty($options['category'])) {
            $sql .= " AND a.id_categorie = :category";
            $params[':category'] = $options['category'];
        }

        // Si on veut le compte total
        if ($options['withCount']) {
            $countSql = preg_replace('/SELECT DISTINCT a\.*,.+?FROM/', 'SELECT COUNT(DISTINCT a.id_article) FROM', $sql);
            $stmtCount = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmtCount->bindValue($key, $value);
            }
            $stmtCount->execute();
            $count = $stmtCount->fetchColumn();
        }

        // Tri et pagination
        $sql .= " ORDER BY a.{$options['orderBy']} {$options['orderDirection']}";
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug pour vérifier le nombre d'articles
        error_log("SQL Query: " . $sql);
        error_log("Total articles found: " . ($options['withCount'] ? $count : count($results)));
        error_log("Limit: " . $options['limit'] . ", Offset: " . $options['offset']);

        // Debug
        error_log("Total count before pagination: " . $totalCount);
        error_log("Current page items: " . count($results));
        error_log("Pagination params - limit: {$options['limit']}, offset: {$options['offset']}");

        return $options['withCount'] ? [$results, $totalCount] : $results;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new Exception('Erreur lors de la récupération des articles');
    }
}

function getCategories(PDO $pdo): array {
    $stmt = $pdo->prepare("SELECT id_categorie, nom FROM categorie ORDER BY ordre ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

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

function updateArticle(PDO $pdo, array $data): bool {
    try {
        $sql = "UPDATE article SET 
                nom = :nom,
                description = :description,
                prix = :prix,
                stock = :stock,
                id_categorie = :id_categorie";
        
        if (isset($data['image'])) {
            $sql .= ", image = :image";
        }
        
        $sql .= " WHERE id_article = :id_article";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':prix' => $data['prix'],
            ':stock' => $data['stock'],
            ':id_categorie' => $data['categorie'],
            ':id_article' => $data['id_article']
        ];
        
        if (isset($data['image'])) {
            $params[':image'] = $data['image'];
        }
        
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new Exception('Erreur lors de la mise à jour de l\'article');
    }
}

function deleteArticle(PDO $pdo, int $id): bool {
    try {
        // Récupérer l'image avant la suppression
        $stmt = $pdo->prepare("SELECT image FROM article WHERE id_article = :id");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Supprimer l'article
        $stmt = $pdo->prepare("DELETE FROM article WHERE id_article = :id");
        $success = $stmt->execute([':id' => $id]);
        
        // Si la suppression a réussi et qu'il y avait une image, la supprimer du serveur
        if ($success && $article && $article['image'] && file_exists($article['image'])) {
            unlink($article['image']);
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new Exception('Erreur lors de la suppression de l\'article');
    }
}

// Ajouter cette fonction helper pour la pagination
function getPaginationData(int $page, int $totalItems, int $itemsPerPage): array {
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));
    $currentPage = min(max(1, $page), $totalPages);
    
    return [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'previousPage' => ($currentPage > 1) ? $currentPage - 1 : null,
        'nextPage' => ($currentPage < $totalPages) ? $currentPage + 1 : null,
        'itemsPerPage' => $itemsPerPage,
        'totalItems' => $totalItems,
        'hasNextPage' => $currentPage < $totalPages,
        'hasPreviousPage' => $currentPage > 1,
        'startItem' => ($currentPage - 1) * $itemsPerPage + 1,
        'endItem' => min($currentPage * $itemsPerPage, $totalItems)
    ];
}
?>