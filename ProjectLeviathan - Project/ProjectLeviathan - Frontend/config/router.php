<?php
class Router {
    private static $routes = [
        '' => ['section' => 'home', 'subsection' => null],
        'explore' => ['section' => 'explore', 'subsection' => 'municipalities'],
        'explore/municipalities' => ['section' => 'explore', 'subsection' => 'municipalities'],
        'explore/universities' => ['section' => 'explore', 'subsection' => 'universities'],
        'settings' => ['section' => 'settings', 'subsection' => 'profile'],
        'settings/your-account' => ['section' => 'settings', 'subsection' => 'profile'],
        'settings/login' => ['section' => 'settings', 'subsection' => 'login'],
        'settings/accessibility' => ['section' => 'settings', 'subsection' => 'accessibility'],
        'help' => ['section' => 'help', 'subsection' => 'privacy'],
        'help/privacy' => ['section' => 'help', 'subsection' => 'privacy'],
        'help/terms' => ['section' => 'help', 'subsection' => 'terms'],
        'help/cookies' => ['section' => 'help', 'subsection' => 'cookies'],
        'help/suggestions' => ['section' => 'help', 'subsection' => 'suggestions']
    ];

    public static function getCurrentRoute() {
        $requestUri = urldecode($_SERVER['REQUEST_URI']);
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptName);
        if ($basePath !== '/') {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        return trim(parse_url($requestUri, PHP_URL_PATH), '/');
    }

    public static function getRouteConfig($path = null) {
        if ($path === null) {
            $path = self::getCurrentRoute();
        }
        
        // --- INICIO DE LA MODIFICACIÓN ---
        // Coincidencia exacta para rutas estáticas
        if (array_key_exists($path, self::$routes)) {
            return self::$routes[$path];
        }
        
        // Manejo de rutas dinámicas para el chat (ej: chat/uuid-del-grupo)
        if (strpos($path, 'chat/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) === 2 && !empty($parts[1])) {
                // Devuelve la sección 'chat' y el ID (UUID)
                return ['section' => 'chat', 'subsection' => 'messages', 'id' => $parts[1]];
            }
            if (count($parts) === 3 && $parts[1] === 'members' && !empty($parts[2])) {
                return ['section' => 'chat', 'subsection' => 'members', 'id' => $parts[2]];
            }
        }
        // --- FIN DE LA MODIFICACIÓN ---
        
        return null;
    }

    public static function isValidRoute($path) {
        return array_key_exists($path, self::$routes);
    }

    public static function getAllRoutes() {
        return self::$routes;
    }
}

$currentPath = Router::getCurrentRoute();

if ($currentPath === 'explore') {
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $baseUrl . '/explore/municipalities', true, 301);
    exit;
}

$routeConfig = Router::getRouteConfig($currentPath);

if ($routeConfig === null) {
    $routeConfig = ['section' => '404', 'subsection' => null, 'id' => null];
    http_response_code(404);
}

$CURRENT_SECTION = $routeConfig['section'];
$CURRENT_SUBSECTION = $routeConfig['subsection'];
$CURRENT_ID = $routeConfig['id'] ?? null; // Almacenamos el ID (UUID)
$CURRENT_PATH = $currentPath;
?>