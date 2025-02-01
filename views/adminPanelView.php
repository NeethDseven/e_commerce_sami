<?php
if (!isset($_SESSION['auth']) || $_SESSION['Role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$articles = $articles ?? [];
$categories = $categories ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des Articles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Articles</h1>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#articleModal">
                    <i class="fas fa-plus"></i> Ajouter un Article
                </button>
                <a href="index.php?page=categoryManagement" class="btn btn-secondary">
                    <i class="fas fa-tags"></i> Gérer les Catégories
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <select class="form-select" id="searchCategory" style="max-width: 200px;">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?= htmlspecialchars($categorie['id_categorie']) ?>">
                                <?= htmlspecialchars($categorie['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un article...">
                    <button class="btn btn-outline-secondary" type="button" id="searchButton">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-outline-danger" id="filterPromos">
                    <i class="fas fa-percentage"></i> Voir les promotions
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 5%">ID</th>
                        <th class="text-center" style="width: 10%">Image</th>
                        <th style="width: 15%">Nom</th>
                        <th class="text-end" style="width: 10%">Prix normal</th>
                        <th class="text-end" style="width: 10%">Prix promo</th>
                        <th class="text-center" style="width: 10%">Réduction</th>
                        <th class="text-center" style="width: 15%">Dates promo</th>
                        <th class="text-center" style="width: 5%">Stock</th>
                        <th style="width: 10%">Catégorie</th>
                        <th class="text-end" style="width: 10%">Actions</th>
                    </tr>
                </thead>
                <tbody id="articlesTable">
                    <!-- Le contenu sera généré par JavaScript -->
                </tbody>
            </table>
        </div>

        <nav aria-label="Navigation des pages">
            <ul class="pagination justify-content-center" id="pagination">
                <!-- La pagination sera générée dynamiquement par JavaScript -->
            </ul>
        </nav>
    </div>

    <div class="modal fade" id="articleModal" tabindex="-1" aria-labelledby="articleModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="articleModalLabel">Gestion d'article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <form id="articleForm" enctype="multipart/form-data">
                        <input type="hidden" id="id_article" name="id_article">
                        
                        <div class="mb-3">
                            <label for="article_nom" class="form-label">Nom de l'article</label>
                            <input type="text" class="form-control" id="article_nom" name="nom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="article_description" class="form-label">Description</label>
                            <textarea class="form-control" id="article_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="article_prix" class="form-label">Prix (€)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="article_prix" name="prix" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="article_stock" class="form-label">Stock</label>
                                <input type="number" min="0" class="form-control" id="article_stock" name="stock" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie" required>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?= htmlspecialchars($categorie['id_categorie']) ?>">
                                        <?= htmlspecialchars($categorie['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Remplacer la section des champs de promotion -->
                        <div class="mb-3">
                            <label class="form-label">Promotion</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_promotion" name="has_promotion">
                                <label class="form-check-label" for="has_promotion">
                                    Activer la promotion
                                </label>
                            </div>
                            <div id="promotion_fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="reduction_percent" class="form-label">Réduction (%)</label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="reduction_percent" 
                                                   name="reduction_percent"
                                                   min="0" 
                                                   max="60" 
                                                   step="1"
                                                   pattern="\d*"
                                                   inputmode="numeric"
                                                   placeholder="Entre 0 et 60">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small class="text-muted">Réduction maximum : 60%</small>
                                        <div id="prix_calcule" class="form-text text-success mt-2"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_debut" class="form-label">Date début</label>
                                        <input type="date" class="form-control" id="date_debut" name="date_debut">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_fin" class="form-label">Date fin</label>
                                        <input type="date" class="form-control" id="date_fin" name="date_fin">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div id="currentImage" class="mt-2"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" form="articleForm">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="assets/js/components/adminPanel.js"></script>
</body>
</html>
