<?php
require("_partials/errors.php");
?>
<div class="row">
    <div class="col">
        <div class="h1 pt-2 pb-2 text-center">
            <?php echo isset($user['id_utilisateur']) ? 'Modifier' : 'Créer'; ?> un utilisateur
        </div>
    
        <form id="utilisateur-form" novalidate>
            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" class="form-control" id="nom" name="nom" 
                       value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required autocomplete="name">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" 
                       <?php echo (!isset($user['id_utilisateur'])) ? 'required' : ''; ?> autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label for="confirmation" class="form-label">Confirmation du mot de passe</label>
                <input type="password" class="form-control" id="confirmation" name="confirmation" 
                       <?php echo (!isset($user['id_utilisateur'])) ? 'required' : ''; ?> autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rôle</label>
                <select class="form-control" id="role" name="role" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars($role); ?>" 
                                <?php echo (isset($user['role']) && $user['role'] === $role) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3 d-flex justify-content-between">
                <a href="?page=userlist" class="btn btn-secondary">Retour</a>
                <button type="submit" class="btn btn-primary" name="<?php echo isset($user['id_utilisateur']) ? 'update' : 'create'; ?>_button">
                    <?php echo isset($user['id_utilisateur']) ? 'Modifier' : 'Créer'; ?>
                </button>
            </div>
        </form>
    </div>
</div>