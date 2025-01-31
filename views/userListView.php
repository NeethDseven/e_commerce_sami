<?php
// Vérification de l'accès
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Définir la page courante par défaut si non spécifiée
if (!isset($_GET['currentPage'])) {
    header('Location: index.php?page=userlist&currentPage=1');
    exit();
}

/** @var TYPE_NAME $pdo */
$roles = getEnumValues($pdo, 'utilisateur', 'role');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success mt-3">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <h1 class="mt-4">Gestion des utilisateurs</h1>
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <input type="search" class="form-control" id="searchInput" placeholder="Rechercher un utilisateur...">
            <button class="btn btn-outline-primary" id="searchButton">
                <i class="fas fa-search"></i> Rechercher
            </button>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            Créer un nouvel utilisateur
        </button>
    </div>
    
    <!-- Conteneur pour le spinner et la liste des utilisateurs -->
    <div id="user-list-container">
        <!-- La liste des utilisateurs sera injectée ici dynamiquement -->
    </div>

    <!-- Conteneur pour la pagination -->
    <div id="pagination-container">
        <!-- La pagination sera injectée ici dynamiquement -->
    </div>

    
    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination" id="pagination">
            <!-- Rempli dynamiquement par JavaScript -->
        </ul>
    </nav>
</div>

<!-- Modal de création d'utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createUserForm" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="create_nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="create_nom" name="nom" 
                               required minlength="2" maxlength="50" autocomplete="name">
                        <div class="invalid-feedback">Le nom doit contenir entre 2 et 50 caractères</div>
                    </div>
                    <div class="mb-3">
                        <label for="create_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="create_email" name="email" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="create_mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="create_mot_de_passe" 
                               name="mot_de_passe" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="create_confirmation" class="form-label">Confirmation</label>
                        <input type="password" class="form-control" id="create_confirmation" 
                               name="confirmation" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="create_role" class="form-label">Rôle</label>
                        <select class="form-control" id="create_role" name="role" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role); ?>">
                                    <?php echo htmlspecialchars($role); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'édition d'utilisateur -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Modifier Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <div class="mb-3">
                        <label for="edit_nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="edit_mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="edit_mot_de_passe" 
                               name="mot_de_passe" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rôle</label>
                        <select class="form-control" id="edit_role" name="role" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role); ?>">
                                    <?php echo htmlspecialchars($role); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="saveEditUserButton">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialisation des composants Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        // Activer la validation des formulaires Bootstrap
        var forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
</script>
<script type="module" src="assets/js/components/userListComponent.js"></script>
</body>
</html>
