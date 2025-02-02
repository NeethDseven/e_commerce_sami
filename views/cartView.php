<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Mon Panier</title>
=======
    <title>Panier</title>
>>>>>>> origin/develop
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
<<<<<<< HEAD
        <h1 class="mb-4">Mon Panier</h1>
        
        <!-- Bouton pour vider le panier -->
        <div class="mb-3">
            <button id="clear-cart" class="btn btn-outline-danger">
                <i class="fas fa-trash"></i> Vider le panier
            </button>
        </div>

        <!-- Container pour les articles du panier -->
        <div class="row">
            <!-- Articles du panier -->
            <div class="col-md-8">
=======
        <h2>Mon Panier</h2>
        <div class="row">
            <div class="col-md-8">
                <!-- Conteneur du panier -->
>>>>>>> origin/develop
                <div id="cart-container" data-user-id="<?php echo htmlspecialchars($_SESSION['id_utilisateur']); ?>">
                    <!-- Le contenu du panier sera injecté ici par JavaScript -->
                </div>
            </div>
<<<<<<< HEAD

            <!-- Résumé du panier -->
            <div class="col-md-4">
=======
            <div class="col-md-4">
                <!-- Actions du panier -->
                <div class="mb-3">
                    <button type="button" id="clear-cart" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Vider le panier
                    </button>
                </div>
                <!-- Résumé du panier -->
>>>>>>> origin/develop
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
<<<<<<< HEAD
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="cartToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
=======
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cartToast" class="toast align-items-center text-white" role="alert" aria-live="assertive" aria-atomic="true">
>>>>>>> origin/develop
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