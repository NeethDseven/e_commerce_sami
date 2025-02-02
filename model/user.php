<?php
<<<<<<< HEAD
if (!defined('USER_MODEL_INCLUDED')) {
    define('USER_MODEL_INCLUDED', true);

    function getUser(PDO $pdo, int $id_utilisateur): array | string
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query="SELECT * FROM utilisateur WHERE id_utilisateur = :id_utilisateur";
        $prep = $pdo->prepare($query);
        $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        try
        {
            $prep->execute();
        }
        catch (PDOException $e)
        {
            return " erreur : ".$e->getCode() .' :</b> '. $e->getMessage();
        }
        
        $res = $prep->fetch();
        $prep->closeCursor();
        
        return $res;
    }
    
    function insertUser(PDO $pdo, string $nom, string $password, string $email, string $role = 'utilisateur'): bool {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :email');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Un utilisateur avec cet email existe déjà");
            }

            $pdo->beginTransaction();
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mot_de_passe, :role)');
            
            $success = $stmt->execute([
                'nom' => $nom,
                'email' => $email,
                'mot_de_passe' => $hashedPassword,
                'role' => $role
            ]);

            if ($success) {
                $pdo->commit();
                return true;
            }

            $pdo->rollBack();
            throw new Exception("Échec de l'insertion en base de données");

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur d'insertion utilisateur: " . $e->getMessage());
            throw $e;
        }
    }
    
    function updateUser(
        PDO $pdo,
        int $id_utilisateur,
        string $nom,
        string $email,
        string $role,
        ?string $mot_de_passe = null
    ): bool | string {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role WHERE id_utilisateur = :id_utilisateur";
        $prep = $pdo->prepare($query);
        $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $prep->bindValue(':nom', $nom);
        $prep->bindValue(':email', $email);
        $prep->bindValue(':role', $role);
        try {
            $prep->execute();
        } catch (PDOException $e) {
            return "Erreur : " . $e->getCode() . ' : ' . $e->getMessage();
        }
        $prep->closeCursor();
        
        if (null !== $mot_de_passe) {
            $query = "UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id_utilisateur";
            $prep = $pdo->prepare($query);
            $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
            $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $prep->bindValue(':mot_de_passe', $hashedPassword);
            try {
                $prep->execute();
            } catch (PDOException $e) {
                return "Erreur : " . $e->getCode() . ' : ' . $e->getMessage();
            }
            $prep->closeCursor();
        }
        
        return true;
    }

    function deleteUser(PDO $pdo, int $id_utilisateur): bool | string {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = "DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur";
        $prep = $pdo->prepare($query);
        $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        try {
            $prep->execute();
        } catch (PDOException $e) {
            return "Erreur : " . $e->getCode() . ' : ' . $e->getMessage();
        }
        $prep->closeCursor();
        return true;
    }

    function getUsersByPage(PDO $pdo, int $offset, int $limit): array {
        try {
            $stmt = $pdo->prepare("SELECT id_utilisateur, nom, email, role FROM utilisateur ORDER BY id_utilisateur ASC LIMIT :limit OFFSET :offset");
            
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'exécution de la requête");
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results === false) {
                return [];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error in getUsersByPage: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des utilisateurs");
        }
    }

    function getUserCount(PDO $pdo): int {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateur");
            $count = $stmt->fetchColumn();
            return (int)$count;
        } catch (PDOException $e) {
            error_log("Error in getUserCount: " . $e->getMessage());
            throw new Exception("Erreur lors du comptage des utilisateurs");
        }
    }

    function getEnumValues(PDO $pdo, string $table, string $column): array {
        $query = "SHOW COLUMNS FROM $table LIKE '$column'";
        $stmt = $pdo->query($query);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return explode(",", str_replace("'", "", substr($row['Type'], 5, -1)));
    }

    function searchUsers($pdo, $search, $offset = 0, $limit = 15) {
        $search = "%$search%";
        $query = "SELECT * FROM utilisateur WHERE nom LIKE :search OR email LIKE :search ORDER BY id_utilisateur DESC LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':search', $search, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching users: " . $e->getMessage());
            return false;
        }
    }

    function getSearchUsersCount($pdo, $search) {
        $search = "%$search%";
        $query = "SELECT COUNT(*) FROM utilisateur WHERE nom LIKE :search OR email LIKE :search";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':search', $search, PDO::PARAM_STR);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting search results: " . $e->getMessage());
            return 0;
        }
    }

