<?php
// Vérification au début du fichier
if (!isset($orderData) || !$orderData) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-warning">Commande non trouvée</div>';
    echo '<a href="index.php?page=orderlist" class="btn btn-secondary">Retour à la liste</a>';
    echo '</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la commande #<?= htmlspecialchars($orderData['order']['id_commande']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .order-details .row { margin-bottom: 1rem; }
        .price { font-weight: bold; }
        @media print {
            .no-print { display: none !important; }
            .container { width: 100%; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Commande #<?= htmlspecialchars($orderData['order']['id_commande']) ?></h1>
            <div class="no-print">
                <a href="index.php?page=orderlist" class="btn btn-secondary">Retour</a>
                <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"></div>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Statut de la commande</h3>
                    <span class="badge bg-<?= getStatusColor($orderData['order']['statut']) ?>">
                        <?= htmlspecialchars($orderData['order']['statut']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Date de commande:</strong> <?= (new DateTime($orderData['order']['date_commande']))->format('d/m/Y H:i') ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Informations de livraison</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Nom:</strong> <?= htmlspecialchars($orderData['shipping_info']['nom']) ?></p>
                        <p><strong>Prénom:</strong> <?= htmlspecialchars($orderData['shipping_info']['prenom']) ?></p>
                        <p><strong>Adresse:</strong><br><?= nl2br(htmlspecialchars($orderData['shipping_info']['adresse'])) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Informations de paiement</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Carte:</strong> <?= htmlspecialchars($orderData['payment_info']['carte']) ?></p>
                        <p><strong>Date d'expiration:</strong> <?= htmlspecialchars($orderData['payment_info']['expiration']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Articles commandés</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Article</th>
                                <th class="text-center">Quantité</th>
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderData['details'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nom']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($item['quantite']) ?></td>
                                <td class="text-end"><?= number_format($item['prix'], 2, ',', ' ') ?> €</td>
                                <td class="text-end"><?= number_format($item['total_ligne'], 2, ',', ' ') ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total de la commande:</strong></td>
                                <td class="text-end"><strong><?= number_format($orderData['total'], 2, ',', ' ') ?> €</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/projet/ecommercesami/assets/js/components/cartComponent.js"></script>
</body>
</html>
