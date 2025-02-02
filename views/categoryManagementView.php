<?php
if (!isset($_SESSION['auth']) || $_SESSION['Role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Catégories</h1>
            <div>
                <button id="toggleSortMode" class="btn btn-warning me-2">
                    <i class="fas fa-sort"></i> Mode tri
                </button>
                <button id="saveSortOrder" class="btn btn-success me-2" style="display: none;">
                    <i class="fas fa-save"></i> Enregistrer l'ordre
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    Ajouter une Catégorie
                </button>
                <a href="index.php?page=admin" class="btn btn-secondary ms-2">
                    Retour à la gestion des articles
                </a>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Ordre d'affichage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="categoriesTable" class="sortable">
<<<<<<< HEAD
=======
                <!-- Le contenu sera chargé dynamiquement -->
>>>>>>> origin/develop
            </tbody>
        </table>
    </div>

<<<<<<< HEAD
=======
    <!-- Modal pour ajouter/modifier une catégorie -->
>>>>>>> origin/develop
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm" onsubmit="return false;" autocomplete="off">
                        <input type="hidden" name="id_categorie" id="categoryId" value="">
                        
                        <div class="mb-3">
                            <label for="category_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="category_nom" name="nom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_ordre" class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" id="category_ordre" name="ordre" 
                                   value="0" min="0" step="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="saveCategory">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="assets/js/components/categoryManagement.js" type="module"></script>
<<<<<<< HEAD
    
=======
    <script>
        // Configuration globale des toasts
        document.addEventListener('DOMContentLoaded', () => {
            const toastElList = document.querySelectorAll('.toast');
            toastElList.forEach(toastEl => {
                const toast = new bootstrap.Toast(toastEl, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
            });
        });
    </script>
>>>>>>> origin/develop
</body>
</html>
