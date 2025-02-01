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
function createCartOrder($pdo, $userId) {
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
        $commandeId = $commande ? $commande['id_commande'] : createCartOrder($pdo, $_SESSION['id_utilisateur']);

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
    try {
        // Vérifier si l'utilisateur est connecté
        $userId = $_SESSION['id_utilisateur'] ?? null;
        
        if (!$userId) {
            return [
                'items' => [],
                'total' => 0
            ];
        }

        $stmt = $pdo->prepare("
            SELECT a.*, c.quantite, 
                   CAST(a.prix AS DECIMAL(10,2)) as prix,
                   CAST(COALESCE(p.prix_promotionnel, a.prix) AS DECIMAL(10,2)) as prix_final
            FROM panier c 
            JOIN article a ON c.id_article = a.id_article 
            LEFT JOIN promotion p ON a.id_article = p.id_article 
                AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin
            WHERE c.id_utilisateur = ?
        ");
        
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = array_reduce($items, function($sum, $item) {
            return $sum + ($item['prix_final'] * $item['quantite']);
        }, 0);

        return [
            'items' => $items,
            'total' => round($total, 2)
        ];
    } catch (PDOException $e) {
        error_log('Erreur getCart: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération du panier');
    }
}

function getUserCart($pdo) {
    try {
        if (!isset($_SESSION['id_utilisateur'])) {
            throw new Exception('Utilisateur non connecté');
        }

        $userId = $_SESSION['id_utilisateur'];

        // Vérifier s'il y a une commande en cours
        $commande = getCurrentOrder($pdo, $userId);
        if (!$commande) {
            // Créer une nouvelle commande si aucune n'existe
            $commandeId = createCartOrder($pdo, $userId);
        } else {
            $commandeId = $commande['id_commande'];
        }

        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                lc.quantite,
                lc.id_commande,
                CAST(a.prix AS DECIMAL(10,2)) as prix,
                CAST(COALESCE(
                    (SELECT p.prix_promotionnel 
                     FROM promotion p 
                     WHERE p.id_article = a.id_article 
                     AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                     LIMIT 1
                    ), a.prix
                ) AS DECIMAL(10,2)) as prix_final
            FROM article a
            LEFT JOIN ligne_commande lc ON a.id_article = lc.id_article
            LEFT JOIN commande c ON lc.id_commande = c.id_commande
            WHERE c.id_utilisateur = :user_id 
            AND c.statut = 'en_cours'
            AND c.id_commande = :commande_id
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'commande_id' => $commandeId
        ]);
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer le total
        $total = array_reduce($items, function($sum, $item) {
            return $sum + ($item['prix_final'] * $item['quantite']);
        }, 0);

        return [
            'items' => $items,
            'total' => round($total, 2),
            'user_id' => $userId,
            'commande_id' => $commandeId
        ];
    } catch (PDOException $e) {
        error_log('Error in getUserCart: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération du panier utilisateur');
    }
}

function getGuestCart($pdo) {
    try {
        // S'assurer que la session est démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $items = [];
        $total = 0;

        // Initialiser le panier si nécessaire
        if (!isset($_SESSION['panier'])) {
            $_SESSION['panier'] = [];
        }

        // Si le panier n'est pas vide
        if (!empty($_SESSION['panier'])) {
            foreach ($_SESSION['panier'] as $idArticle => $quantite) {
                $stmt = $pdo->prepare("
                    SELECT 
                        a.*,
                        COALESCE(
                            (SELECT p.prix_promotionnel 
                             FROM promotion p 
                             WHERE p.id_article = a.id_article 
                             AND CURRENT_DATE BETWEEN p.date_debut AND p.date_fin 
                             LIMIT 1
                            ), 
                            a.prix
                        ) as prix_final
                    FROM article a 
                    WHERE a.id_article = ?
                ");
                
                $stmt->execute([$idArticle]);
                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($article) {
                    $article['quantite'] = $quantite;
                    $items[] = $article;
                    $total += $article['prix_final'] * $quantite;
                }
            }
        }

        return [
            'items' => $items,
            'total' => round($total, 2)
        ];

    } catch (PDOException $e) {
        error_log('Erreur SQL dans getGuestCart: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération du panier invité');
    } catch (Exception $e) {
        error_log('Erreur dans getGuestCart: ' . $e->getMessage());
        throw new Exception('Erreur inattendue lors de la récupération du panier');
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
            DELETE ligne_commande FROM ligne_commande
            JOIN commande ON commande.id_commande = ligne_commande.id_commande
            WHERE commande.id_utilisateur = :id_utilisateur 
            AND commande.statut = 'en_cours'
            AND ligne_commande.id_article = :id_article
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
                UPDATE ligne_commande
                JOIN commande ON commande.id_commande = ligne_commande.id_commande
                SET ligne_commande.quantite = :quantite
                WHERE commande.id_utilisateur = :user_id 
                AND commande.statut = 'en_cours'
                AND ligne_commande.id_article = :id_article
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
                DELETE ligne_commande FROM ligne_commande
                JOIN commande ON commande.id_commande = ligne_commande.id_commande
                WHERE commande.id_utilisateur = :id_utilisateur 
                AND commande.statut = 'en_cours'
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

function validateOrder($pdo, $userId) {
    try {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_utilisateur'] != $userId) {
            throw new Exception('Utilisateur non autorisé');
        }

        $pdo->beginTransaction();

        // Récupérer la commande en cours
        $stmt = $pdo->prepare("
            SELECT c.id_commande, lc.id_article, lc.quantite, a.stock
            FROM commande c
            JOIN ligne_commande lc ON c.id_commande = lc.id_commande
            JOIN article a ON lc.id_article = a.id_article
            WHERE c.id_utilisateur = :user_id AND c.statut = 'en_cours'
        ");
        $stmt->execute(['user_id' => $userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new Exception('Panier vide');
        }

        // Vérifier et mettre à jour le stock
        foreach ($items as $item) {
            if ($item['quantite'] > $item['stock']) {
                $pdo->rollBack();
                throw new Exception('Stock insuffisant pour l\'article #' . $item['id_article']);
            }

            // Mettre à jour le stock
            $stmt = $pdo->prepare("
                UPDATE article 
                SET stock = stock - :quantite 
                WHERE id_article = :id_article
            ");
            $stmt->execute([
                'quantite' => $item['quantite'],
                'id_article' => $item['id_article']
            ]);
        }

        // Valider la commande
        $stmt = $pdo->prepare("
            UPDATE commande 
            SET statut = 'validée', 
                date_validation = NOW() 
            WHERE id_commande = :id_commande
        ");
        $stmt->execute(['id_commande' => $items[0]['id_commande']]);

        $pdo->commit();
        return ['success' => true, 'commande_id' => $items[0]['id_commande']];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error in validateOrder: ' . $e->getMessage());
        throw new Exception('Erreur lors de la validation de la commande: ' . $e->getMessage());
    }
}

?>
