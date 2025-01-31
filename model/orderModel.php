<?php
function getOrderById($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT commande.*, utilisateur.nom as nom_utilisateur
        FROM commande 
        LEFT JOIN utilisateur ON commande.id_utilisateur = utilisateur.id_utilisateur
        WHERE commande.id_commande = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOrderDetails($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT article.prix as prix, article.nom, detail_commande.quantite
        FROM detail_commande
        JOIN article ON detail_commande.id_article = article.id_article
        WHERE detail_commande.id_commande = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateOrderTotal($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT SUM(article.prix * detail_commande.quantite) as total
        FROM detail_commande
        JOIN article ON detail_commande.id_article = article.id_article
        WHERE detail_commande.id_commande = ?
    ");
    $stmt->execute([$orderId]);
    return floatval($stmt->fetchColumn());
}

function updateOrderStatus($orderId, $newStatus) {
    global $pdo;
    try {
        if (!isValidStatus($newStatus)) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE commande SET statut = ? WHERE id_commande = ?");
        return $stmt->execute([$newStatus, $orderId]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
        return false;
    }
}

function getOrdersByStatus($status = null) {
    global $pdo;
    try {
        $sql = "
            SELECT 
                commande.id_commande,
                commande.id_utilisateur,
                commande.date_commande,
                commande.statut,
                utilisateur.nom as nom_utilisateur,
                COUNT(detail_commande.id_article) as nombre_articles,
                SUM(article.prix * detail_commande.quantite) as total
            FROM commande
            LEFT JOIN utilisateur ON commande.id_utilisateur = utilisateur.id_utilisateur
            LEFT JOIN detail_commande ON commande.id_commande = detail_commande.id_commande
            LEFT JOIN article ON detail_commande.id_article = article.id_article
        ";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE commande.statut = ?";
            $params[] = $status;
        }
        
        $sql .= " GROUP BY commande.id_commande, commande.date_commande, commande.statut, utilisateur.nom 
                  ORDER BY commande.date_commande DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des commandes: " . $e->getMessage());
        return [];
    }
}

// Fonction pour valider le statut
function isValidStatus($status) {
    return in_array($status, ['en cours', 'validée', 'annulée']);
}

function getAllOrders($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id_commande,
                c.date_commande,
                c.statut,
                u.nom as nom_utilisateur
            FROM commande c
            LEFT JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
            ORDER BY c.date_commande DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des commandes : " . $e->getMessage());
        return [];
    }
}

function saveOrderDetails($pdo, $orderId, $paymentInfo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE commande 
            SET 
                nom_livraison = :nom,
                prenom_livraison = :prenom,
                adresse_livraison = :adresse,
                carte_paiement = :carte,
                date_expiration = :expiration
            WHERE id_commande = :orderId
        ");
        
        // Masquer le numéro de carte avant stockage
        $maskedCard = '****' . substr($paymentInfo['carte'], -4);
        
        return $stmt->execute([
            'nom' => $paymentInfo['nom'],
            'prenom' => $paymentInfo['prenom'],
            'adresse' => $paymentInfo['adresse'],
            'carte' => $maskedCard,
            'expiration' => $paymentInfo['expiration'],
            'orderId' => $orderId
        ]);
    } catch (PDOException $e) {
        error_log('Erreur lors de la sauvegarde des détails de commande: ' . $e->getMessage());
        throw new Exception('Erreur lors de la sauvegarde des détails de commande');
    }
}

function getOrdersByUser($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id_commande,
                c.date_commande,
                c.statut,
                c.total,
                COUNT(dc.id_article) as nombre_articles
            FROM commande c
            LEFT JOIN ligne_commande dc ON c.id_commande = dc.id_commande
            WHERE c.id_utilisateur = :user_id
            GROUP BY c.id_commande
            ORDER BY c.date_commande DESC
        ");
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getOrdersByUser: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des commandes");
    }
}

function createOrder($pdo, $userId, $cartItems, $paymentInfo) {
    try {
        $pdo->beginTransaction();

        // Créer la commande
        $stmt = $pdo->prepare("INSERT INTO commandes (id_utilisateur, date_commande, statut) VALUES (:userId, NOW(), 'en_attente')");
        $stmt->execute(['userId' => $userId]);
        $orderId = $pdo->lastInsertId();

        // Insérer les détails de la commande
        $stmt = $pdo->prepare("INSERT INTO details_commande (id_commande, id_article, quantite, prix_unitaire) VALUES (:orderId, :articleId, :quantity, :price)");
        
        foreach ($cartItems as $item) {
            $stmt->execute([
                'orderId' => $orderId,
                'articleId' => $item['id_article'],
                'quantity' => $item['quantite'],
                'price' => $item['prix']
            ]);
        }

        // Vider le panier après la commande
        clearCart($pdo, $userId);

        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Erreur lors de la création de la commande : " . $e->getMessage());
    }
}
