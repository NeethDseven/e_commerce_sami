<?php

function getActivePromotions(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.id_article,
                a.nom,
                a.description,
                a.image,
                CAST(a.prix AS DECIMAL(10,2)) as prix,
                CAST(p.prix_promotionnel AS DECIMAL(10,2)) as prix_promotionnel,
                p.date_debut,
                p.date_fin,
                c.nom as categorie_nom,
                a.stock,
                CASE 
                    WHEN p.prix_promotionnel IS NOT NULL 
                    AND p.prix_promotionnel < a.prix 
                    THEN ABS(ROUND(
                        ((a.prix - p.prix_promotionnel) / a.prix * 100),
                        1
                    ))
                    ELSE NULL 
                END as pourcentage_reduction
            FROM article a
            INNER JOIN promotion p ON a.id_article = p.id_article
            LEFT JOIN categorie c ON a.id_categorie = c.id_categorie
            WHERE CURRENT_DATE BETWEEN p.date_debut AND p.date_fin
            AND a.stock > 0
            AND a.prix > 0
            AND p.prix_promotionnel < a.prix
            HAVING pourcentage_reduction > 0
            ORDER BY pourcentage_reduction DESC"
        );
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Post-traitement des résultats
        return array_map(function($item) {
            if (isset($item['pourcentage_reduction'])) {
                $reduction = floatval($item['pourcentage_reduction']);
                // Calcul plus précis du pourcentage
                if ($reduction > 0 && $reduction < 0.1) {
                    $reduction = 0.1; // Minimum 0.1% si une réduction existe
                }
                $item['pourcentage_reduction'] = round($reduction, 1);
            }
            return $item;
        }, $results);
        
    } catch (PDOException $e) {
        error_log("Error in getActivePromotions: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des promotions");
    }
}

function addPromotion($pdo, $articleId, $reductionPercent, $dateDebut, $dateFin) {
    try {
        // Récupérer le prix original de l'article
        $stmt = $pdo->prepare("SELECT prix FROM article WHERE id_article = :id");
        $stmt->execute([':id' => $articleId]);
        $prixOriginal = $stmt->fetchColumn();

        // Limiter la réduction entre 0 et 60%
        $reductionPercent = min(60, max(0, $reductionPercent));
        
        // Calculer le prix promotionnel
        $prixPromotionnel = $prixOriginal * (1 - $reductionPercent / 100);

        $sql = "INSERT INTO promotion (id_article, prix_promotionnel, date_debut, date_fin) 
                VALUES (:article_id, :prix_promo, :date_debut, :date_fin)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':article_id' => $articleId,
            ':prix_promo' => $prixPromotionnel,
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function updatePromotion($pdo, $articleId, $data) {
    try {
        error_log("Updating promotion for article $articleId with data: " . print_r($data, true));
        
        if ($data === null) {
            $stmt = $pdo->prepare("DELETE FROM promotion WHERE id_article = ?");
            return $stmt->execute([$articleId]);
        }

        // Récupérer le prix original
        $stmt = $pdo->prepare("SELECT prix FROM article WHERE id_article = ?");
        $stmt->execute([$articleId]);
        $prix = floatval($stmt->fetchColumn());
        
        if ($prix <= 0) {
            throw new Exception("Prix de l'article invalide");
        }

        // Calculer et valider le prix promotionnel
        $reduction = abs(min(60, max(0, floatval($data['reduction_percent']))));
        $prixPromotionnel = $prix * (1 - ($reduction / 100));
        
        // Vérifier que le prix promotionnel est valide
        if ($prixPromotionnel >= $prix || $prixPromotionnel <= 0) {
            throw new Exception("Prix promotionnel invalide");
        }

        // Mettre à jour ou insérer la promotion
        $stmt = $pdo->prepare("SELECT id_promotion FROM promotion WHERE id_article = ?");
        $stmt->execute([$articleId]);
        
        if ($stmt->fetch()) {
            $sql = "UPDATE promotion 
                    SET prix_promotionnel = :prix_promo,
                        date_debut = :date_debut,
                        date_fin = :date_fin 
                    WHERE id_article = :id_article";
        } else {
            $sql = "INSERT INTO promotion 
                    (id_article, prix_promotionnel, date_debut, date_fin) 
                    VALUES 
                    (:id_article, :prix_promo, :date_debut, :date_fin)";
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id_article' => $articleId,
            ':prix_promo' => $prixPromotionnel,
            ':date_debut' => $data['date_debut'],
            ':date_fin' => $data['date_fin']
        ]);
    } catch (PDOException $e) {
        error_log("Error in updatePromotion: " . $e->getMessage());
        throw new Exception("Erreur lors de la mise à jour de la promotion");
    }
}

function removePromotion($pdo, $articleId) {
    try {
        error_log("Removing promotion for article $articleId");
        $stmt = $pdo->prepare("DELETE FROM promotion WHERE id_article = ?");
        return $stmt->execute([$articleId]);
    } catch (PDOException $e) {
        error_log("Error in removePromotion: " . $e->getMessage());
        throw new Exception("Erreur lors de la suppression de la promotion");
    }
}

function getPromotionForArticle($pdo, $articleId) {
    try {
        // Vérifier d'abord si l'article a une promotion active
        $sql = "SELECT p.*, 
                ROUND(((a.prix - p.prix_promotionnel) / a.prix * 100), 0) as pourcentage_reduction 
                FROM promotion p 
                JOIN article a ON p.id_article = a.id_article 
                WHERE p.id_article = :id 
                AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $articleId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucune promotion active n'est trouvée, retourner null
        if (!$result) {
            return null;
        }
        
        return $result;
    } catch (PDOException $e) {
        return null;
    }
}
