<?php
// 1. Inicia la sesión para poder leer las variables de sesión.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Lee la razón desde la sesión, no desde la URL ($_GET).
$reason = $_SESSION['status_reason'] ?? 'deactivated';

// --- INICIO DE LA CORRECCIÓN ---
// Se ha eliminado la línea "unset($_SESSION['status_reason']);"
// para que el motivo del estado persista entre recargas de la página.
// --- FIN DE LA CORRECCIÓN ---


$title = "¡Oops! Tu cuenta no está disponible";
$message = "No tienes acceso porque la cuenta ha sido desactivada. Si crees que esto es un error, por favor, contacta con el equipo de soporte.";
$icon = "cloud_off";

switch ($reason) {
    case 'suspended':
        $title = "Cuenta Suspendida";
        $message = "Tu cuenta ha sido suspendida temporalmente. Por favor, contacta con el soporte para más información.";
        $icon = "pause_circle";
        break;
    case 'banned':
        $title = "Cuenta Baneada";
        $message = "Esta cuenta ha sido baneada permanentemente y ya no se puede acceder a ella. Si crees que esto es un error, contacta al soporte.";
        $icon = "block";
        break;
    case 'deleted':
        $title = "Cuenta Eliminada";
        $message = "Esta cuenta ha sido eliminada y ya no existe. No es posible recuperarla.";
        $icon = "delete_forever";
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - ProjectLeviathan</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
</head>
<body>
    <div class="page-wrapper">
        <header class="page-header"><a href="login" class="logo-link"></a></header>
        <main class="main-container centered">
            <section class="content-wrapper status-wrapper">
                <span class="material-symbols-rounded status-icon"><?php echo $icon; ?></span>
                <h1><?php echo htmlspecialchars($title); ?></h1>
                <p><?php echo htmlspecialchars($message); ?></p>
                <a href="login" class="go-back-btn">Volver al inicio</a>
            </section>
        </main>
        <footer class="page-footer">
            <a href="#">Términos de uso</a><span class="separator">|</span><a href="#">Política de privacidad</a>
        </footer>
    </div>
</body>
</html>