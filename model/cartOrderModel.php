<?php

function isUserConnected(): bool {
    return isset($_SESSION['auth']) && $_SESSION['auth'] === true && isset($_SESSION['id_utilisateur']);
}

function getGuestIdentifier() {
    return $_SESSION['guest_id'] ?? session_id();
}

// Fonction unifiée pour vérifier l'article et son stock
function verifyArticle($pdo, $idArticle) {
    try {
        $stmt = $pdo->prepare("SELECT stock FROM article WHERE id_article = ?");
        $stmt->execute([$idArticle]);
        $stock = $stmt->fetchColumn();

        if ($stock === false) {
            throw new Exception("Article non trouvé");
        }

        return $stock;
    } catch (PDOException $e) {
        throw new Exception('Erreur lors de la vérification de l\'article');
    }
}

function addToCart($pdo, $idArticle, $quantite) {
    try {
        // Utiliser la fonction unifiée
        $stockDisponible = verifyArticle($pdo, $idArticle);

        if ($stockDisponible <= 0) {
            throw new Exception("Stock épuisé");
        }

        if ($quantite > $stockDisponible) {
            throw new Exception("Stock insuffisant. Il ne reste que $stockDisponible article(s) disponible(s)");
        }

        // Ajouter au panier selon le type d'utilisateur
        $result = isUserConnected() 
            ? addToUserCart($pdo, $idArticle, $quantite)
            : addToGuestCart($idArticle, $quantite);

        if (!$result) {
            throw new Exception("Erreur lors de l'ajout au panier");
        }

        return [
            'success' => true,
            'message' => 'Article ajouté au panier',
            'stock_restant' => $stockDisponible - $quantite
        ];
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

// Nouvelle fonction pour récupérer une commande en cours
function getCurrentOrder($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT id_commande FROM commande WHERE id_utilisateur = :id_utilisateur AND statut = 'en_cours'");
    $stmt->execute(['id_utilisateur' => $userId]);
    return $stmt->fetch();
}

// Nouvelle fonction pour créer une commande
function createOrder($pdo, $userId) {
    $stmt = $pdo->prepare("INSERT INTO commande (id_utilisateur, date_commande, statut) VALUES (:id_utilisateur, NOW(), 'en_cours')");
    $stmt->execute(['id_utilisateur' => $userId]);
    return $pdo->lastInsertId();
}

// Nouvelle fonction pour ajouter une ligne de commande
function addOrderLine($pdo, $commandeId, $articleId, $quantite) {
    $stmt = $pdo->prepare("INSERT INTO ligne_commande (id_commande, id_article, quantite) VALUES (:id_commande, :id_article, :quantite) ON DUPLICATE KEY UPDATE quantite = quantite + :quantite");
    return $stmt->execute([
        'id_commande' => $commandeId,
        'id_article' => $articleId,
        'quantite' => $quantite
    ]);
}

// Fonction modifiée pour utiliser les nouvelles fonctions
function addToUserCart($pdo, $idArticle, $quantite) {
    try {
        // Rechercher une commande en cours
        $commande = getCurrentOrder($pdo, $_SESSION['id_utilisateur']);

        // Créer une nouvelle commande si nécessaire
        $commandeId = $commande ? $commande['id_commande'] : createOrder($pdo, $_SESSION['id_utilisateur']);

        // Ajouter la ligne de commande
        addOrderLine($pdo, $commandeId, $idArticle, $quantite);

        return ['success' => true, 'message' => 'Article ajouté à la commande'];
    } catch (PDOException $e) {
        error_log('Error in addToUserCart: ' . $e->getMessage());
        throw new Exception('Erreur lors de l\'ajout au panier utilisateur');
    }
}

function addToGuestCart($idArticle, $quantite) {
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    
    if (!isset($_SESSION['panier'][$idArticle])) {
        $_SESSION['panier'][$idArticle] = 0;
    }
    $_SESSION['panier'][$idArticle] += $quantite;

    return [
        'success' => true,
        'guest_id' => getGuestIdentifier(),
        'message' => 'Article ajouté au panier invité'
    ];
}

function getCart($pdo) {
    return isUserConnected() ? getUserCart($pdo) : getGuestCart($pdo);
}

function getUserCart($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                lc.quantite,
                CAST(a.prix AS DECIMAL(10,2)) as prix,
                CAST(COALESCE(
                    (SELECT p.prix_promotionnel 
                     FROM promotion p 
                     WHERE p.id_article = a.id_article 
                     AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                     AND p.prix_promotionnel < a.prix
                     LIMIT 1
                    ), a.prix
                ) AS DECIMAL(10,2)) as prix_final
            FROM ligne_commande lc
            JOIN commande c ON c.id_commande = lc.id_commande
            JOIN article a ON a.id_article = lc.id_article
            WHERE c.id_utilisateur = :id_utilisateur 
            AND c.statut = 'en_cours'
        ");
        
        $stmt->execute(['id_utilisateur' => $_SESSION['id_utilisateur']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir les prix en nombres
        foreach ($items as &$item) {
            $item['prix'] = floatval($item['prix']);
            $item['prix_final'] = floatval($item['prix_final']);
            $item['quantite'] = intval($item['quantite']);
        }
        
        $total = array_reduce($items, function($sum, $item) {
            return $sum + ($item['prix_final'] * $item['quantite']);
        }, 0);

        return [
            'items' => $items,
            'total' => round($total, 2),
            'user_id' => $_SESSION['id_utilisateur']
        ];
    } catch (PDOException $e) {
        error_log('Error in getUserCart: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération du panier utilisateur');
    }
}

function getGuestCart($pdo) {
    try {
        $items = [];
        if (isset($_SESSION['panier'])) {
            foreach ($_SESSION['panier'] as $idArticle => $quantite) {
                $stmt = $pdo->prepare("
                    SELECT a.*,
                           COALESCE(
                               (SELECT p.prix_promotionnel 
                                FROM promotion p 
                                WHERE p.id_article = a.id_article 
                                AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                                AND p.prix_promotionnel < a.prix
                                LIMIT 1
                               ), a.prix
                           ) as prix_final
                    FROM article a 
                    WHERE a.id_article = ?
                ");
                $stmt->execute([$idArticle]);
                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($article) {
                    $article['quantite'] = $quantite;
                    $items[] = $article;
                }
            }
        }

        return [
            'items' => $items,
            'total' => array_reduce($items, function($sum, $item) {
                return $sum + ($item['prix_final'] * $item['quantite']);
            }, 0)
        ];
    } catch (Exception $e) {
        error_log('Error in getGuestCart: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération du panier invité');
    }
}

function removeFromCart($pdo, $idArticle, $quantite = null) {
    try {
        if (isUserConnected()) {
            return removeFromUserCart($pdo, $idArticle);
        } else {
            return removeFromGuestCart($idArticle);
        }
    } catch (Exception $e) {
        error_log('Erreur removeFromCart: ' . $e->getMessage());
        throw new Exception("Erreur lors de la suppression du panier");
    }
}

function removeFromUserCart($pdo, $idArticle) {
    try {
        $stmt = $pdo->prepare("
            DELETE lc FROM ligne_commande lc
            JOIN commande c ON c.id_commande = lc.id_commande
            WHERE c.id_utilisateur = :id_utilisateur 
            AND c.statut = 'en_cours'
            AND lc.id_article = :id_article
        ");
        $stmt->execute([
            'id_utilisateur' => $_SESSION['id_utilisateur'],
            'id_article' => $idArticle
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function removeFromGuestCart($idArticle) {
    if (isset($_SESSION['panier'][$idArticle])) {
        unset($_SESSION['panier'][$idArticle]);
        return true;
    }
    return false;
}

function updateCartQuantity($pdo, $idArticle, $newQuantity) {
    try {
        // Vérifier le stock réellement disponible
        $stockDisponible = verifyArticle($pdo, $idArticle);
        
        if ($stockDisponible <= 0) {
            throw new Exception("Stock indisponible");
        }

        if ($newQuantity > $stockDisponible) {
            throw new Exception("Stock insuffisant. Il ne reste que $stockDisponible article(s) disponible(s)");
        }

        // Le reste de la fonction reste inchangé
        if (isUserConnected()) {
            $stmt = $pdo->prepare("
                UPDATE ligne_commande lc
                JOIN commande c ON c.id_commande = lc.id_commande
                SET lc.quantite = :quantite
                WHERE c.id_utilisateur = :user_id 
                AND c.statut = 'en_cours'
                AND lc.id_article = :id_article
            ");
            
            $stmt->execute([
                'quantite' => $newQuantity,
                'user_id' => $_SESSION['id_utilisateur'],
                'id_article' => $idArticle
            ]);
        } else {
            if (!isset($_SESSION['panier'])) {
                $_SESSION['panier'] = [];
            }
            $_SESSION['panier'][$idArticle] = $newQuantity;
        }
        
        return [
            'success' => true,
            'stock_restant' => $stockDisponible - $newQuantity
        ];
    } catch (Exception $e) {
        error_log('Error in updateCartQuantity: ' . $e->getMessage());
        throw new Exception($e->getMessage());
    }
}

function clearCart($pdo) {
    try {
        if (isUserConnected()) {
            $stmt = $pdo->prepare("
                DELETE lc FROM ligne_commande lc
                JOIN commande c ON c.id_commande = lc.id_commande
                WHERE c.id_utilisateur = :id_utilisateur 
                AND c.statut = 'en_cours'
            ");
            $stmt->execute(['id_utilisateur' => $_SESSION['id_utilisateur']]);
        } else {
            $_SESSION['panier'] = [];
        }
        return true;
    } catch (Exception $e) {
        error_log('Error in clearCart: ' . $e->getMessage());
        throw new Exception('Erreur lors du vidage du panier');
    }
}
?>
