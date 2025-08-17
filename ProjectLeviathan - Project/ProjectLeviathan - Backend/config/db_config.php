<?php
// db_config.php - Archivo de configuración de la base de datos

// --- DEFINIR CREDENCIALES ---
define('DB_HOST', 'localhost');    // Tu servidor de base de datos (usualmente localhost)
define('DB_USER', 'root');         // Tu usuario de la base de datos
define('DB_PASS', '');             // La contraseña para ese usuario
define('DB_NAME', 'project_db');   // El nombre de tu base de datos

// --- CREAR LA CONEXIÓN ---
try {
    // Usamos PDO para una conexión más segura y versátil
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

    // Configurar PDO para que reporte errores de SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Evitar emulación de sentencias preparadas para mayor seguridad
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // --- INICIO DE LA MODIFICACIÓN ---
    // Registrar el error detallado en el archivo de log de errores del servidor.
    // Asegúrate de que PHP esté configurado para registrar errores.
    error_log("Error de conexión a la base de datos: " . $e->getMessage());

    // Enviar un código de estado HTTP 500 (Error Interno del Servidor).
    http_response_code(500);
    
    // Mostrar un mensaje genérico y seguro al usuario.
    // En una aplicación real, podrías tener una página de error diseñada para esto.
    die("ERROR: Ha ocurrido un problema con el servidor. Por favor, inténtalo de nuevo más tarde.");
    // --- FIN DE LA MODIFICACIÓN ---
}
?>