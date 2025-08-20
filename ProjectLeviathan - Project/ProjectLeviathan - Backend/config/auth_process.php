<?php
// Establece la zona horaria a UTC para todos los cálculos de fecha y hora.
date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';

// --- INICIO DE LA LÓGICA DE SEGURIDAD CORREGIDA Y SIMPLIFICADA ---
define('MAX_ATTEMPTS', 5);
define('BLOCK_TIME_MINUTES', 15);
define('RESEND_COOLDOWN_SECONDS', 120); // 2 minutos de cooldown para reenvío

/**
 * Función 1: Comprueba SI YA EXISTE un bloqueo de tiempo activo.
 * Su única responsabilidad es leer, no escribir.
 */
function check_for_active_block($pdo, $identifier, $ip, $type) {
    $stmt = $pdo->prepare("SELECT blocked_until FROM security_logs WHERE user_identifier = :identifier AND ip_address = :ip AND attempt_type = :type");
    $stmt->execute(['identifier' => $identifier, 'ip' => $ip, 'type' => $type]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($log && $log['blocked_until'] && strtotime($log['blocked_until']) > time()) {
        // Si el bloqueo está activo, detiene la ejecución inmediatamente.
        send_response(false, 'Has excedido el número de intentos. Por favor, espera ' . BLOCK_TIME_MINUTES . ' minutos.');
    }
    return true; // Si no hay bloqueo, permite continuar.
}

/**
 * Función 2: Registra un intento fallido y, SI ES NECESARIO, crea un nuevo bloqueo.
 */
function record_failed_attempt_and_block_if_needed($pdo, $identifier, $ip, $type) {
    // Busca el registro actual para esta combinación.
    $stmt = $pdo->prepare("SELECT id, attempts, total_failures FROM security_logs WHERE user_identifier = :identifier AND ip_address = :ip AND attempt_type = :type");
    $stmt->execute(['identifier' => $identifier, 'ip' => $ip, 'type' => $type]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($log) {
        // Si ya existe un registro, incrementa ambos contadores.
        $new_attempts = $log['attempts'] + 1;
        $new_total_failures = $log['total_failures'] + 1;
        
        // Ahora comprueba si este nuevo intento debe causar un bloqueo.
        if ($new_attempts >= MAX_ATTEMPTS) {
            $blocked_until = date('Y-m-d H:i:s', strtotime('+' . BLOCK_TIME_MINUTES . ' minutes'));
            // Actualiza el registro, establece el bloqueo Y reinicia el contador temporal.
            $stmt_update = $pdo->prepare("UPDATE security_logs SET attempts = 0, total_failures = :total_failures, last_attempt_at = NOW(), blocked_until = :blocked_until WHERE id = :id");
            $stmt_update->execute([
                'total_failures' => $new_total_failures,
                'blocked_until' => $blocked_until,
                'id' => $log['id']
            ]);
            // Informa al usuario que acaba de ser bloqueado.
            send_response(false, 'Has excedido el número de intentos. Por favor, espera ' . BLOCK_TIME_MINUTES . ' minutos.');
        } else {
            // Si aún no se alcanza el límite, solo actualiza los contadores.
            $stmt_update = $pdo->prepare("UPDATE security_logs SET attempts = :attempts, total_failures = :total_failures, last_attempt_at = NOW() WHERE id = :id");
            $stmt_update->execute([
                'attempts' => $new_attempts,
                'total_failures' => $new_total_failures,
                'id' => $log['id']
            ]);
        }
    } else {
        // Si es el primer fallo, crea el registro.
        $stmt_insert = $pdo->prepare("INSERT INTO security_logs (user_identifier, ip_address, attempt_type, last_attempt_at) VALUES (:identifier, :ip, :type, NOW())");
        $stmt_insert->execute(['identifier' => $identifier, 'ip' => $ip, 'type' => $type]);
    }
}

/**
 * Nueva función: Verificar cooldown de reenvío de código
 */
function check_resend_cooldown($pdo, $email, $type) {
    $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE user_email = :email AND type = :type ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['email' => $email, 'type' => $type]);
    $last_code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_code) {
        $time_since_last = time() - strtotime($last_code['created_at']);
        if ($time_since_last < RESEND_COOLDOWN_SECONDS) {
            $remaining_seconds = RESEND_COOLDOWN_SECONDS - $time_since_last;
            return $remaining_seconds;
        }
    }
    return 0;
}

