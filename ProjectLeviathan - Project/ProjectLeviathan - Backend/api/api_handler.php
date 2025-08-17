<?php
session_start();
header('Content-Type: application/json');

// Incluir configuración de la base de datos
require_once __DIR__ . '/../config/db_config.php';

// Validar el token CSRF para todas las peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']))) {
    echo json_encode(['success' => false, 'message' => 'Error de validación de seguridad.']);
    exit;
}

// Obtener la acción a realizar
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// --- ACCIÓN: OBTENER DETALLES DE UN GRUPO Y VALIDAR MEMBRESÍA ---
if ($action === 'get_group_details') {
    $groupUuid = $_GET['group_uuid'] ?? '';
    if (empty($userId) || empty($groupUuid)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
        exit;
    }
    try {
        // --- INICIO DE LA CORRECCIÓN ---
        // 1. Obtener los detalles del grupo (lógica secuencial para evitar errores con UNION)
        $stmt_municipality = $pdo->prepare("SELECT group_title FROM group_municipality WHERE uuid = :uuid");
        $stmt_municipality->execute(['uuid' => $groupUuid]);
        $group = $stmt_municipality->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            $stmt_university = $pdo->prepare("SELECT group_title FROM group_university WHERE uuid = :uuid");
            $stmt_university->execute(['uuid' => $groupUuid]);
            $group = $stmt_university->fetch(PDO::FETCH_ASSOC);
        }

        if (!$group) {
            // Si el grupo no se encuentra en ninguna tabla, el UUID es incorrecto.
            echo json_encode(['success' => false, 'message' => 'Error al cargar la conversación.']);
            exit;
        }

        // 2. Si el grupo existe, ahora se valida la membresía del usuario.
        $stmt_member_check = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid");
        $stmt_member_check->execute(['user_id' => $userId, 'group_uuid' => $groupUuid]);
        
        if ($stmt_member_check->rowCount() === 0) {
            // Si no es miembro, el error es sobre la pertenencia.
            echo json_encode(['success' => false, 'message' => 'No perteneces a este grupo.']);
            exit;
        }
        
        // 3. Si ambas validaciones pasan, se envían los datos del grupo.
        echo json_encode(['success' => true, 'group' => $group]);
        // --- FIN DE LA CORRECCIÓN ---

    } catch (PDOException $e) {
        error_log("API Error (get_group_details): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor.']);
    }
    exit;
}


// --- ACCIÓN: OBTENER GRUPOS DEL USUARIO ---
if ($action === 'get_user_groups') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare(
            "SELECT 
                CASE
                    WHEN gm.group_type = 'municipality' THEN m.uuid
                    WHEN gm.group_type = 'university' THEN u.uuid
                END AS uuid,
                CASE
                    WHEN gm.group_type = 'municipality' THEN m.group_title
                    WHEN gm.group_type = 'university' THEN u.group_title
                END AS group_title,
                CASE
                    WHEN gm.group_type = 'municipality' THEN CONCAT('Un espacio para la comunidad de ', m.group_title)
                    WHEN gm.group_type = 'university' THEN CONCAT('Comunidad de ', u.group_title)
                END AS group_subtitle,
                gm.group_type,
                CASE
                    WHEN gm.group_type = 'municipality' THEN m.members
                    WHEN gm.group_type = 'university' THEN u.members
                END AS members
             FROM group_members gm
             LEFT JOIN group_municipality m ON gm.group_uuid = m.uuid AND gm.group_type = 'municipality'
             LEFT JOIN group_university u ON gm.group_uuid = u.uuid AND gm.group_type = 'university'
             WHERE gm.user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (PDOException $e) {
        error_log("API Error (get_user_groups): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al obtener tus grupos.']);
    }
    exit;
}

// --- ACCIÓN: OBTENER MENSAJES DE UN GRUPO (VERIFICACIÓN DE MEMBRESÍA) ---
if ($action === 'get_chat_messages') {
    $groupUuid = $_GET['group_uuid'] ?? '';
    if (empty($userId) || empty($groupUuid)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para cargar el chat.']);
        exit;
    }
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid");
        $stmt_check->execute(['user_id' => $userId, 'group_uuid' => $groupUuid]);
        if ($stmt_check->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Ya no perteneces a este grupo.']);
            exit;
        }

        echo json_encode(['success' => true, 'messages' => []]);

    } catch (PDOException $e) {
        error_log("API Error (get_chat_messages): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al verificar la membresía.']);
    }
    exit;
}

// (El resto del archivo permanece igual)

// --- ACCIÓN: OBTENER TODOS LOS MUNICIPIOS ---
if ($action === 'get_municipalities') {
    try {
        $stmt = $pdo->query(
            "SELECT m.id, m.group_title, COUNT(u.id) as university_count 
             FROM group_municipality m 
             LEFT JOIN group_university u ON m.id = u.municipality_id 
             GROUP BY m.id, m.group_title 
             ORDER BY m.group_title ASC"
        );
        $municipalities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'municipalities' => $municipalities]);
    } catch (PDOException $e) {
        error_log("API Error (get_municipalities): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al obtener los municipios.']);
    }
    exit;
}


