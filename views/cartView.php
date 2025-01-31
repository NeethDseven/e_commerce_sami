<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail de la Commande</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet"></head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Détail de votre commande</h1>
        
        <!-- Résumé de la commande -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">Résumé</h3>
            </div>
            <div class="card-body">
                <div id="cart-summary">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détail des articles -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">Articles</h3>
            </div>
            <div class="card-body">
                <div id="cart-container" class="row">
                    <!-- Le contenu sera remplacé par JavaScript -->
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="row mt-4">
            <div class="col-md-6">
                <a href="index.php" class="btn btn-secondary">Continuer mes achats</a>
            </div>
            <div class="col-md-6 text-end">
                <button id="clear-cart" class="btn btn-danger me-2">
                    Vider le panier
                </button>
                <button id="validate-cart" class="btn btn-success" style="display: none;">
                    Valider la commande
                </button>
            </div>
        </div>
    </div>
    
    <!-- Toast pour les notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cartToast" class="toast align-items-center text-white" role="alert">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="./assets/js/components/cartComponent.js">
    </script>
</body>
</html>