<?php

declare(strict_types=1);

/**
 * @var App\Core\Router $router
 * This file is included from public/index.php, where $router is instantiated.
 */

// Placeholder for a potential HomeController or similar for the main page
// For now, let's assume a simple closure or a WelcomeController if we create one.
$router->get('/', [App\Controllers\HomeController::class, 'index']); // Example, HomeController needs to be created

// --- Admin Routes ---
// Grouping or prefixing for admin routes can be handled in a more advanced router
// For this simple router, we'll define them individually.

// Auth
$router->get('/admin/login', [App\Controllers\Admin\AuthController::class, 'showLoginForm']);
$router->post('/admin/login', [App\Controllers\Admin\AuthController::class, 'login']);
$router->post('/admin/logout', [App\Controllers\Admin\AuthController::class, 'logout']); // Should be POST for logout

// Admin Dashboard (Protected - middleware would handle this in a real app)
$router->get('/admin/dashboard', [App\Controllers\Admin\DashboardController::class, 'index']);

// Menu Categories (Admin)
$router->get('/admin/categories', [App\Controllers\Admin\CategoryController::class, 'index']);
$router->get('/admin/categories/create', [App\Controllers\Admin\CategoryController::class, 'create']);
$router->post('/admin/categories', [App\Controllers\Admin\CategoryController::class, 'store']);
$router->get('/admin/categories/{id}/edit', [App\Controllers\Admin\CategoryController::class, 'edit']);
$router->post('/admin/categories/{id}', [App\Controllers\Admin\CategoryController::class, 'update']); // Could be PUT
$router->post('/admin/categories/{id}/delete', [App\Controllers\Admin\CategoryController::class, 'destroy']); // Could be DELETE

// Menu Items (Admin)
$router->get('/admin/menu-items', [App\Controllers\Admin\MenuItemController::class, 'index']);
$router->get('/admin/menu-items/create', [App\Controllers\Admin\MenuItemController::class, 'create']);
$router->post('/admin/menu-items', [App\Controllers\Admin\MenuItemController::class, 'store']);
$router->get('/admin/menu-items/{id}/edit', [App\Controllers\Admin\MenuItemController::class, 'edit']);
$router->post('/admin/menu-items/{id}', [App\Controllers\Admin\MenuItemController::class, 'update']); // Could be PUT
$router->post('/admin/menu-items/{id}/delete', [App\Controllers\Admin\MenuItemController::class, 'destroy']); // Could be DELETE

// Admin Orders/Bill Management
$router->get('/admin/orders', [App\Controllers\Admin\OrderController::class, 'index']);
$router->get('/admin/orders/{id}', [App\Controllers\Admin\OrderController::class, 'show']);
$router->post('/admin/orders/{id}/update-status', [App\Controllers\Admin\OrderController::class, 'updateStatus']);
$router->post('/admin/orders/{id}/mark-paid', [App\Controllers\Admin\OrderController::class, 'markAsPaid']);


// --- Waiter Interface Routes ---
$router->get('/waiter', [App\Controllers\Waiter\TableController::class, 'index']); // Table selection
$router->get('/waiter/table/{tableId}/order', [App\Controllers\Waiter\OrderController::class, 'showOrderScreen']);
$router->post('/waiter/table/{tableId}/order', [App\Controllers\Waiter\OrderController::class, 'submitOrder']);
$router->post('/waiter/order/{orderId}/add-item', [App\Controllers\Waiter\OrderController::class, 'addItemToOrder']);
$router->post('/waiter/order-item/{orderItemId}/update', [App\Controllers\Waiter\OrderController::class, 'updateOrderItem']);
$router->post('/waiter/order-item/{orderItemId}/remove', [App\Controllers\Waiter\OrderController::class, 'removeOrderItem']);
$router->post('/waiter/order/{orderId}/initiate-billing', [App\Controllers\Waiter\OrderController::class, 'initiateBilling']);


// --- Kitchen Display System (KDS) Routes ---
$router->get('/kds', [App\Controllers\Kds\KdsController::class, 'index']);
$router->post('/kds/order/{orderId}/update-status', [App\Controllers\Kds\KdsController::class, 'updateOrderStatus']); // e.g., to 'in_progress' or 'ready'


// --- A simple placeholder for HomeController ---
// This should be in src/Controllers/HomeController.php eventually
if (!class_exists('App\Controllers\HomeController')) {
    class_alias(PlaceholderHomeController::class, 'App\Controllers\HomeController');
}
// Placeholder classes for other controllers until they are created to avoid fatal errors on route loading.
// This is a temporary measure for smoother step-by-step development.
if (!class_exists(PlaceholderHomeController::class)) {
    class PlaceholderHomeController extends App\Core\Controller {
        public function index() {
            // Check if APP_URL is set, otherwise default to relative path for assets
            $baseUrl = rtrim(getenv('APP_URL') ?: '', '/');

            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Bestellando!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{$baseUrl}/assets/css/style.css"> <!-- Ensure this path is correct -->
    <style>
        body { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; text-align: center; }
        .interface-links a { margin: 0 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Welcome to Bestellando!</h1>
        <p class="lead">The Restaurant Ordering System Prototype is under construction.</p>
        <p>Routing is set up. Core backend classes are in place.</p>
        <hr class="my-4">
        <p>Access different parts of the application:</p>
        <div class="interface-links">
            <a href="{$baseUrl}/waiter" class="btn btn-primary">Waiter Interface</a>
            <a href="{$baseUrl}/kds" class="btn btn-secondary">Kitchen Display</a>
            <a href="{$baseUrl}/admin/login" class="btn btn-info">Admin Panel</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
            // We can't use $this->render yet if layouts aren't defined
            // For now, just echo basic HTML.
            echo $html;
        }
    }
}

// --- Placeholder Controllers to avoid fatal errors during early dev ---
// These should be replaced by actual controller files in src/Controllers/*
if (!class_exists('App\Controllers\Admin\AuthController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Admin\AuthController'); }
if (!class_exists('App\Controllers\Admin\DashboardController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Admin\DashboardController'); }
if (!class_exists('App\Controllers\Admin\CategoryController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Admin\CategoryController'); }
if (!class_exists('App\Controllers\Admin\MenuItemController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Admin\MenuItemController'); }
if (!class_exists('App\Controllers\Admin\OrderController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Admin\OrderController'); }
if (!class_exists('App\Controllers\Waiter\TableController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Waiter\TableController'); }
if (!class_exists('App\Controllers\Waiter\OrderController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Waiter\OrderController'); }
if (!class_exists('App\Controllers\Kds\KdsController')) { class_alias(GenericPlaceholderController::class, 'App\Controllers\Kds\KdsController'); }

if (!class_exists(GenericPlaceholderController::class)) {
    class GenericPlaceholderController extends App\Core\Controller {
        public function __call($name, $arguments) {
            echo "<h1>Placeholder Controller</h1><p>Method: {$name} called in " . get_class($this) . "</p><p>This is a placeholder. Actual implementation pending.</p>";
            if (!empty($arguments)) {
                echo "<p>Arguments:</p><pre>" . htmlspecialchars(print_r($arguments, true)) . "</pre>";
            }
             echo '<p><a href="' . (getenv('APP_URL') ?: '') . '/">Back to Home</a></p>';
        }
    }
}

?>
