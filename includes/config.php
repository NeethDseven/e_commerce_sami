<?php
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/projet/e_commerce_sami-develop/');
define('CONTROLLER_PATH', BASE_PATH . '/controller');
define('MODEL_PATH', BASE_PATH . '/model');
define('VIEW_PATH', BASE_PATH . '/views');

// For JavaScript consumption
header('X-Base-URL: ' . BASE_URL);
