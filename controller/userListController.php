<?php

require __DIR__ . '/../model/userModel.php';
include __DIR__ . '/../includes/database.php';

/**
 * @var PDO $pdo
 */

$page = isset($_GET['currentPage']) ? (int)$_GET['currentPage'] : 1;
$limit = 15;
$offset = max(0, ($page - 1) * $limit);

$users = getUsersByPage($pdo, $offset, $limit);
$totalUsers = getUserCount($pdo);
$totalPages = ceil($totalUsers / $limit);

header('Content-Type: application/json');
echo json_encode([
    'users' => $users,
    'total' => $totalUsers,
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'success' => true
]);