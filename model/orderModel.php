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

function getOrderDetails($pdo, $orderId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                c.nom_livraison as nom,
                c.prenom_livraison as prenom,
                c.email_livraison as email,
                c.adresse_livraison as adresse
            FROM commande c
            WHERE c.id_commande = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Récupérer les détails des articles
        $stmt = $pdo->prepare("
            SELECT 
                dc.id_article,
                dc.quantite,
                dc.prix_unitaire as prix,
                a.nom,
                a.description,
                (dc.quantite * dc.prix_unitaire) as total_ligne
            FROM detail_commande dc
            INNER JOIN article a ON dc.id_article = a.id_article
            WHERE dc.id_commande = ?
        ");
        $stmt->execute([$orderId]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer le total
        $total = array_reduce($details, function($sum, $item) {
            return $sum + $item['total_ligne'];
        }, 0);
        
        return [
            'order' => $order,
            'details' => $details,
            'total' => $total,
            'shipping_info' => [
                'nom' => $order['nom_livraison'],
                'prenom' => $order['prenom_livraison'],
                'adresse' => $order['adresse_livraison'],
                'email' => $order['email_livraison']
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur SQL dans getOrderDetails: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des détails de la commande");
    }
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
        $orderId = intval($orderId);
        if (!$orderId || !isValidStatus($newStatus)) {
            error_log("Invalid status update parameters - orderId: $orderId, status: $newStatus");
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE commande 
            SET statut = :status 
            WHERE id_commande = :orderId
        ");
        
        $result = $stmt->execute([
            ':status' => $newStatus,
            ':orderId' => $orderId
        ]);

        if (!$result) {
            error_log("Update failed for order $orderId");
            return false;
        }

        // Verify the update
        $verifyStmt = $pdo->prepare("
            SELECT statut FROM commande WHERE id_commande = ?
        ");
        $verifyStmt->execute([$orderId]);
        $currentStatus = $verifyStmt->fetchColumn();

        if ($currentStatus !== $newStatus) {
            error_log("Status verification failed - Expected: $newStatus, Got: $currentStatus");
            return false;
        }

        return true;
    } catch (PDOException $e) {
        error_log("Database error during status update: " . $e->getMessage());
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
    $validStatuses = [
        'en cours',
        'validée',
        'expédiée',
        'livrée',
        'annulée',
        'en attente'
    ];
    return in_array(strtolower(trim($status)), $validStatuses);
}

function getAllOrders($pdo, $page = 1, $perPage = 15) {
    $offset = ($page - 1) * $perPage;
    
    $query = "SELECT SQL_CALC_FOUND_ROWS 
                c.id_commande as id,
                c.date_commande,
                c.statut as status,
                u.nom as nom_client,
                (SELECT SUM(a.prix * dc.quantite) 
                 FROM detail_commande dc 
                 JOIN article a ON dc.id_article = a.id_article 
                 WHERE dc.id_commande = c.id_commande) as total
             FROM commande c
             LEFT JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
             ORDER BY c.date_commande DESC
             LIMIT :offset, :perPage";
             
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtenir le nombre total de commandes
    $totalRows = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    
    return [
        'orders' => $orders,
        'total' => $totalRows,
        'pages' => ceil($totalRows / $perPage),
        'currentPage' => $page
    ];
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

function getOrdersByUser($pdo, $userId = null, $email = null) {
    try {
        $params = [];
        $where = "";

        if ($userId !== null) {
            $where = "WHERE c.id_utilisateur = :user_id";
            $params['user_id'] = $userId;
        } elseif ($email !== null) {
            $where = "WHERE c.email_livraison = :email AND c.est_invite = 1";
            $params['email'] = $email;
        }

        $stmt = $pdo->prepare("
            SELECT 
                c.id_commande,
                c.date_commande,
                c.statut,
                c.total,
                COUNT(dc.id_article) as nombre_articles,
                c.nom_livraison,
                c.email_livraison,
                c.est_invite
            FROM commande c
            LEFT JOIN detail_commande dc ON c.id_commande = dc.id_commande
            $where
            GROUP BY c.id_commande
            ORDER BY c.date_commande DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getOrdersByUser: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des commandes");
    }
}

function createOrder($pdo, $userId, $cartItems, $paymentInfo, $guestId = null) {
    try {
        $pdo->beginTransaction();

        // Création de la commande
        $sql = "INSERT INTO commande (
            id_utilisateur,
            guest_id,
            date_commande,
            statut,
            est_invite,
            nom_livraison,
            prenom_livraison,
            email_livraison,
            adresse_livraison
        ) VALUES (
            :id_utilisateur,
            :guest_id,
            NOW(),
            'en cours',
            :est_invite,
            :nom_livraison,
            :prenom_livraison,
            :email_livraison,
            :adresse_livraison
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_utilisateur' => $userId,
            'guest_id' => $guestId,
            'est_invite' => ($userId === null) ? 1 : 0,
            'nom_livraison' => $paymentInfo['nom'],
            'prenom_livraison' => $paymentInfo['prenom'],
            'email_livraison' => $paymentInfo['email'],
            'adresse_livraison' => $paymentInfo['adresse']
        ]);

        $orderId = $pdo->lastInsertId();

        // Ajout des articles de la commande - Correction du nom de la table
        $stmt = $pdo->prepare("INSERT INTO detail_commande 
            (id_commande, id_article, quantite, prix_unitaire) 
            VALUES (:orderId, :articleId, :quantity, :price)");

        foreach ($cartItems as $item) {
            $stmt->execute([
                'orderId' => $orderId,
                'articleId' => $item['id_article'],
                'quantity' => $item['quantite'],
                'price' => $item['prix']
            ]);

            // Mettre à jour le stock
            $updateStock = $pdo->prepare("UPDATE article 
                SET stock = stock - :quantity 
                WHERE id_article = :articleId");
            $updateStock->execute([
                'quantity' => $item['quantite'],
                'articleId' => $item['id_article']
            ]);
        }

        $pdo->commit();
        return [
            'success' => true, 
            'orderId' => $orderId,
            'message' => 'Commande créée avec succès'
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur création commande: " . $e->getMessage());
        return [
            'success' => false, 
            'error' => $e->getMessage()
        ];
    }
}

// Ajout d'une fonction utilitaire pour la validation
function validateOrderData($cartItems, $paymentInfo) {
    $errors = [];
    
    if (empty($cartItems)) {
        $errors[] = "Le panier ne peut pas être vide";
    }
    
    $minRequiredFields = ['nom', 'email', 'adresse'];
    foreach ($minRequiredFields as $field) {
        if (empty($paymentInfo[$field])) {
            $errors[] = "Le champ $field est requis";
        }
    }
    
    if (!empty($paymentInfo['email']) && !filter_var($paymentInfo['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }
    
    return $errors;
}

function validateGuestData($data) {
    $required = ['nom', 'prenom', 'email', 'adresse'];
    $errors = [];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "Le champ $field est requis";
        }
    }

    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }

    return count($errors) === 0 ? true : $errors;
}