// --- ACCIÓN: OBTENER GRUPOS DE MUNICIPIOS ---
if ($action === 'get_municipality_groups') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare(
            "SELECT g.uuid, g.group_title, g.privacy, g.members,
                    (gm.user_id IS NOT NULL) AS is_member
             FROM group_municipality g 
             LEFT JOIN group_members gm ON g.uuid = gm.group_uuid AND gm.user_id = :user_id AND gm.group_type = 'municipality'
             ORDER BY g.group_title ASC"
        );
        $stmt->execute(['user_id' => $userId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($groups as &$group) {
            $group['is_member'] = (bool)$group['is_member'];
            $group['members'] = (int)$group['members'];
            $group['group_subtitle'] = 'Un espacio para la comunidad de ' . $group['group_title'] . '.';
        }

        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (PDOException $e) {
        error_log("API Error (get_municipality_groups): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al obtener los grupos.']);
    }
    exit;
}

// --- ACCIÓN: OBTENER GRUPOS DE UNIVERSIDADES (CON FILTRO) ---
if ($action === 'get_university_groups') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
        exit;
    }
    
    $municipalityId = $_GET['municipality_id'] ?? 'all';

    try {
        $sql = "SELECT u.uuid, u.group_title, u.privacy, u.members, 
                       m.group_title as municipality_name,
                       (gm.user_id IS NOT NULL) AS is_member
                FROM group_university u
                JOIN group_municipality m ON u.municipality_id = m.id
                LEFT JOIN group_members gm ON u.uuid = gm.group_uuid AND gm.user_id = :user_id AND gm.group_type = 'university'";

        $params = ['user_id' => $userId];

        if ($municipalityId !== 'all' && is_numeric($municipalityId)) {
            $sql .= " WHERE u.municipality_id = :municipality_id";
            $params['municipality_id'] = $municipalityId;
        }

        $sql .= " ORDER BY u.group_title ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($groups as &$group) {
            $group['is_member'] = (bool)$group['is_member'];
            $group['members'] = (int)$group['members'];
            $group['group_subtitle'] = 'Comunidad de ' . $group['group_title'] . ' en ' . $group['municipality_name'];
        }

        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (PDOException $e) {
        error_log("API Error (get_university_groups): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al obtener las universidades.']);
    }
    exit;
}


// --- ACCIÓN: UNIRSE O ABANDONAR UN GRUPO (LÓGICA POLIMÓRFICA) ---
if ($action === 'toggle_group_membership') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para unirte a un grupo.']);
        exit;
    }

    $groupUuid = $_POST['group_uuid'] ?? '';
    $groupType = $_POST['group_type'] ?? '';

    if (empty($groupUuid) || !in_array($groupType, ['municipality', 'university'])) {
        echo json_encode(['success' => false, 'message' => 'Datos de grupo inválidos.']);
        exit;
    }

    $groupTable = 'group_' . $groupType;

    try {
        $pdo->beginTransaction();

        $stmt_group = $pdo->prepare("SELECT id, members, privacy FROM `$groupTable` WHERE uuid = :uuid");
        $stmt_group->execute(['uuid' => $groupUuid]);
        $group = $stmt_group->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'El grupo no existe.']);
            exit;
        }
        $groupId = $group['id'];
        $currentMembers = $group['members'];

        $stmt_member = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid AND group_type = :group_type");
        $stmt_member->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
        $isMember = $stmt_member->fetch();

        if ($isMember) {
            $stmt_delete = $pdo->prepare("DELETE FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid AND group_type = :group_type");
            $stmt_delete->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
            
            $newMemberCount = max(0, $currentMembers - 1);
            $stmt_update = $pdo->prepare("UPDATE `$groupTable` SET members = :members WHERE id = :id");
            $stmt_update->execute(['members' => $newMemberCount, 'id' => $groupId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'action' => 'left', 'newMemberCount' => $newMemberCount, 'message' => 'Has abandonado el grupo.']);
        } else {
            if ($group['privacy'] === 'public') {
                $stmt_insert = $pdo->prepare("INSERT INTO group_members (user_id, group_uuid, group_type) VALUES (:user_id, :group_uuid, :group_type)");
                $stmt_insert->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
                
                $newMemberCount = $currentMembers + 1;
                $stmt_update = $pdo->prepare("UPDATE `$groupTable` SET members = :members WHERE id = :id");
                $stmt_update->execute(['members' => $newMemberCount, 'id' => $groupId]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'action' => 'joined', 'newMemberCount' => $newMemberCount, 'message' => 'Te has unido al grupo.']);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => true, 'action' => 'private', 'message' => 'Este grupo es privado y requiere un código de acceso.']);
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (toggle_group_membership): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor.']);
    }
    exit;
}

