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
                <!-- Le carrousel sera injecté ici -->
            </div>
        </div>
    </div>

    <!-- Ajouter du CSS personnalisé -->
    <style>
        #promotions-container {
            max-width: 1200px; /* Augmenter la largeur pour 3 articles */
            margin: 0 auto;
            position: relative;
        }
        .carousel-inner {
            padding: 1rem;
            margin: 0 auto;
        }
        .custom-carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            border: none;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .custom-carousel-button:hover {
            background-color: rgba(0, 0, 0, 0.9);
            cursor: pointer;
        }
        .carousel-control-prev.custom-carousel-button {
            left: calc(50% - 300px); /* Ajuster selon vos besoins */
        }
        .carousel-control-next.custom-carousel-button {
            right: calc(50% - 300px); /* Ajuster selon vos besoins */
        }
        .custom-carousel-icon {
            width: 24px;
            height: 24px;
            background-color: transparent;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            filter: brightness(2);
        }
        #carouselExampleRide {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .carousel-item .card {
            border: none;
        }
        .carousel-item .card-img-overlay {
            padding: 2rem;
        }
        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            background-color: rgba(0,0,0,0.5);
            border-radius: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 50px;
            width: 50px;
            opacity: 0.9;
        }
        .carousel-control-prev {
            left: -25px;
        }
        .carousel-control-next {
            right: -25px;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 30px;
            height: 30px;
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 15px;
        }
        .card {
            margin: 0 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        /* Assurer que les boutons sont au-dessus des cartes */
        .carousel-control-prev,
        .carousel-control-next {
            z-index: 10;
        }
        .carousel-indicators {
            bottom: -40px;
        }
        .carousel-indicators button {
            background-color: #666;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .carousel-indicators .active {
            background-color: #000;
        }
        .savings-badge {
            border-radius: 50%;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>

    <!-- Section des catégories -->
    <div class="row mb-4">
        <div class="col">
            <div id="category-buttons" class="mb-4">
                <button class="btn btn-outline-primary category-btn me-2" data-category="" data-order="0">
                    Toutes les catégories
                </button>
                <?php foreach ($categories as $category): ?>
                    <button class="btn btn-outline-primary category-btn me-2" 
                            data-category="<?= htmlspecialchars($category['id_categorie']) ?>"
                            data-order="<?= htmlspecialchars($category['ordre']) ?>">
                        <?= htmlspecialchars($category['nom']) ?>
                    </button>
                <?php endforeach; ?>
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
