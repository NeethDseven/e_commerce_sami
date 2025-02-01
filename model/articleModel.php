<?php
require_once __DIR__ . '/promotionModel.php';

function getArticles(PDO $pdo, array $options = []) {
    try {
        $sql = "SELECT 
                a.*, 
                c.nom as categorie_nom
                FROM article a 
                LEFT JOIN categorie c ON a.id_categorie = c.id_categorie";

        // Ajouter le JOIN pour les promotions si nécessaire
        if (!empty($options['promos_only'])) {
            $sql = "SELECT 
                    a.*, 
                    c.nom as categorie_nom,
                    p.prix_promotionnel,
                    p.date_debut,
                    p.date_fin
                    FROM article a 
                    LEFT JOIN categorie c ON a.id_categorie = c.id_categorie
                    INNER JOIN promotion p ON a.id_article = p.id_article
                    WHERE CURRENT_DATE BETWEEN p.date_debut AND p.date_fin";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $params = [];
        
        // Ajout des filtres
        if (!empty($options['category'])) {
            $sql .= " AND a.id_categorie = :category";
            $params[':category'] = $options['category'];
        }
        
        if (!empty($options['search'])) {
            $sql .= " AND (a.nom LIKE :search OR a.description LIKE :search)";
            $params[':search'] = '%' . $options['search'] . '%';
        }
        
        // Comptage pour pagination
        $countSql = str_replace(['a.*', 'c.nom as categorie_nom'], 'COUNT(DISTINCT a.id_article)', $sql);
        $stmtCount = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetchColumn();
        
        // Pagination
        $sql .= " ORDER BY a.id_article ASC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindValue(':limit', (int)($options['limit'] ?? 15), PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)($options['offset'] ?? 0), PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Modifier la partie qui ajoute les informations de promotion
        foreach ($articles as &$article) {
            $promotion = getPromotionForArticle($pdo, $article['id_article']);
            // Réinitialiser les valeurs de promotion si aucune promotion active n'est trouvée
            $article['prix_promotionnel'] = null;
            $article['date_debut'] = null;
            $article['date_fin'] = null;
            $article['pourcentage_reduction'] = null;
            
            if ($promotion) {
                $article['prix_promotionnel'] = $promotion['prix_promotionnel'];
                $article['date_debut'] = $promotion['date_debut'];
                $article['date_fin'] = $promotion['date_fin'];
                $article['pourcentage_reduction'] = $promotion['pourcentage_reduction'];
            }
        }
        
        return [$articles, $totalCount];
        
    } catch (PDOException $e) {
        error_log('Erreur SQL dans getArticles: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération des articles');
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
?>