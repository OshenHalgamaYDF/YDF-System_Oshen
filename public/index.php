<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Include configuration
require APP_PATH . '/config/config.php';

// Get the requested URL
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '/';

// Simple routing - map URLs to controller methods
$routes = [
    '/' => 'HomeController@index',
    'login' => 'AuthController@login',
    'register' => 'AuthController@register', 
    'logout' => 'AuthController@logout',
    'reset-password' => 'AuthController@resetPassword',
    'access/users' => 'AccessController@users',
    'products/manage' => 'ProductsController@manage',
    'customers/manage' => 'CustomersController@manage',
    'suppliers/manage' => 'SuppliersController@manage',
];

// Find the route
if (isset($routes[$url])) {
    list($controllerName, $methodName) = explode('@', $routes[$url]);
} else {
    // Show 404 if route not found
    http_response_code(404);
    include APP_PATH . '/views/errors/404.php';
    exit();
}

// Include and call the controller method
require APP_PATH . "/controllers/{$controllerName}.php";

$controller = new $controllerName();
$controller->$methodName();