// --- ACCIÓN: UNIRSE A GRUPO PRIVADO CON CÓDIGO (LÓGICA POLIMÓRFICA) ---
if ($action === 'join_private_group') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para unirte.']);
        exit;
    }

    $groupUuid = $_POST['group_uuid'] ?? '';
    $accessCode = $_POST['access_code'] ?? '';
    $groupType = $_POST['group_type'] ?? '';

    if (empty($groupUuid) || empty($accessCode) || !in_array($groupType, ['municipality', 'university'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para unirte al grupo.']);
        exit;
    }
    
    $groupTable = 'group_' . $groupType;

    try {
        $pdo->beginTransaction();

        $stmt_group = $pdo->prepare("SELECT id, members, access_code FROM `$groupTable` WHERE uuid = :uuid AND privacy = 'private'");
        $stmt_group->execute(['uuid' => $groupUuid]);
        $group = $stmt_group->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Este grupo no es privado o no existe.']);
            exit;
        }

        if ($group['access_code'] !== $accessCode) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'El código de acceso es incorrecto.']);
            exit;
        }
        
        $groupId = $group['id'];
        $currentMembers = $group['members'];

        $stmt_insert = $pdo->prepare("INSERT INTO group_members (user_id, group_uuid, group_type) VALUES (:user_id, :group_uuid, :group_type) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt_insert->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);

        if ($stmt_insert->rowCount() > 0) {
            $newMemberCount = $currentMembers + 1;
            $stmt_update = $pdo->prepare("UPDATE `$groupTable` SET members = :members WHERE id = :id");
            $stmt_update->execute(['members' => $newMemberCount, 'id' => $groupId]);
        } else {
            $newMemberCount = $currentMembers;
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'action' => 'joined', 'newMemberCount' => $newMemberCount, 'message' => 'Te has unido al grupo con éxito.']);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (join_private_group): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor.']);
    }
    exit;
}

