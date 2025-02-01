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

    <!-- Section des promotions -->
    <div class="row mb-4">
        <div class="col">
            <h3 class="text-center mb-4">Nos Promotions</h3>
            <div id="promotions-container">
                <!-- Le carrousel sera injecté ici via promotions.js -->
            </div>
        </div>
    </div>

    <!-- Section des catégories -->
    <div class="row mb-4">
        <div class="col">
            <div id="category-buttons" class="mb-4">
                <!-- Les boutons de catégorie seront injectés ici via componentCategorie.js -->
            </div>
        </div>
    </div>

    <!-- Conteneur pour les articles -->
    <div id="article" class="row"></div>

    <!-- Conteneur pour la pagination -->
    <div id="pagination-container" class="mt-4">
        <nav aria-label="Navigation des pages">
            <ul class="pagination justify-content-center">
            </ul>
        </nav>
    </div>

    <!-- Toast pour les notifications -->
    <div class="toast-container position-absolute top-0 end-0 p-3">
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive"
            aria-atomic="true" id="toast">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="./assets/js/components/componentArticle.js"></script>
<script type="module" src="./assets/js/components/componentCategorie.js"></script>
<script type="module" src="./assets/js/promotions.js"></script>
