<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        // A simple View renderer could be instantiated here if not injected.
        // For now, views will be simple PHP includes.
        // $this->view = new View();
    }

    /**
     * Loads a view file.
     *
     * @param string $viewName The name of the view file (e.g., 'admin/dashboard/index')
     *                         This corresponds to src/Views/admin/dashboard/index.php
     * @param array $data Data to be extracted and made available to the view.
     */
    protected function render(string $viewName, array $data = []): void
    {
        // Construct the full path to the view file
        $viewFile = ROOT_PATH . '/src/Views/' . str_replace('.', '/', $viewName) . '.php';

        if (file_exists($viewFile)) {
            // Extract data array into individual variables
            extract($data);

            // Start output buffering
            ob_start();

            // Include the view file
            include $viewFile;

            // Get the content of the buffer and end buffering
            $content = ob_get_clean();
            echo $content; // Output the rendered view
        } else {
            // In a real app, throw an exception or render a specific error view
            error_log("View file not found: {$viewFile}");
            http_response_code(500);
            echo "<h1>Error: View Not Found</h1><p>The view file '{$viewName}' could not be located.</p>";
        }
    }

    /**
     * Redirects to a given URL.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode HTTP status code for the redirect (default 302).
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        // For relative URLs, construct absolute URL if APP_URL is defined
        if (filter_var($url, FILTER_VALIDATE_URL) === false && isset($_ENV['APP_URL'])) {
            $appUrl = rtrim($_ENV['APP_URL'] ?? getenv('APP_URL'), '/');
            $url = $appUrl . '/' . ltrim($url, '/');
        } elseif (filter_var($url, FILTER_VALIDATE_URL) === false) {
            // Fallback for relative URLs if APP_URL is not set (less reliable)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $url = $protocol . $host . '/' . ltrim($url, '/');
        }

        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    /**
     * Returns JSON response.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode HTTP status code (default 200).
     */
    protected function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Get JSON input from request body.
     *
     * @return mixed Decoded JSON data or null if error.
     */
    protected function getJsonInput(): mixed
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return null;
        }
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log error or handle appropriately
            error_log('Invalid JSON input: ' . json_last_error_msg());
            return null;
        }
        return $data;
    }

    /**
     * Sanitize output to prevent XSS.
     *
     * @param string|null $data
     * @return string
     */
    protected function sanitize(?string $data): string
    {
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }
}
