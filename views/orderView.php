<?php
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Commandes</title>
</head>
<body>
    <div class="container mt-4">
        <h2>Mes Commandes</h2>
        <?php if (empty($orderData['orders'])): ?>
            <div class="alert alert-info">
                Vous n'avez pas encore de commandes.
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($orderData['orders'] as $order): ?>
                    <a href="index.php?page=orderDetails&id=<?= htmlspecialchars($order['id_commande']) ?>" 
                       class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Commande #<?= htmlspecialchars($order['id_commande']) ?></h5>
                            <small><?= htmlspecialchars($order['date_commande']) ?></small>
                        </div>
                        <p class="mb-1">
                            Statut: <?= htmlspecialchars($order['statut']) ?><br>
                            Articles: <?= htmlspecialchars($order['nombre_articles']) ?>
                        </p>
                        <small>Total: <?= number_format($order['total'], 2, ',', ' ') ?> €</small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
