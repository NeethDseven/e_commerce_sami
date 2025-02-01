<?php
function getActivePromotions(PDO $pdo) {
    try {
        $sql = "SELECT 
                a.*,
                p.prix_promotionnel,
                p.date_debut,
                p.date_fin,
                ROUND(((a.prix - p.prix_promotionnel) / a.prix * 100), 2) as pourcentage_reduction
                FROM article a 
                INNER JOIN promotion p ON a.id_article = p.id_article 
                WHERE CURRENT_DATE BETWEEN p.date_debut AND p.date_fin
                ORDER BY pourcentage_reduction DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur SQL dans getActivePromotions: " . $e->getMessage());
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
        error_log("Erreur dans addPromotion: " . $e->getMessage());
        return false;
    }
}

function updatePromotion(PDO $pdo, $articleId, $data) {
    try {
        error_log('Début updatePromotion - Article ID: ' . $articleId);
        error_log('Données promotion reçues: ' . print_r($data, true));

        // Supprimer toute promotion existante
        $deleteStmt = $pdo->prepare("DELETE FROM promotion WHERE id_article = ?");
        $deleteStmt->execute([$articleId]);
        error_log('Anciennes promotions supprimées');

        // Si data est null, on s'arrête ici (suppression de promotion)
        if ($data === null) {
            error_log('Suppression de la promotion uniquement');
            return true;
        }

        // Validation stricte de la réduction
        $reduction = floatval($data['reduction_percent']);
        error_log('Réduction après conversion: ' . $reduction);

        if ($reduction <= 0 || empty($data['date_debut']) || empty($data['date_fin'])) {
            error_log('Données de promotion invalides');
            return false;
        }

        // Récupérer le prix de l'article
        $stmt = $pdo->prepare("SELECT prix FROM article WHERE id_article = ?");
        $stmt->execute([$articleId]);
        $prix = $stmt->fetchColumn();

        // Calculer le prix promotionnel avec la réduction exacte
        $prix_promotionnel = round($prix * (1 - ($reduction / 100)), 2);
        error_log("Prix original: $prix, Réduction exacte: $reduction%, Prix promotionnel calculé: $prix_promotionnel");

        // Insérer la nouvelle promotion
        $sql = "INSERT INTO promotion (id_article, prix_promotionnel, date_debut, date_fin) 
                VALUES (:id_article, :prix_promo, :date_debut, :date_fin)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':id_article' => $articleId,
            ':prix_promo' => $prix_promotionnel,
            ':date_debut' => $data['date_debut'],
            ':date_fin' => $data['date_fin']
        ]);

        error_log('Nouvelle promotion ' . ($result ? 'créée avec succès' : 'échec de création'));
        return $result;

    } catch (Exception $e) {
        error_log("Erreur dans updatePromotion: " . $e->getMessage());
        throw new Exception("Impossible de mettre à jour la promotion");
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
        error_log("Erreur dans getPromotionForArticle: " . $e->getMessage());
        return null;
    }
}