=======
	function getUser(PDO $pdo, int $id_utilisateur): array | string
	{
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$query="SELECT * FROM utilisateur WHERE id_utilisateur = :id_utilisateur";
		$prep = $pdo->prepare($query);
		$prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
		try
		{
			$prep->execute();
		}
		catch (PDOException $e)
		{
			return " erreur : ".$e->getCode() .' :</b> '. $e->getMessage();
		}
		
		$res = $prep->fetch();
		$prep->closeCursor();
		
		return $res;
	}
	
	function insertUser(PDO $pdo, string $nom, string $password, string $email, string $role = 'utilisateur'): bool {
    try {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Un utilisateur avec cet email existe déjà");
        }

        $pdo->beginTransaction();
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mot_de_passe, :role)');
        
        $success = $stmt->execute([
            'nom' => $nom,
            'email' => $email,
            'mot_de_passe' => $hashedPassword,
            'role' => $role
        ]);

        if ($success) {
            $pdo->commit();
            return true;
        }

        $pdo->rollBack();
        throw new Exception("Échec de l'insertion en base de données");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur d'insertion utilisateur: " . $e->getMessage());
        throw $e;
    }
}
	
	function updateUser(
    PDO $pdo,
    int $id_utilisateur,
    string $nom,
    string $email,
    string $role,
    ?string $mot_de_passe = null
): bool | string {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role WHERE id_utilisateur = :id_utilisateur";
    $prep = $pdo->prepare($query);
    $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
    $prep->bindValue(':nom', $nom);
    $prep->bindValue(':email', $email);
    $prep->bindValue(':role', $role);
    try {
        $prep->execute();
    } catch (PDOException $e) {
        return "Erreur : " . $e->getCode() . ' : ' . $e->getMessage();
    }
    $prep->closeCursor();
    
    if (null !== $mot_de_passe) {
        $query = "UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id_utilisateur";
        $prep = $pdo->prepare($query);
        $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $prep->bindValue(':mot_de_passe', $mot_de_passe);
        try {
            $prep->execute();
        } catch (PDOException $e) {
            return "Erreur : " . $e->getCode() . ' : ' . $e->getMessage();
        }
        $prep->closeCursor();
    }
    
    return true;
}

function deleteUser(PDO $pdo, int $id_utilisateur): bool | string {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur";
    $prep = $pdo->prepare($query);
    $prep->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
    try {
        $prep->execute();
    } catch (PDOException $e) {
        return "Erreur : " . $e->getCode() . ' : ' . $e->getMessage();
    }
    $prep->closeCursor();
    return true;
}



function getUsersByPage(PDO $pdo, int $offset, int $limit): array {
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur ORDER BY id_utilisateur DESC LIMIT :limit OFFSET :offset");
        
        // Bind des paramètres de manière explicite
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results === false) {
            return [];
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("Error in getUsersByPage: " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des utilisateurs");
    }
}

function getUserCount(PDO $pdo): int {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateur");
        $count = $stmt->fetchColumn();
        return (int)$count;
    } catch (PDOException $e) {
        error_log("Error in getUserCount: " . $e->getMessage());
        throw new Exception("Erreur lors du comptage des utilisateurs");
    }
}

function getEnumValues(PDO $pdo, string $table, string $column): array {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
    return explode("','", $matches[1]);
>>>>>>> origin/develop
}
