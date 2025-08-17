<?php
// /ProjectLeviathan - Project/ProjectLeviathan - Backend/config/router.php

class BackendRouter {
    private static $routes = [
        '' => 'login',
        'login' => 'login',
        'register' => 'register',
        'forgot-password' => 'forgot-password',
        'inactive-account' => 'inactive-account'
    ];

    public static function getPath() {
        $requestUri = urldecode($_SERVER['REQUEST_URI']);
        $scriptName = $_SERVER['SCRIPT_NAME'];
        
        $basePath = dirname($scriptName);
        if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        return trim(parse_url($requestUri, PHP_URL_PATH), '/');
    }

    public static function getView() {
        $path = self::getPath();

        // --- INICIO DE LÓGICA CORREGIDA ---

        // 1. Primero, busca una coincidencia exacta en las rutas.
        // Esto funciona para /login, /register, /forgot-password, etc.
        if (array_key_exists($path, self::$routes)) {
            $viewName = self::$routes[$path];
            $viewFile = __DIR__ . '/../views/' . $viewName . '.php';
            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }

        // 2. Si no hay coincidencia exacta, verifica si es una sub-ruta (para los formularios con etapas).
        $path_parts = explode('/', $path);
        $main_route = $path_parts[0];

        // Comprueba si la primera parte de la ruta (ej: "register") es una ruta válida.
        if (count($path_parts) > 1 && array_key_exists($main_route, self::$routes)) {
            $viewName = self::$routes[$main_route];
            $viewFile = __DIR__ . '/../views/' . $viewName . '.php';
            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }

        // 3. Si ninguna de las condiciones anteriores se cumple, carga la vista de login por defecto.
        return __DIR__ . '/../views/login.php';

        // --- FIN DE LÓGICA CORREGIDA ---
    }
}
?>