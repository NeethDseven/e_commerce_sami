<?php
<<<<<<< HEAD

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

if (strpos($_SERVER['REQUEST_URI'], 'developindex.php') !== false) {
    $correctedUrl = str_replace('developindex.php', 'develop/index.php', $_SERVER['REQUEST_URI']);
    header('Location: ' . $correctedUrl, true, 301);
    exit;
}

require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ob_start();
session_start();

=======
// Démarrer le buffering de sortie immédiatement
ob_start();
session_start();

// Utility functions
>>>>>>> origin/develop
function checkSession() {
    if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Session expirée', 'redirect' => 'index.php?page=login']);
<<<<<<< HEAD
            exit();
        }
        header('Location: index.php?page=login');
        exit();
=======
            exit;
        }
        header('Location: index.php?page=login');
        exit;
>>>>>>> origin/develop
    }
    return true;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

<<<<<<< HEAD
=======
// Initialize session
>>>>>>> origin/develop
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    $_SESSION['auth'] = false;
    $_SESSION['Role'] = 'guest';
    $_SESSION['guest_id'] = session_id();
}

<<<<<<< HEAD
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/model/articleModel.php';
require_once __DIR__ . '/model/user.php';
require_once __DIR__ . '/model/categoryModel.php';

$auth_required_pages = [
    'profile'
];

$auth_required_controllers = [
    'profile'
];

$categories = getCategories($pdo);

=======
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
>>>>>>> origin/develop
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

<<<<<<< HEAD
$routeHandlers = [
    'orderView' => ['view' => 'views/cartView.php'],
    'home' => ['controller' => 'controller/orderDetailController.php'],
    'articles' => ['controller' => 'controller/orderDetailController.php'],
    'orderDetailsView' => ['controller' => 'controller/orderDetailController.php'],
    'userlist' => ['controller' => 'controller/userController.php', 'view' => 'views/userListView.php', 'admin' => true],
    'orderdetails' => [
        'controller' => 'controller/orderDetailController.php',
        'view' => 'views/orderDetailsView.php'
    ],
    'orderlist' => [
        'controller' => 'controller/orderController.php',
        'view' => 'views/orderListView.php',
        'admin' => true 
    ],
    'adminPanel' => ['view' => 'views/adminPanelView.php', 'admin' => true],
    'admin' => ['controller' => 'controller/adminPanelController.php', 'admin' => true],
    'categoryManagement' => ['view' => 'views/categoryManagementView.php', 'admin' => true]
];

=======
// Maintenant on peut inclure le navbar et commencer l'affichage
include '_partials/_navbar.php';

// Handle AJAX requests first
>>>>>>> origin/develop
if (isAjaxRequest()) {
    ob_clean();
    
    if (isset($_GET['controller'])) {
        $controller = $_GET['controller'];
<<<<<<< HEAD
        
=======
>>>>>>> origin/develop
        if (in_array($controller, $auth_required_controllers)) {
            checkSession();
        }
        
        $controllerFile = "controller/{$controller}Controller.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            exit();
<<<<<<< HEAD
        }
        
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Controller not found']);
        exit();
    }
}

=======
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
>>>>>>> origin/develop
if (in_array($page, $auth_required_pages)) {
    checkSession();
}

<<<<<<< HEAD
if (!isAjaxRequest()) {
    include '_partials/_navbar.php';
}

try {
    if (isset($routeHandlers[$page])) {
        $route = $routeHandlers[$page];
        
        if (isset($route['admin']) && $route['admin'] && $_SESSION['Role'] !== 'admin') {
            header('Location: index.php');
            exit();
        }
        
        if (isset($route['controller'])) {
            require_once $route['controller'];
        }
        
        if (isset($route['view'])) {
            include $route['view'];
            exit();
        }
        
        if ($route['admin']) {
            exit();
        }
    } else {
        include 'controller/articleController.php';
=======
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
>>>>>>> origin/develop
    }
} catch (Exception $e) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
<<<<<<< HEAD
        exit();
    }
}

switch ($_GET['page'] ?? '') {    
    case 'orderdetails':
        if (isset($_GET['order_id'])) {
            require_once 'views/orderDetailsView.php';
        } else {
            header('Location: index.php?page=orders');
        }
        break;
}

if (isset($_GET['page']) && $_GET['page'] === 'orderdetails') {
    try {
        require_once 'model/orderModel.php';
        $orderId = (int)$_GET['order_id'];
        $orderData = getOrderDetails($pdo, $orderId);

        if (!$orderData) {
            throw new Exception('Commande non trouvée');
        }

        if (isAjaxRequest()) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $orderData
            ]);
            exit;
        }

        if (!isAjaxRequest()) {
            ob_start();
            include '_partials/_navbar.php';
            require_once 'views/orderDetailsView.php';
            $content = ob_get_clean();
            echo $content;
            exit;
        }

    } catch (Exception $e) {
        if (isAjaxRequest()) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } else {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?page=orderlist');
        }
        exit;
=======
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
>>>>>>> origin/develop
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Commerce de Zinzin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
=======
    <title>Simple Page avec Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($page === 'userlist'): ?>
        <script type="module" src="/Projet/ecommercesami/assets/js/components/userListComponent.js"></script>
    <?php endif; ?>
>>>>>>> origin/develop
</body>
</html>