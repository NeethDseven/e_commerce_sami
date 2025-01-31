<?php
session_start();

// Ajouter ce bloc au début du fichier pour gérer les erreurs JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

require_once 'includes/database.php';
include_once 'model/articleModel.php';
include_once 'model/user.php';

// Empêcher la mise en mémoire tampon de la sortie
ob_start();

// Gestion des requêtes AJAX en premier
if (isset($_GET['controller'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
              
    if ($isAjax) {
        $controller = $_GET['controller'];
        switch ($controller) {
            case 'category':
                require_once 'controller/categoryController.php';
                exit();
            case 'cart':
                require_once 'controller/cartOrderController.php';
                exit();
            default:
                $controllerFile = "controller/{$controller}Controller.php";
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    exit();
                }
        }
    }
}

// Debug des chemins
if (isset($_GET['debug'])) {
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    error_log('Script filename: ' . $_SERVER['SCRIPT_FILENAME']);
}

// Détecter si c'est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Si c'est une requête AJAX, traiter uniquement le contrôleur demandé
if ($isAjax) {
    ob_clean(); // Nettoyer tout buffer existant
    $controller = $_GET['controller'] ?? '';
    
    switch ($controller) {
        case 'cart':
            require_once 'controller/cartOrderController.php';
            break;
        case 'promotion':
            require_once 'controller/promotionController.php';
            exit();
        // ...existing cases...
    }
    exit; // Arrêter l'exécution après avoir géré la requête AJAX
}

/** @var \PDO $pdo */

// Gestion de la session invité
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    $_SESSION['auth'] = false;
    $_SESSION['Role'] = 'guest';
    $_SESSION['guest_id'] = session_id(); // Utiliser l'ID de session comme identifiant invité
}

$categories = getCategories($pdo);

include '_partials/_navbar.php';

$page = $_GET['page'] ?? 'accueil';

if ($page === 'login') {
    include 'controller/loginController.php';
    exit();
} elseif ($page === 'register') {
    include 'controller/registerController.php';
    exit();
} elseif ($page === 'logout') {
    session_destroy();
    session_start();
    $_SESSION['auth'] = false;
    $_SESSION['Role'] = 'guest';
    header('Location: index.php');
    exit();
} elseif ($page === 'userlist' && isset($_SESSION['Role']) && $_SESSION['Role'] === 'admin') {
    include 'views/userListView.php';
    exit();
} elseif ($page === 'orderView') {
    include 'views/cartView.php'; // Ensure this line includes the correct file
    exit();
}

// Gérer la requête de connexion
if (isset($_POST['login'])) {
    include 'controller/loginController.php';
    exit();
}

try {
    switch ($page) {
        case 'user':
            include 'controller/user.php';
            break;
        case 'orderView':
            include 'views/cartView.php';
            break;
        case 'adminPanel': // Changer 'admin' en 'adminPanel'
        case 'admin':
            if (isset($_SESSION['auth']) && $_SESSION['Role'] === 'admin') {
                if ($isAjax) {
                    ob_clean();
                    require_once 'controller/adminPanelController.php';
                    exit;
                }
                require_once 'views/adminPanelView.php';
                exit;
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
                    exit;
                }
                header('Location: index.php');
            }
            exit;
        case 'categoryManagement':
            if (isset($_SESSION['auth']) && $_SESSION['Role'] === 'admin') {
                include 'views/categoryManagementView.php';
            } else {
                header('Location: index.php');
                exit();
            }
            break;
        case 'home':
        case 'articles':
        default:
            include 'controller/articleController.php';
            break;
    }
} catch (Exception $e) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    // ... reste du code de gestion d'erreur ...
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Page avec Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php
    $page = $_GET['page'] ?? 'home';
    
    if ($page === 'userlist') {
        include 'views/userList.php';
    } else if ($page === 'user') {
        include 'views/user.php';
    }
    // ...existing code...
    ?>

    <!-- Toast container -->
    <div id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($page === 'userlist'): ?>
    <script type="module" src="/Projet/ecommercesami/assets/js/components/userListComponent.js"></script>
    <?php endif; ?>
</body>
</html>