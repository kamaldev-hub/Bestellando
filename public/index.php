<?php

declare(strict_types=1);

// Define a constant for the project root directory
define('ROOT_PATH', dirname(__DIR__));

// Autoloader - Composer should have been installed
// If vendor/autoload.php is missing, it means composer install hasn't run.
// This will be an issue if Docker environment isn't used by the user to run composer install.
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    // Fallback or error message if composer dependencies are not installed
    // This is critical as the application won't run without autoloading.
    echo "<h1>Error: Composer dependencies not installed.</h1>";
    echo "<p>Please run 'composer install' in the project root directory.</p>";
    echo "<p>If using Docker, run 'docker-compose exec web composer install'.</p>";
    error_log("FATAL: vendor/autoload.php not found. Composer install needed.");
    exit(1); // Exit if dependencies are missing
}

// Load environment variables
// This requires `vlucas/phpdotenv` which should be added via composer.
// For now, we'll rely on environment variables being set by Docker or server.
// If Dotenv is added later, uncomment:
/*
try {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    error_log("Could not find .env file: " . $e->getMessage());
    // Handle case where .env is not found, maybe default settings or die with error
    die("Error: .env file not found. Please copy .env.example to .env and configure it.");
}
*/

// Basic error reporting (adjust for production based on APP_DEBUG from .env)
$appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? 'false';
if (strtolower($appDebug) === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
    // TODO: Implement proper logging for production
}

// Start secure session (must be called before any output)
App\Core\Security::startSecureSession();

// Initialize Router
$router = new App\Core\Router();

// Load routes definition
// This file will contain calls to $router->get(), $router->post(), etc.
require_once ROOT_PATH . '/config/routes.php';

// Dispatch the request
// The REQUEST_URI might include the base path if not running in root, handle this if needed.
// For now, assume app runs from domain root or Apache is configured to handle it.
$requestUri = $_SERVER['REQUEST_URI'];
$httpMethod = $_SERVER['REQUEST_METHOD'];

// Basic base path handling if app is in a subdirectory (e.g. http://localhost/bestellando/)
// This is a very simple approach. A more robust one might be needed depending on server config.
$basePath = ''; // If app is in a subdirectory, set it here e.g. /bestellando
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
if (empty($requestUri)) {
    $requestUri = '/'; // Default to root if URI becomes empty after stripping base path
}


try {
    $router->dispatch($httpMethod, $requestUri);
} catch (\PDOException $e) {
    // Handle database connection errors gracefully
    error_log("Database Critical Error: " . $e->getMessage());
    http_response_code(503); // Service Unavailable
    // You would render a user-friendly error page here
    echo "<h1>Service Temporarily Unavailable</h1><p>We are experiencing technical difficulties. Please try again later.</p>";
    // (new App\Controllers\ErrorController())->serviceUnavailable($e->getMessage());
} catch (\Throwable $e) {
    // Catch any other unhandled exceptions
    error_log("Unhandled Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    // Render a generic error page
    echo "<h1>Internal Server Error</h1><p>An unexpected error occurred. Please try again later.</p>";
    // (new App\Controllers\ErrorController())->genericError($e->getMessage());
}

?>
