<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
</head>
<div class="container">
    <?php if (isset($error)): ?>
        <p class="alert alert-danger"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="row mb-4">
            <div class="col">
                <a href="index.php?page=userlist" class="btn btn-secondary">Liste des utilisateurs</a>
            </div>
            <div class="col">
                <a href="index.php?page=admin" class="btn btn-secondary">Gestion du Site</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h3 class="text-center mb-4">Nos Promotions</h3>
            <div id="promotions-container" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                </div>
                <div class="carousel-inner">
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#promotions-container" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark rounded-circle" aria-hidden="true"></span>
                    <span class="visually-hidden">Précédent</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promotions-container" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark rounded-circle" aria-hidden="true"></span>
                    <span class="visually-hidden">Suivant</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div id="category-buttons" class="mb-4">
            </div>
        </div>
    </div>

    <div id="article" class="row"></div>

    <div id="pagination-container" class="mt-4">
        <nav aria-label="Navigation des pages">
            <ul class="pagination justify-content-center">
            </ul>
        </nav>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">Article ajouté au panier</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="./assets/js/components/componentArticle.js"></script>
<script type="module" src="./assets/js/components/componentCategorie.js"></script>
<script type="module" src="./assets/js/components/promotionComponent.js"></script>