// --- FIN DE LA LÓGICA DE SEGURIDAD CORREGIDA Y SIMPLIFICADA ---

/**
 * Limpia los registros de seguridad cuando una operación es exitosa.
 */
function clear_security_logs($pdo, $identifier, $type) {
    $stmt = $pdo->prepare("DELETE FROM security_logs WHERE user_identifier = :identifier AND attempt_type = :type");
    $stmt->execute(['identifier' => $identifier, 'type' => $type]);
}


function send_response($success, $message, $extra_data = [])
{
    $response = ['success' => $success, 'message' => $message];
    echo json_encode(array_merge($response, $extra_data));
    exit;
}

function is_csrf_valid()
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

function get_frontend_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = str_replace('/ProjectLeviathan - Backend/config', '/ProjectLeviathan - Frontend', dirname($_SERVER['SCRIPT_NAME']));
    return rtrim($protocol . $host . $base_path, '/') . '/';
}

function determineUserLanguage($browser_lang) {
    $available_languages = ['es-MX', 'en-US'];
    $primary_lang = strtok($browser_lang, ',');
    if (in_array($primary_lang, $available_languages)) {
        return $primary_lang;
    }
    $short_lang = substr($primary_lang, 0, 2);
    foreach ($available_languages as $lang) {
        if (substr($lang, 0, 2) === $short_lang) {
            return $lang;
        }
    }
    return 'en-US';
}

function generate_verification_code() {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $part1 = substr(str_shuffle(str_repeat($chars, 4)), 0, 4);
    $part2 = substr(str_shuffle(str_repeat($chars, 4)), 0, 4);
    $part3 = substr(str_shuffle(str_repeat($chars, 4)), 0, 4);
    return $part1 . '-' . $part2 . '-' . $part3;
}

$action = $_POST['action'] ?? '';
$user_ip = $_SERVER['REMOTE_ADDR'] ?: 'UNKNOWN';

if ($action === 'get_csrf_token') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    send_response(true, 'Token generado.', ['csrf_token' => $_SESSION['csrf_token']]);
}

if (!is_csrf_valid()) {
    send_response(false, 'Error de validación de seguridad. Por favor, recarga la página.');
}

