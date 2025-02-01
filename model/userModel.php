<?php

function getUsersByPage(PDO $pdo, int $offset, int $limit): array
{
    $stmt = $pdo->prepare('SELECT id_utilisateur, nom, email, role FROM utilisateur LIMIT :limit OFFSET :offset');
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserCount(PDO $pdo) {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM utilisateur');
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function isEmailExists(PDO $pdo, string $email): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :email');
    $stmt->execute(['email' => $email]);
    return (bool)$stmt->fetchColumn();
}