// --- ACCIÓN: ACTUALIZAR UNA PREFERENCIA DE USUARIO ---
if ($action === 'update_preference') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
        exit;
    }

    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';

    $allowed_fields = ['language', 'usage_type', 'open_links_in_new_tab', 'show_sensitive_content', 'theme', 'shortcuts_need_modifier', 'high_contrast_colors'];
    
    if (empty($field) || !in_array($field, $allowed_fields) || $value === '') {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos para la actualización.']);
        exit;
    }

    if (in_array($field, ['open_links_in_new_tab', 'show_sensitive_content', 'shortcuts_need_modifier', 'high_contrast_colors'])) {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    if ($field === 'language' && !in_array($value, ['es-MX', 'en-US'])) {
        echo json_encode(['success' => false, 'message' => 'Idioma no soportado.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE user_preferences SET `$field` = :value WHERE user_id = :user_id");
        $stmt->execute(['value' => $value, 'user_id' => $userId]);

        if ($stmt->rowCount() > 0 && isset($_SESSION['user_preferences'])) {
            $_SESSION['user_preferences'][$field] = $value;
        }

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Preferencia actualizada con éxito.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No se realizaron cambios.']);
        }
    } catch (PDOException $e) {
        error_log("API Error (update_preference): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al actualizar la preferencia.']);
    }
    exit;
}

// --- ACCIÓN: OBTENER FECHAS DE LA CUENTA ---
if ($action === 'get_account_dates') {
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
        exit;
    }
    $response = ['success' => false];
    try {
        setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'Spanish');
        $stmt_creation = $pdo->prepare("SELECT created_at FROM users WHERE id = :user_id");
        $stmt_creation->execute(['user_id' => $userId]);
        $creation_date = $stmt_creation->fetchColumn();
        $stmt_pass_update = $pdo->prepare(
            "SELECT updated_at FROM user_update_history 
             WHERE user_id = :user_id AND field_changed = 'password' 
             ORDER BY updated_at DESC LIMIT 1"
        );
        $stmt_pass_update->execute(['user_id' => $userId]);
        $last_password_update = $stmt_pass_update->fetchColumn();
        $response['success'] = true;
        $response['creation_date'] = $creation_date ? strftime('%e de %B de %Y a las %I:%M %p', strtotime($creation_date)) : 'No disponible';
        $response['last_password_update'] = $last_password_update ? 'Última actualización: ' . strftime('%e de %B de %Y a las %I:%M %p', strtotime($last_password_update)) : 'Aún no has actualizado tu contraseña.';
    } catch (PDOException $e) {
        error_log("API Error (get_account_dates): " . $e->getMessage());
        $response['message'] = 'Error del servidor.';
    }
    echo json_encode($response);
    exit;
}

// --- ACCIÓN: ACTUALIZAR PERFIL (NOMBRE DE USUARIO O CORREO) ---
if ($action === 'update_profile') {
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');

    if (empty($field) || empty($value) || empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para realizar la actualización.']);
        exit;
    }

    $response = ['success' => false];

    try {
        $stmt_old_query = "";
        if ($field === 'username') $stmt_old_query = "SELECT `username` FROM users WHERE id = :user_id";
        elseif ($field === 'email') $stmt_old_query = "SELECT `email` FROM users WHERE id = :user_id";
        
        $stmt_old = $pdo->prepare($stmt_old_query);
        $stmt_old->execute(['user_id' => $userId]);
        $old_value = $stmt_old->fetchColumn();

        if ($old_value === $value) {
            echo json_encode(['success' => true, 'newValue' => htmlspecialchars($value), 'message' => 'No se realizaron cambios.']);
            exit;
        }

        $stmt_time = $pdo->prepare("SELECT updated_at FROM user_update_history WHERE user_id = :user_id AND field_changed = :field ORDER BY updated_at DESC LIMIT 1");
        $stmt_time->execute(['user_id' => $userId, 'field' => $field]);
        $last_update_record = $stmt_time->fetch(PDO::FETCH_ASSOC);

        if ($last_update_record) {
            $last_update_time = new DateTime($last_update_record['updated_at']);
            $current_time = new DateTime();
            $interval_seconds = $current_time->getTimestamp() - $last_update_time->getTimestamp();
            $limit_seconds = 30 * 24 * 60 * 60;

            if ($interval_seconds < $limit_seconds) {
                $days_left = ceil(($limit_seconds - $interval_seconds) / (24 * 60 * 60));
                $field_name = $field === 'username' ? 'nombre de usuario' : 'correo';
                $response['message'] = "Debes esperar " . $days_left . " día(s) más para volver a cambiar tu " . $field_name . ".";
                echo json_encode($response);
                exit;
            }
        }
        
        if ($field === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._-]+@(gmail\.com|outlook\.com)$/i', $value)) {
                $response['message'] = 'Solo se permiten correos válidos de @gmail.com o @outlook.com.';
                echo json_encode($response);
                exit;
            }
        } elseif ($field === 'username') {
            if (!preg_match('/^[a-zA-Z0-9_]{4,25}$/', $value)) {
                $response['message'] = 'El nombre de usuario debe tener entre 4 y 25 caracteres y solo puede contener letras, números y guiones bajos.';
                echo json_encode($response);
                exit;
            }
        }

        $check_query = "";
        if ($field === 'username') $check_query = "SELECT id FROM users WHERE `username` = :value AND id != :user_id";
        elseif ($field === 'email') $check_query = "SELECT id FROM users WHERE `email` = :value AND id != :user_id";
        
        $stmt_check = $pdo->prepare($check_query);
        $stmt_check->execute(['value' => $value, 'user_id' => $userId]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'Ese ' . ($field === 'username' ? 'nombre de usuario' : 'correo electrónico') . ' ya está en uso.';
            echo json_encode($response);
            exit;
        }

        $pdo->beginTransaction();
        
        $update_query = "";
        if ($field === 'username') $update_query = "UPDATE users SET `username` = :value WHERE id = :user_id";
        elseif ($field === 'email') $update_query = "UPDATE users SET `email` = :value WHERE id = :user_id";
        else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Operación no permitida.']);
            exit;
        }

        $stmt_update = $pdo->prepare($update_query);
        $stmt_update->execute(['value' => $value, 'user_id' => $userId]);

        $stmt_history = $pdo->prepare("INSERT INTO user_update_history (user_id, field_changed, old_value, new_value) VALUES (:user_id, :field, :old_value, :new_value)");
        $stmt_history->execute(['user_id' => $userId, 'field' => $field, 'old_value' => $old_value, 'new_value' => $value]);

        $pdo->commit();
        $_SESSION[$field] = $value;
        $response['success'] = true;
        $response['newValue'] = htmlspecialchars($value);
        $response['message'] = '¡Tu ' . ($field === 'username' ? 'nombre de usuario' : 'correo') . ' se ha actualizado correctamente!';
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (update_profile): " . $e->getMessage());
        $response['message'] = 'Error del servidor al intentar actualizar los datos.';
    }
    echo json_encode($response);
    exit;
}

