<?php

namespace App\Services;

class Router {
    private array $routes = [];

    /**
     * Map GET route
     */
    public function get(string $path, $callback): void {
        $this->routes['GET'][$path] = $callback;
    }

    /**
     * Map POST route
     */
    public function post(string $path, $callback): void {
        $this->routes['POST'][$path] = $callback;
    }

    /**
     * Dispatch HTTP request to registered handler
     */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Handle subdirectory installations (like in local http://localhost/scholarship/)
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && $scriptName !== '\\' && !empty($scriptName)) {
            if (strpos($uri, $scriptName) === 0) {
                $uri = substr($uri, strlen($scriptName));
            }
        }
        
        $uri = '/' . trim($uri, '/');
        
        // 1. Direct match check
        if (isset($this->routes[$method][$uri])) {
            $callback = $this->routes[$method][$uri];
            $this->executeCallback($callback);
            return;
        }

        // 2. Pattern match check for parameterized routes (e.g. /scholarships/{slug})
        foreach ($this->routes[$method] ?? [] as $routePath => $callback) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePath);
            $pattern = '@^' . $pattern . '$@';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // remove full match
                $this->executeCallback($callback, $matches);
                return;
            }
        }

        // 3. Fallback to 404
        $this->render404();
    }

    /**
     * Execute matching callback (closure or Controller class action)
     */
    private function executeCallback($callback, array $params = []): void {
        if (is_callable($callback)) {
            call_user_func_array($callback, $params);
        } elseif (is_array($callback)) {
            [$controllerClass, $method] = $callback;
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], $params);
                } else {
                    $this->serverError("Method '$method' not found in controller '$controllerClass'.");
                }
            } else {
                $this->serverError("Controller class '$controllerClass' not found.");
            }
        } else {
            $this->serverError("Invalid route callback.");
        }
    }

    private function render404(): void {
        http_response_code(404);
        try {
            view('errors.404');
        } catch (\Exception $e) {
            echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
        }
    }

    private function serverError(string $message): void {
        http_response_code(500);
        if (config('app.debug', false)) {
            echo "<h1>500 Internal Server Error</h1><p>" . e($message) . "</p>";
        } else {
            echo "<h1>500 Internal Server Error</h1><p>Something went wrong on our end.</p>";
        }
    }
}
