<?php
// /ProjectLeviathan - Project/ProjectLeviathan - Backend/index.php

session_start();



// --- INICIO DE LA MODIFICACIÓN ---

// Incluimos el router primero para poder analizar la ruta.
require_once 'config/router.php';

// Definimos la URL base para los assets y redirecciones.
$BASE_URL_BACKEND = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

// Obtenemos la ruta actual que el usuario está visitando.
$currentPath = BackendRouter::getPath();

// Si la ruta está vacía, significa que se está accediendo a la raíz del backend.
// En este caso, redirigimos a la página de /login.
if ($currentPath === '') {
    header('Location: ' . $BASE_URL_BACKEND . 'login');
    exit;
}

// --- FIN DE LA MODIFICACIÓN ---


// Si no se redirigió, obtenemos la vista que corresponde a la ruta.
$viewToInclude = BackendRouter::getView();

// Incluimos la vista que el router ha determinado.
include $viewToInclude;
?>