switch ($action) {
    case 'login':
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Primero, comprueba si ya estás bloqueado.
        check_for_active_block($pdo, $email, $user_ip, 'login');

        if (!$email || empty($password)) {
            send_response(false, 'Por favor, completa todos los campos.');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, phone_number, password, role, created_at, status FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Si el login es exitoso...
                    if ($user['status'] !== 'active') {
                        $_SESSION['status_reason'] = $user['status'];
                        send_response(false, 'Cuenta inactiva.', ['redirect_url' => 'inactive-account']);
                        exit;
                    }
                    clear_security_logs($pdo, $email, 'login');
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['phone_number'] = $user['phone_number'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['created_at'] = $user['created_at'];
                    $redirect_url = get_frontend_url();
                    send_response(true, 'Inicio de sesión exitoso.', ['redirect_url' => $redirect_url]);
                } else {
                    // Si el login falla...
                    record_failed_attempt_and_block_if_needed($pdo, $email, $user_ip, 'login');
                    // Envía una respuesta genérica para no revelar si el correo existe o no.
                    send_response(false, 'Correo o contraseña incorrectos.');
                }
            } catch (PDOException $e) {
                send_response(false, 'Error del servidor. Inténtalo de nuevo más tarde.');
            }
        }
        break;

    case 'validate_step1':
        $ip_limit = 3;
        $time_limit_hours = 8;
        $_SESSION['client_metadata'] = ['ip_address' => $user_ip, 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US'];
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            send_response(false, 'Correo electrónico inválido.');
        } else {
            try {
                if ($user_ip !== 'UNKNOWN') {
                    $time_limit = date('Y-m-d H:i:s', strtotime('-' . $time_limit_hours . ' hours'));
                    $stmt_ip = $pdo->prepare("SELECT COUNT(*) FROM users_metadata WHERE ip_address = :ip AND created_at >= :time_limit");
                    $stmt_ip->execute(['ip' => $user_ip, 'time_limit' => $time_limit]);
                    if ($stmt_ip->fetchColumn() >= $ip_limit) {
                        send_response(false, 'Se ha alcanzado el límite de cuentas para esta red. Inténtalo de nuevo más tarde.');
                    }
                }
                $stmt_email = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt_email->execute(['email' => $email]);
                if ($stmt_email->fetch()) {
                    send_response(false, 'Este correo electrónico ya está registrado.');
                } else {
                    send_response(true, 'Email validado.');
                }
            } catch (PDOException $e) {
                send_response(false, 'Error del servidor.');
            }
        }
        break;

    case 'generate_code':
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $phone_number = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_NUMBER_INT);
        $country_code = filter_input(INPUT_POST, 'country_code', FILTER_SANITIZE_STRING);
        $username = $_POST['username'] ?? '';
        if (!preg_match('/^[a-zA-Z0-9_]{4,25}$/', $username) || !preg_match('/^[0-9]{10,15}$/', $phone_number) || !$email || empty($password)) {
            send_response(false, 'Datos de formulario inválidos o incompletos.');
        } else {
            try {
                $full_phone = $country_code . $phone_number;
                $stmt_check = $pdo->prepare("SELECT username FROM users WHERE username = :username OR phone_number = :phone");
                $stmt_check->execute(['username' => $username, 'phone' => $full_phone]);
                if ($stmt_check->fetch()) {
                    send_response(false, 'El nombre de usuario o teléfono ya están en uso.');
                } else {
                    $_SESSION['registration_data'] = ['email' => $email, 'username' => $username, 'full_phone' => $full_phone, 'hashed_password' => password_hash($password, PASSWORD_BCRYPT)];
                    $stmt_delete_old = $pdo->prepare("DELETE FROM verification_codes WHERE user_email = :email AND type = 'account_verification'");
                    $stmt_delete_old->execute(['email' => $email]);
                    $verification_code = generate_verification_code();
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $stmt_code = $pdo->prepare("INSERT INTO verification_codes (user_email, phone_number, type, code, ip_address, expires_at) VALUES (:email, :phone, 'account_verification', :code, :ip, :expires)");
                    $stmt_code->execute(['email' => $email, 'phone' => $full_phone, 'code' => $verification_code, 'ip' => $user_ip, 'expires' => $expires_at]);
                    
                    // Enviar el tiempo de cooldown inicial
                    send_response(true, 'Código generado.', [
                        'email' => $email,
                        'cooldown_seconds' => RESEND_COOLDOWN_SECONDS
                    ]);
                }
            } catch (PDOException $e) {
                send_response(false, 'Error del servidor.');
            }
        }
        break;

    case 'verify_account':
        if (!isset($_SESSION['registration_data']) || !isset($_SESSION['client_metadata'])) {
            send_response(false, 'Sesión expirada. Reinicia el proceso.');
        } else {
            $reg_data = $_SESSION['registration_data'];
            $meta_data = $_SESSION['client_metadata'];
            $code = $_POST['verification_code'] ?? '';
            try {
                $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE user_email = :email AND phone_number = :phone AND type = 'account_verification' ORDER BY id DESC LIMIT 1");
                $stmt->execute(['email' => $reg_data['email'], 'phone' => $reg_data['full_phone']]);
                $verification = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$verification || $verification['code'] !== $code || strtotime($verification['expires_at']) < time()) {
                    send_response(false, 'El código de verificación es inválido o ha expirado.');
                }
                
                $pdo->beginTransaction();

                // --- INICIO DE LA MODIFICACIÓN ---
                // Verificación final y atómica para prevenir race conditions.
                $stmt_final_check = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username OR phone_number = :phone");
                $stmt_final_check->execute([
                    'email' => $reg_data['email'],
                    'username' => $reg_data['username'],
                    'phone' => $reg_data['full_phone']
                ]);
                if ($stmt_final_check->fetch()) {
                    $pdo->rollBack(); // Se revierte la transacción.
                    send_response(false, 'El correo, usuario o teléfono ya han sido registrados por otra persona. Por favor, intenta de nuevo.');
                    exit;
                }
                // --- FIN DE LA MODIFICACIÓN ---

                $user_role = 'user';
                $stmt_user = $pdo->prepare("INSERT INTO users (uuid, username, email, phone_number, password, role, created_at) VALUES (:uuid, :user, :email, :phone, :pass, :role, NOW())");
                $stmt_user->execute(['uuid' => bin2hex(random_bytes(16)), 'user' => $reg_data['username'], 'email' => $reg_data['email'], 'phone' => $reg_data['full_phone'], 'pass' => $reg_data['hashed_password'], 'role' => $user_role]);
                $user_id = $pdo->lastInsertId();
                $stmt_meta = $pdo->prepare("INSERT INTO users_metadata (user_id, ip_address, user_agent) VALUES (:id, :ip, :agent)");
                $stmt_meta->execute(['id' => $user_id, 'ip' => $meta_data['ip_address'], 'agent' => $meta_data['user_agent']]);
                $user_language = determineUserLanguage($meta_data['language']);
                $stmt_prefs = $pdo->prepare("INSERT INTO user_preferences (user_id, language) VALUES (:user_id, :language)");
                $stmt_prefs->execute(['user_id' => $user_id, 'language' => $user_language]);
                $stmt_del = $pdo->prepare("DELETE FROM verification_codes WHERE id = :id");
                $stmt_del->execute(['id' => $verification['id']]);
                $pdo->commit();
                unset($_SESSION['registration_data'], $_SESSION['client_metadata']);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $reg_data['username'];
                $_SESSION['email'] = $reg_data['email'];
                $_SESSION['phone_number'] = $reg_data['full_phone'];
                $_SESSION['role'] = $user_role;
                $redirect_url = get_frontend_url();
                send_response(true, '¡Cuenta creada!', ['redirect_url' => $redirect_url]);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Error en verify_account: " . $e->getMessage());
                send_response(false, 'Error del servidor al crear la cuenta.');
            }
        }
        break;

    case 'send_code':
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        check_for_active_block($pdo, $email, $user_ip, 'password_reset');
        
        if (!$email) {
            send_response(false, 'El formato del correo no es válido.');
        }
        try {
            $stmt = $pdo->prepare("SELECT id, status, phone_number FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['status'] === 'active') {
                $stmt_delete_old = $pdo->prepare("DELETE FROM verification_codes WHERE user_email = :email AND type = 'password_reset'");
                $stmt_delete_old->execute(['email' => $email]);
                $verification_code = generate_verification_code();
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $stmt_code = $pdo->prepare("INSERT INTO verification_codes (user_email, phone_number, type, code, ip_address, expires_at) VALUES (:email, :phone, 'password_reset', :code, :ip, :expires)");
                $stmt_code->execute(['email' => $email, 'phone' => $user['phone_number'], 'code' => $verification_code, 'ip' => $user_ip, 'expires' => $expires_at]);
                $_SESSION['reset_email'] = $email;
                
                // Enviar el tiempo de cooldown inicial
                send_response(true, 'Código enviado.', [
                    'email' => $email,
                    'cooldown_seconds' => RESEND_COOLDOWN_SECONDS
                ]);
            } else {
                record_failed_attempt_and_block_if_needed($pdo, $email, $user_ip, 'password_reset');
                send_response(false, 'No se encontró una cuenta activa con ese correo.');
            }
        } catch (PDOException $e) {
            send_response(false, 'Error del servidor al procesar el correo.');
        }
        break;

    case 'verify_code':
        $code = $_POST['verification_code'] ?? '';
        $email = $_SESSION['reset_email'] ?? null;
        check_for_active_block($pdo, $email, $user_ip, 'password_reset');

        if (!$email || empty($code)) {
            send_response(false, 'El código no es válido o la sesión ha expirado.');
        }
        try {
            $stmt_user = $pdo->prepare("SELECT phone_number FROM users WHERE email = :email");
            $stmt_user->execute(['email' => $email]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                send_response(false, 'Error de sesión de usuario.');
            }
            $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE user_email = :email AND phone_number = :phone AND type = 'password_reset' ORDER BY id DESC LIMIT 1");
            $stmt->execute(['email' => $email, 'phone' => $user['phone_number']]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verification || $verification['code'] !== $code || strtotime($verification['expires_at']) < time()) {
                record_failed_attempt_and_block_if_needed($pdo, $email, $user_ip, 'password_reset');
                send_response(false, 'El código de verificación es inválido o ha expirado.');
            }
            
            clear_security_logs($pdo, $email, 'password_reset');
            $_SESSION['reset_code_verified'] = true;
            $stmt_del = $pdo->prepare("DELETE FROM verification_codes WHERE id = :id");
            $stmt_del->execute(['id' => $verification['id']]);
            send_response(true, 'Código verificado.');
        } catch (PDOException $e) {
            send_response(false, 'Error del servidor al verificar.');
        }
        break;

    case 'reset_password':
        $password = $_POST['password'] ?? '';
        $email = $_SESSION['reset_email'] ?? null;
        $code_verified = $_SESSION['reset_code_verified'] ?? false;
        if (!$email || !$code_verified || strlen($password) < 8 || $password !== ($_POST['confirm_password'] ?? '')) {
            send_response(false, 'Datos inválidos o la verificación ha fallado.');
        }
        try {
            $pdo->beginTransaction();
            $stmt_user = $pdo->prepare("SELECT id, password FROM users WHERE email = :email");
            $stmt_user->execute(['email' => $email]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            if (!$user || password_verify($password, $user['password'])) {
                $pdo->rollBack();
                send_response(false, 'La contraseña no puede ser la misma que la actual.');
            }
            $stmt_update = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt_update->execute(['password' => password_hash($password, PASSWORD_BCRYPT), 'email' => $email]);
            $stmt_history = $pdo->prepare("INSERT INTO user_update_history (user_id, field_changed) VALUES (:user_id, 'password')");
            $stmt_history->execute(['user_id' => $user['id']]);
            $pdo->commit();
            unset($_SESSION['reset_email'], $_SESSION['reset_code_verified']);
            send_response(true, 'Contraseña actualizada.', ['redirect_url' => 'login']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            send_response(false, 'Error del servidor al actualizar.');
        }
        break;

    case 'resend_code':
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            send_response(false, 'No se pudo identificar el correo electrónico para reenviar el código.');
        }
        
        try {
            $type = '';
            $phone_number = '';
            if (isset($_SESSION['registration_data']) && $_SESSION['registration_data']['email'] === $email) {
                $type = 'account_verification';
                $phone_number = $_SESSION['registration_data']['full_phone'];
            } elseif (isset($_SESSION['reset_email']) && $_SESSION['reset_email'] === $email) {
                $type = 'password_reset';
                $stmt_phone = $pdo->prepare("SELECT phone_number FROM users WHERE email = :email");
                $stmt_phone->execute(['email' => $email]);
                $phone_number = $stmt_phone->fetchColumn();
            } else {
                 send_response(false, 'La sesión ha expirado o es inválida. Por favor, reinicia el proceso.');
            }
            
            if(empty($phone_number)){
                 send_response(false, 'No se encontró un número de teléfono asociado.');
            }
            
            // VERIFICAR COOLDOWN DE REENVÍO
            $remaining_cooldown = check_resend_cooldown($pdo, $email, $type);
            if ($remaining_cooldown > 0) {
                send_response(false, 'Debes esperar antes de solicitar otro código.', [
                    'cooldown_seconds' => $remaining_cooldown
                ]);
            }
            
            $stmt_delete_old = $pdo->prepare("DELETE FROM verification_codes WHERE user_email = :email AND type = :type");
            $stmt_delete_old->execute(['email' => $email, 'type' => $type]);
            $verification_code = generate_verification_code();
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt_code = $pdo->prepare("INSERT INTO verification_codes (user_email, phone_number, type, code, ip_address, expires_at) VALUES (:email, :phone, :type, :code, :ip, :expires)");
            $stmt_code->execute(['email' => $email, 'phone' => $phone_number, 'type' => $type, 'code' => $verification_code, 'ip' => $user_ip, 'expires' => $expires_at]);
            
            send_response(true, 'Se ha generado y enviado un nuevo código de verificación.', [
                'cooldown_seconds' => RESEND_COOLDOWN_SECONDS
            ]);
        } catch (PDOException $e) {
            error_log("Error en resend_code: " . $e->getMessage());
            send_response(false, 'Error del servidor al reenviar el código.');
        }
        break;

    default:
        send_response(false, "La acción '{$action}' no es válida.");
        break;
}
?>