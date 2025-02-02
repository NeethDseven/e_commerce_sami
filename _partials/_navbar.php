<?php
$action = $action ?? 'default_action';
$page = $_GET['page'] ?? '';

// S'assurer qu'on n'envoie pas de sortie si on est en train de gérer une redirection
if (headers_sent()) {
    return;
}
?>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php" data-nav="home">Accueil</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=userlist&currentPage=1">Liste des Utilisateurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin" onclick="window.location.href=this.href; return false;">Gestion du Site</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto">
                <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] === true): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=logout" data-nav="auth">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=login" data-nav="auth">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link modal-trigger" href="#" 
                           data-nav="auth" 
                           data-action="register" 
                           data-bs-toggle="modal" 
                           data-bs-target="#inscriptionModal"
                           onclick="event.stopPropagation();">S'inscrire</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=orderView" data-nav="order">Ma Commande</a>
                </li>
            </ul>
            <form class="d-flex" role="search" method="GET" action="index.php" id="navSearchForm">
                <?php 
                $excludedPages = ['userlist', 'orderView', 'admin', 'categoryManagement', 'orderlist', 'orderList'];
                if (!in_array($page, $excludedPages)): 
                ?>
                    <select class="form-select me-2" name="category" id="navSearchCategory" data-current="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>">
                        <option value="">Categories</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int)$category['id_categorie']; ?>"
                                        <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id_categorie']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input class="form-control" type="search" name="search" id="navSearchInput" 
                           placeholder="Rechercher..." aria-label="Search" 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>
</nav>

<!-- Modal d'inscription -->
<div class="modal fade" id="inscriptionModal" tabindex="-1" aria-labelledby="inscriptionModalLabel" aria-hidden="true" data-modal="auth">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inscriptionModalLabel">Inscription</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isset($_SESSION['errors'])): ?>
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <form id="inscription-form" method="POST" action="index.php?page=register">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" name="register">S'inscrire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>