// --- ACCIÓN: ACTUALIZAR CONTRASEÑA ---
if ($action === 'update_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $response = ['success' => false];
    try {
        $stmt_user = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt_user->execute(['user_id' => $userId]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $response['message'] = 'No se encontró el usuario.';
            echo json_encode($response);
            exit;
        }
        if (!password_verify($current_password, $user['password'])) {
            $response['message'] = 'La contraseña actual es incorrecta.';
            echo json_encode($response);
            exit;
        }
        if (empty($new_password) && empty($confirm_password)) {
            $response['success'] = true;
            echo json_encode($response);
            exit;
        }
        
        if (password_verify($new_password, $user['password'])) {
            $response['message'] = 'La nueva contraseña no puede ser igual a la actual.';
            echo json_encode($response);
            exit;
        }
        if ($new_password !== $confirm_password) {
            $response['message'] = 'Las nuevas contraseñas no coinciden.';
            echo json_encode($response);
            exit;
        }
        if (strlen($new_password) < 8) {
            $response['message'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            echo json_encode($response);
            exit;
        }
        $pdo->beginTransaction();
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt_update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :user_id");
        $stmt_update->execute(['password' => $hashed_password, 'user_id' => $userId]);
        $stmt_history = $pdo->prepare("INSERT INTO user_update_history (user_id, field_changed) VALUES (:user_id, 'password')");
        $stmt_history->execute(['user_id' => $userId]);
        $pdo->commit();
        $response['success'] = true;
        $response['message'] = '¡Tu contraseña ha sido actualizada con éxito!';
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (update_password): " . $e->getMessage());
        $response['message'] = 'Error del servidor al intentar actualizar la contraseña.';
    }
    echo json_encode($response);
    exit;
}

// --- ACCIÓN: ELIMINAR CUENTA ---
if ($action === 'delete_account') {
    $password = $_POST['password'] ?? '';
    $response = ['success' => false];
    if (empty($password) || empty($userId)) {
        $response['message'] = 'Se requiere contraseña para eliminar la cuenta.';
        echo json_encode($response);
        exit;
    }
    try {
        $stmt_user = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt_user->execute(['user_id' => $userId]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $response['message'] = 'No se encontró el usuario.';
            echo json_encode($response);
            exit;
        }
        if (password_verify($password, $user['password'])) {
            $stmt_delete = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = :user_id");
            $stmt_delete->execute(['user_id' => $userId]);
            session_unset();
            session_destroy();
            $response['success'] = true;
            $response['redirect_url'] = '../'; 
        } else {
            $response['message'] = 'La contraseña es incorrecta.';
        }
    } catch (PDOException $e) {
        error_log("API Error (delete_account): " . $e->getMessage());
        $response['message'] = 'Error del servidor al intentar eliminar la cuenta.';
    }
    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
?>