<?php
session_start(); // Inicia la sesión para poder acceder a ella.

// --- INICIO DE LA MODIFICACIÓN ---

// 1. Verificar que la solicitud sea por método POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si no es POST, simplemente redirige o muestra un error, pero no cierres la sesión.
    header('Location: ./'); // Redirige a la página de login.
    exit;
}

// 2. Validar el token CSRF.
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Si el token no es válido, se detiene la ejecución para prevenir CSRF.
    session_destroy(); // Es una buena práctica destruir la sesión si hay un intento de ataque.
    die('Error de validación de seguridad.');
}
// --- FIN DE LA MODIFICACIÓN ---

// Si todo es correcto, procede a destruir la sesión.

// Destruye todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borra también la cookie de la sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruye la sesión.
session_destroy();

// Define la ruta base para redirigir al directorio de login.
$login_path = './';

// Redirige al usuario a la página de inicio de sesión.
header("Location: " . $login_path);
exit;
?>