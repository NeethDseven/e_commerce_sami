<?php

function checkUserExists(PDO $pdo, string $email, string $username): bool {
    $query = "SELECT COUNT(*) FROM utilisateur WHERE email = :email OR nom = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    
    return (bool) $stmt->fetchColumn();
}

function registerUser(PDO $pdo, string $nom, string $email, string $password): bool|string {
    try {
        // Vérifier si l'utilisateur existe déjà
        if (checkUserExists($pdo, $email, $nom)) {
            return "Un utilisateur avec cet email ou ce nom existe déjà";
        }

        $pdo->beginTransaction();

        // Hash du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertion de l'utilisateur avec le rôle 'user'
        $query = "INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (:nom, :email, :password, 'utilisateur')";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password', $hashed_password);
        
        $result = $stmt->execute();
        
        if ($result) {
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return "Erreur lors de l'insertion de l'utilisateur";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return "Erreur lors de l'inscription : " . $e->getMessage();
    }
}
