<?php

function getCategories(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("
            SELECT id_categorie, nom, ordre 
            FROM categorie 
            WHERE id_categorie IS NOT NULL 
            ORDER BY ordre ASC
        ");
        
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log('Catégories récupérées: ' . print_r($categories, true));
        return $categories;
    } catch (PDOException $e) {
        error_log('Erreur getCategories: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération des catégories');
    }
}

function createCategory(PDO $pdo, array $data): int {
    try {
        $stmt = $pdo->prepare('INSERT INTO categorie (nom, ordre) VALUES (:nom, :ordre)');
        $stmt->execute([
            ':nom' => $data['nom'],
            ':ordre' => $data['ordre'] ?? 0
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Erreur createCategory: ' . $e->getMessage());
        throw new Exception('Erreur lors de la création de la catégorie');
    }
}

function updateCategory(PDO $pdo, array $data): bool {
    try {
        $stmt = $pdo->prepare('UPDATE categorie SET nom = :nom, ordre = :ordre WHERE id_categorie = :id');
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':ordre' => $data['ordre'],
            ':id' => $data['id_categorie']
        ]);
    } catch (PDOException $e) {
        error_log('Erreur updateCategory: ' . $e->getMessage());
        throw new Exception('Erreur lors de la mise à jour de la catégorie');
    }
}

function deleteCategory(PDO $pdo, int $id): bool {
    try {
        $stmt = $pdo->prepare('DELETE FROM categorie WHERE id_categorie = :id');
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Erreur deleteCategory: ' . $e->getMessage());
        throw new Exception('Erreur lors de la suppression de la catégorie');
    }
}

function getCategoryById(PDO $pdo, int $id): ?array {
    try {
        $stmt = $pdo->prepare('SELECT * FROM categorie WHERE id_categorie = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Erreur getCategoryById: ' . $e->getMessage());
        throw new Exception('Erreur lors de la récupération de la catégorie');
    }
}
