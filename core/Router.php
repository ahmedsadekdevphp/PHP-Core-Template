<?php

namespace Core;

use App\Middlewares\RoleMiddleware;
use InvalidArgumentException;

class Router
{
    private const HTTP_METHODS = ['GET', 'POST', 'DELETE', 'PUT'];

    private $routes = [];

    //for all incoming request
    private $globalMiddleware = [
        'RateLimitMiddleware'
    ];
    //middlewares need authintication
    private $specialMiddleware = [
        'ThrottleMiddleware',
    ];

    /**
     * Adds a new route to the routing table.
     *
     * @param string $method The HTTP method (e.g., 'GET', 'POST') for the route.
     * @param string $uri The URI (endpoint) for the route.
     * @param mixed $action The action to be executed when the route is matched. This can be 
     *                      a callable or a controller action string (e.g., 'ControllerName@methodName').
     * 
     * @throws InvalidArgumentException If the provided HTTP method is not supported.
     * @return void
     */
    public function add(string $method, string $uri, $action, array $middleware = [], array $roles = []): void
    {
        if (!in_array($method, self::HTTP_METHODS)) {
            throw new InvalidArgumentException("HTTP method $method is not supported.");
        }

        $this->routes[$method][] = [
            'uri' => $this->normalizeRoute($uri),
            'action' => $action,
            'middleware' => $middleware,
            'roles' => $roles
        ];
    }

    /**
     * Dispatches a request to the appropriate controller action based on the URL and HTTP method.
     *
     * @param string $url The request URL to be dispatched.
     * @return void
     * 
     * @throws Exception If the HTTP method is not allowed (405) or the route is not found (404).
     */
    public function dispatch(string $url): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $this->normalizeUrl($url);
        $this->checkMethod($method, $this->routes);

        // Find the matching route and parameters
        list($action, $params, $middleware, $roles) = $this->findMatchingRoute($url, $this->routes[$method]);

