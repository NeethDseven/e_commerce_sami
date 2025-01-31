<?php

function connect($pdo, $identifier, $password) {
    try {
        // Debug
        error_log("Tentative de connexion - Recherche utilisateur avec identifiant: $identifier");
        
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE nom = :identifier OR email = :identifier");
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug
        if ($user) {
            error_log("Utilisateur trouvé avec l'identifiant: $identifier");
        } else {
            error_log("Aucun utilisateur trouvé avec l'identifiant: $identifier");
        }
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            unset($user['mot_de_passe']);
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erreur de connexion : " . $e->getMessage());
        throw new Exception("Erreur lors de la connexion");
    }
}