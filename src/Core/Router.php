<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Adds a route to the routing table.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path URL path (e.g., /users/:id)
     * @param array $handler Controller and method [ControllerClass::class, 'methodName']
     * @param array $middlewares Array of middleware aliases or FQCNs for this route
     */
    public function addRoute(string $method, string $path, array $handler, array $middlewares = []): void
    {
        $path = $this->normalizePath($path);
        $this->routes[strtoupper($method)][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Helper for GET routes.
     */
    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Helper for POST routes.
     */
    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Helper for PUT routes.
     */
    public function put(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Helper for DELETE routes.
     */
    public function delete(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Normalizes the path: adds leading slash if missing.
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return '/' . $path;
    }

    /**
     * Dispatches the request to the appropriate handler.
     *
     * @param string $httpMethod
     * @param string $uri
     */
    public function dispatch(string $httpMethod, string $uri): void
    {
        $uri = $this->normalizePath(strtok($uri, '?')); // Remove query string
        $method = strtoupper($httpMethod);

        $routeData = null;
        $params = [];

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $path => $data) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $path);
                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $uri, $matches)) {
                    $routeData = $data;
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }

        if ($routeData === null) {
            $this->handleNotFound();
            return;
        }

        // TODO: Implement middleware execution if $routeData['middlewares'] is not empty

        $controllerClass = $routeData['handler'][0];
        $actionMethod = $routeData['handler'][1];

        if (!class_exists($controllerClass)) {
            error_log("Router Error: Controller class {$controllerClass} not found.");
            $this->handleServerError("Controller class not found.");
            return;
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $actionMethod)) {
            error_log("Router Error: Method {$actionMethod} not found in controller {$controllerClass}.");
            $this->handleServerError("Action method not found.");
            return;
        }

        // Call the controller action, passing extracted parameters
        try {
            call_user_func_array([$controllerInstance, $actionMethod], $params);
        } catch (\Exception $e) {
            error_log("Router Dispatch Exception: " . $e->getMessage());
            $this->handleServerError("An error occurred while processing your request.");
        }
    }

    private function handleNotFound(): void
    {
        http_response_code(404);
        // In a real app, you'd render a nice 404 page
        echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
        // Alternatively, call a dedicated error controller/method
        // (new ErrorController())->notFound();
    }

    private function handleServerError(string $message = "Internal Server Error"): void
    {
        http_response_code(500);
        // In a real app, you'd render a nice 500 page
        echo "<h1>500 Internal Server Error</h1><p>{$message}</p>";
        // (new ErrorController())->serverError($message);
    }
}