        if ($action) {
            if (!$this->handleMiddleware($middleware, $roles)) {
                return;
            }
            $params = $this->sanitizeParameters($params);
            $this->handleAction($action, $params);
        } else {
            $this->sendResponse(404, "Route not found for URL: $url");
        }
    }


    /**
     * Finds the matching route for a given URL.
     *
     * @param string $url The request URL.
     * @param array $routes The defined routes.
     * @return array The action and parameters for the matched route.
     */
    private function findMatchingRoute(string $url, array $routes)
    {
        // Check for exact (static) match first
        foreach ($routes as $route) {
            if ($route['uri'] === $url) {
                return [$route['action'], [], $route['middleware'], $route['roles']]; // Include roles
            }
        }

        // Check for dynamic (parameterized) routes
        foreach ($routes as $route) {
            $routeUri = $route['uri'];

            // Check if route has dynamic parameters (e.g., /user/{user_id})
            $pattern = preg_replace('#\{[a-zA-Z0-9_]+\}#', '([a-zA-Z0-9_]+)', $routeUri);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches); // Remove full match
                return [$route['action'], $matches, $route['middleware'], $route['roles']]; // Include roles
            }
        }

        return [null, [], [], []]; // No match found
    }


    /**
     * Checks if the provided URL matches any defined route.
     * 
     * @param string $url The URL to be checked.
     * @param array $routes The list of defined routes where each key represents a valid URL.
     * 
     * @throws Exception Sends a 404 response if the route for the given URL is not found.
     */
    private function CheckRoute($url, $routes)
    {
        if (!array_key_exists($url, $routes)) {
            $this->sendResponse(404, "Route not found for URL: $url");
        }
    }

    /**
     * Checks if the provided HTTP method is allowed .
     *
     * @param string $method The HTTP method to be checked (e.g., 'GET', 'POST').
     * @param array $routes The list of routes where each key represents an allowed method.
     * 
     * @throws Exception Sends a 405 response if the method is not allowed.
     */
    private function checkMethod($method, $routes)
    {
        if (!array_key_exists($method, $routes)) {
            $this->sendResponse(405, "Method $method not allowed.");
        }
    }


    /**     
     * This function uses `parse_url` to extract the path part of the URL and trims any 
     * leading or trailing slashes ('/') from the result. The normalization ensures that 
     *
     * @param string $url The full URL to be normalized.
     * @return string The normalized URL path.
     */
    private function normalizeUrl(string $url): string
    {
        return trim(parse_url($url, PHP_URL_PATH), '/');
    }

    private function normalizeRoute(string $uri): string
    {
        return trim($uri, '/');
    }

    /**
     * Handles the execution of an action, which can either be a callable 
     * (such as a closure) or a controller action specified as a string.
     * @param mixed $action The action to be executed. This can be a callable or a 
     * string representing a controller action.
     * @return mixed The result of the invoked action, either from the callable 
     * or from the controller method.
     * @throws Exception If the action is not callable and the controller action cannot 
     *be handled (e.g., if the controller or method does not exist).
     */

    private function handleAction($action, array $params = [])
    {
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        $this->handleControllerAction($action, $params);
    }
    /**
     * Handles the execution of a specified controller action.
     *     *
     * @param string $action The action string specifying the controller and method, 
     *                       formatted as 'ControllerName@methodName'.
     * @return mixed The result of the invoked method from the controller.
     * @throws Exception If the controller file does not exist or if the method is 
     * not found in the controller.
     */
    private function handleControllerAction(string $action, array $params = [])
    {
        list($controllerName, $actionMethod) = explode('@', $action);
        $controllerClass = "App\\Controllers\\" . $controllerName;

        $controllerFile = dirname(__DIR__) . '/app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->sendResponse(404, "Controller $controllerName not found.");
        }

        require_once $controllerFile;
        $controllerInstance = new $controllerClass();
        if (!method_exists($controllerInstance, $actionMethod)) {
            $this->sendResponse(404, "Method $actionMethod not found in controller $controllerName.");
        }

        return call_user_func_array([$controllerInstance, $actionMethod], $params); // Pass parameters to method
    }

    private function sendResponse(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }

    /**
     * Sanitize parameters to prevent XSS and other security issues.
     *
     * @param array $params The parameters to sanitize.
     * @return array The sanitized parameters.
     */
    private function sanitizeParameters(array $params): array
    {
        return array_map(function ($param) {
            return htmlspecialchars(strip_tags($param), ENT_QUOTES, 'UTF-8');
        }, $params);
    }

    /**
     * Handles the execution of middleware, including global, specific role,
     * and special middleware.
     *
     * @param array $middleware An array of middleware classes to be executed.
     * @param array $roles An array of roles associated with the request.
     * @return bool Returns true if all middleware passes; otherwise, false.
     */
    private function handleMiddleware(array $middleware, array $roles): bool
    {
        // Handle global middleware
        foreach ($this->globalMiddleware as $m) {
            if (!$this->executeMiddleware($m)) {
                return false; // Middleware failed
            }
        }

        // Handle specific role middleware if it exists
        if (!empty($middleware)) {
            foreach ($middleware as $m) {
                if ($m === RoleMiddleware::class && !empty($roles)) {
                    $middlewareInstance = new $m();
                    $middlewareInstance->setRoles($roles);
                } else {
                    $middlewareInstance = new $m();
                }

                if (!$middlewareInstance->handle()) {
                    return false; // Middleware failed
                }
            }
        }

        // Handle special middleware
        foreach ($this->specialMiddleware as $m) {
            if (!$this->executeMiddleware($m)) {
                return false; // Middleware failed
            }
        }

        return true; // All middleware passed
    }

    /**
     * Executes a specific middleware class and returns the result of its handle method.
     *
     * @param string $middleware The name of the middleware class to be executed.
     * @return bool Returns the result of the middleware's handle method.
     */
    private function executeMiddleware(string $middleware): bool
    {
        $middlewareClass = "App\\Middlewares\\" . $middleware;
        $middlewareInstance = new $middlewareClass();
        return $middlewareInstance->handle();
    }
}
