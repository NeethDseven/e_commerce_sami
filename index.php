<?php

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

function checkSession() {
    if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Session expirée', 'redirect' => 'index.php?page=login']);
            exit();
        }
        header('Location: index.php?page=login');
        exit();
    }
    return true;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    $_SESSION['auth'] = false;
    $_SESSION['Role'] = 'guest';
    $_SESSION['guest_id'] = session_id();
}

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
        }
        
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Controller not found']);
        exit();
    }
}

if (in_array($page, $auth_required_pages)) {
    checkSession();
}

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
    }
} catch (Exception $e) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commerce de Zinzin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>