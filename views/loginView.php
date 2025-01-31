<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Page avec Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-3">
            <div id="errors">
                <?php
                if (isset($_SESSION['errors'])) {
                    foreach ($_SESSION['errors'] as $error) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
                    }
                    unset($_SESSION['errors']); // Clear errors after displaying
                }
                ?>
            </div>
            <form method="POST" action="http://127.0.0.1/projet/ecommercesami/index.php?page=login" autocomplete="off" id="login-form">
                <div class="mb-3">
                    <label for="username" class="form-label">Identifiant</label>
                    <input type="text" class="form-control" id="username" name="username" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" name="valid_login" id="valid-login-btn">Valider</button>
                </div>
            </form>
            <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="mt-3">
                    <a href="http://127.0.0.1/projet/ecommercesami/index.php?page=userlist" class="btn btn-secondary">Liste des utilisateurs</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>