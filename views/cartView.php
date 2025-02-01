<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Mon Panier</h2>
        <div class="row">
            <div class="col-md-8">
                <!-- Conteneur du panier -->
                <div id="cart-container" data-user-id="<?php echo htmlspecialchars($_SESSION['id_utilisateur']); ?>">
                    <!-- Le contenu du panier sera injecté ici par JavaScript -->
                </div>
            </div>
            <div class="col-md-4">
                <!-- Actions du panier -->
                <div class="mb-3">
                    <button type="button" id="clear-cart" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Vider le panier
                    </button>
                </div>
                <!-- Résumé du panier -->
                <div id="cart-summary">
                    <!-- Le résumé sera injecté ici par JavaScript -->
                </div>
            </div>
        </div>

        <!-- Bouton retour -->
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Continuer mes achats
            </a>
        </div>
    </div>

    <!-- Toast pour les notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cartToast" class="toast align-items-center text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="./assets/js/components/cartComponent.js"></script>
</body>
</html>