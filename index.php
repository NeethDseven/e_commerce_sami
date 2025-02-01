<?php
// Démarrer le buffering de sortie immédiatement
ob_start();
session_start();

// Utility functions
function checkSession() {
    if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Session expirée', 'redirect' => 'index.php?page=login']);
            exit;
        }
        header('Location: index.php?page=login');
        exit;
    }
    return true;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Initialize session
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    $_SESSION['auth'] = false;
    $_SESSION['Role'] = 'guest';
    $_SESSION['guest_id'] = session_id();
}

// Required files
require_once 'includes/database.php';
include_once 'model/articleModel.php';
include_once 'model/user.php';
require_once __DIR__ . '/model/categoryModel.php';

// Authentication required pages and controllers
$auth_required_pages = ['orderView', 'profile', 'orderDetails', 'cartOrder'];
$auth_required_controllers = ['cartOrder', 'order', 'profile'];

/** @var \PDO $pdo */
$categories = getCategories($pdo);

// Handle authentication pages first (before any output)
$page = $_GET['page'] ?? 'accueil';
if (in_array($page, ['login', 'register', 'logout'])) {
    switch ($page) {
        case 'login':
            include 'controller/loginController.php';
            exit();
        case 'register':
            include 'controller/registerController.php';
            exit();
        case 'logout':
            session_destroy();
            session_start();
            $_SESSION['auth'] = false;
            $_SESSION['Role'] = 'guest';
            header('Location: index.php');
            exit();
    }
}

// Maintenant on peut inclure le navbar et commencer l'affichage
include '_partials/_navbar.php';

// Handle AJAX requests first
if (isAjaxRequest()) {
    ob_clean();
    
    if (isset($_GET['controller'])) {
        $controller = $_GET['controller'];
        if (in_array($controller, $auth_required_controllers)) {
            checkSession();
        }
        
        $controllerFile = "controller/{$controller}Controller.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            exit();
        } else {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Controller not found']);
            exit();
        }
    }
}

// Handle AJAX requests
if (isAjaxRequest()) {
    ob_clean();
    
    if (isset($_GET['controller'])) {
        $controller = $_GET['controller'];
        if (in_array($controller, $auth_required_controllers)) {
            checkSession();
        }
        
        // Ajout de la vérification spécifique pour l'action validate
        if ($controller === 'cartOrder' && isset($_GET['action']) && $_GET['action'] === 'validate') {
            if (!isset($_SESSION['id_utilisateur'])) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Utilisateur non authentifié']);
                exit;
            }
        }
        
        $controllerFile = "controller/{$controller}Controller.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            exit();
        }
    }
    
    $page = $_GET['page'] ?? '';
    switch ($page) {
        case 'promotions':
            require_once 'controller/promotionController.php';
            exit;
    }
}

// Regular page handling
$page = $_GET['page'] ?? 'accueil';
if (in_array($page, $auth_required_pages)) {
    checkSession();
}

try {
    switch ($page) {
        case 'orderView':
            include 'views/cartView.php';
            break;
        case 'home':
        case 'articles':
        case 'orderDetailsView':
            require_once 'controller/orderDetailController.php';
            break;
        default:
            include 'controller/articleController.php';
            break;
    }
} catch (Exception $e) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle admin pages
if ($_SESSION['Role'] === 'admin') {
    switch ($page) {
        case 'userlist':
            include 'views/userListView.php';
            exit();
        case 'orderdetails':
            if (!isset($_GET['id'])) {
                header('Location: index.php?page=orderlist');
                exit();
            }
            require_once 'controller/orderController.php';
            require_once 'views/orderDetailsView.php';
            exit();
        case 'orderlist':
            require_once 'model/orderModel.php';
            require_once 'controller/orderController.php';
            exit();
        case 'adminPanel':
        case 'admin':
            require_once $isAjaxRequest ? 'controller/adminPanelController.php' : 'views/adminPanelView.php';
            exit();
        case 'categoryManagement':
            include 'views/categoryManagementView.php';
            exit();
    }
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($page === 'userlist'): ?>
        <script type="module" src="/Projet/ecommercesami/assets/js/components/userListComponent.js"></script>
    <?php endif; ?>
</body>
</html>