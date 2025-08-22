<?php
date_default_timezone_set('America/Chicago');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';

// Función centralizada para enviar respuestas JSON y terminar la ejecución.
function send_json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

// VALIDACIÓN DE ENTRADAS

// Validar token CSRF para todas las peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        send_json_response(false, 'Error de validación de seguridad.');
    }
}

$action = $_REQUEST['action'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$request_data = [];

// Definir reglas de validación para cada acción
$validation_rules = [
    'get_group_members' => ['group_uuid' => 'required|uuid'],
    'get_group_details' => ['group_uuid' => 'required|uuid'],
    'get_chat_messages' => ['group_uuid' => 'required|uuid', 'offset' => 'integer'],
    'get_university_groups' => ['municipality_id' => 'required|string'],
    'get_municipality_groups' => [],
    'toggle_group_membership' => [
        'group_uuid' => 'required|uuid',
        'group_type' => 'required|enum:municipality,university'
    ],
    'join_private_group' => [
        'group_uuid' => 'required|uuid',
        'group_type' => 'required|enum:municipality,university',
        'access_code' => 'required|string|max:14'
    ],
    'update_preference' => [
        'field' => 'required|string',
        'value' => 'string'
    ],
    'update_profile' => [
        'field' => 'required|enum:username,email',
        'value' => 'required|string|max:126'
    ],
    'update_password' => [
        'current_password' => 'required|string|max:255',
        'new_password' => 'string|max:255',
        'confirm_password' => 'string|max:255'
    ],
    'delete_account' => ['password' => 'required|string|max:255'],
    'get_websocket_token' => [],
    'get_user_groups' => [],
    'get_municipalities' => [],
    'get_account_dates' => [],
    'report_message' => [
        'message_id' => 'required|integer',
        'report_image' => 'boolean' // <-- AÑADIDO
    ],
    'delete_message' => ['message_id' => 'required|integer'],
    'upload_image' => ['group_uuid' => 'required|uuid']
];

if (!array_key_exists($action, $validation_rules)) {
    send_json_response(false, 'Acción no reconocida.');
}

$rules = $validation_rules[$action];
$source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

foreach ($rules as $param => $rule) {
    $value = $source[$param] ?? null;
    $valid = true;
    $message = '';
    $rule_parts = explode('|', $rule);

    foreach ($rule_parts as $part) {
        if ($part === 'required' && ($value === null || $value === '')) {
            $valid = false;
            $message = "El campo '{$param}' es requerido.";
            break;
        }
        if ($value === null || $value === '') continue;
        if ($part === 'uuid' && !preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $value)) {
            $valid = false; $message = "El formato del UUID para '{$param}' no es válido."; break;
        }
        if ($part === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $valid = false; $message = "El campo '{$param}' debe ser un número entero."; break;
        }
        // --- INICIO DE LA MODIFICACIÓN ---
        if ($part === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                $valid = false; $message = "El campo '{$param}' debe ser un valor booleano."; break;
            }
        }
        // --- FIN DE LA MODIFICACIÓN ---
        if (strpos($part, 'enum:') === 0) {
            $options = explode(',', substr($part, 5));
            if (!in_array($value, $options)) {
                $valid = false; $message = "El valor para '{$param}' no es válido."; break;
            }
        }
        if (strpos($part, 'max:') === 0) {
            $max = (int) substr($part, 4);
            if (strlen($value) > $max) {
                $valid = false; $message = "El campo '{$param}' no puede exceder los {$max} caracteres."; break;
            }
        }
    }
    if (!$valid) {
        send_json_response(false, $message);
    }
    $request_data[$param] = is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
}

// ACCIÓN: OBTENER MIEMBROS DE UN GRUPO
if ($action === 'get_group_members') {
    $groupUuid = $request_data['group_uuid'];
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
    }
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid");
        $stmt_check->execute(['user_id' => $userId, 'group_uuid' => $groupUuid]);
        if ($stmt_check->rowCount() === 0) {
            send_json_response(false, 'No tienes permiso para ver los miembros de este grupo.');
        }

        $stmt_members = $pdo->prepare(
            "SELECT u.id, u.username, u.role
             FROM users u
             JOIN group_members gm ON u.id = gm.user_id
             WHERE gm.group_uuid = :group_uuid
             ORDER BY
                FIELD(u.role, 'owner', 'admin', 'community-manager', 'moderator', 'elite', 'premium', 'vip', 'user'),
                u.username ASC"
        );
        $stmt_members->execute(['group_uuid' => $groupUuid]);
        $members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

        send_json_response(true, 'Miembros del grupo obtenidos.', ['members' => $members]);

    } catch (PDOException $e) {
        error_log("API Error (get_group_members): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al obtener los miembros.');
    }
}

// ACCIÓN: GENERAR TOKEN PARA WEBSOCKET
if ($action === 'get_websocket_token') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
    }
    try {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $pdo->prepare(
            "INSERT INTO websocket_auth_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)"
        );
        $stmt->execute(['user_id' => $userId, 'token' => $token, 'expires_at' => $expires_at]);

        send_json_response(true, 'Token generado.', ['token' => $token]);

    } catch (PDOException $e) {
        error_log("API Error (get_websocket_token): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al generar el token.');
    }
}

// ACCIÓN: OBTENER DETALLES DE UN GRUPO Y VALIDAR MEMBRESÍA
if ($action === 'get_group_details') {
    $groupUuid = $request_data['group_uuid'];
    if (empty($userId)) {
        send_json_response(false, 'Faltan datos.');
    }
    try {
        $stmt_municipality = $pdo->prepare("SELECT group_title FROM group_municipality WHERE uuid = :uuid");
        $stmt_municipality->execute(['uuid' => $groupUuid]);
        $group = $stmt_municipality->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            $stmt_university = $pdo->prepare("SELECT group_title FROM group_university WHERE uuid = :uuid");
            $stmt_university->execute(['uuid' => $groupUuid]);
            $group = $stmt_university->fetch(PDO::FETCH_ASSOC);
        }

        if (!$group) {
            send_json_response(false, 'Error al cargar la conversación.');
        }

        $stmt_member_check = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid");
        $stmt_member_check->execute(['user_id' => $userId, 'group_uuid' => $groupUuid]);

        if ($stmt_member_check->rowCount() === 0) {
            send_json_response(false, 'No perteneces a este grupo.');
        }

        send_json_response(true, 'Detalles del grupo obtenidos.', ['group' => $group]);

    } catch (PDOException $e) {
        error_log("API Error (get_group_details): " . $e->getMessage());
        send_json_response(false, 'Error del servidor.');
    }
}


// ACCIÓN: OBTENER GRUPOS DEL USUARIO
if ($action === 'get_user_groups') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
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
        send_json_response(true, 'Grupos del usuario obtenidos.', ['groups' => $groups]);
    } catch (PDOException $e) {
        error_log("API Error (get_user_groups): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al obtener tus grupos.');
    }
}

// ACCIÓN: OBTENER MENSAJES DE UN GRUPO
if ($action === 'get_chat_messages') {
    $groupUuid = $request_data['group_uuid'];
    $offset = $request_data['offset'] ?? 0;
    $limit = 50;

    if (empty($userId)) {
        send_json_response(false, 'Faltan datos para cargar el chat.');
    }
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid");
        $stmt_check->execute(['user_id' => $userId, 'group_uuid' => $groupUuid]);
        if ($stmt_check->rowCount() === 0) {
            send_json_response(false, 'No perteneces a este grupo.');
        }

        $stmt_messages = $pdo->prepare(
            "SELECT
                gm.id as message_id, gm.user_id, u.username,
                gm.is_deleted, gm.message_text AS message, gm.sent_at AS timestamp,
                gm.image_url,
                replied_msg.message_text AS replied_message_text,
                replied_msg.image_url AS replied_image_url,
                replied_user.username AS replied_username
             FROM group_messages gm
             JOIN users u ON gm.user_id = u.id
             LEFT JOIN group_messages replied_msg ON gm.reply_to_message_id = replied_msg.id
             LEFT JOIN users replied_user ON replied_msg.user_id = replied_user.id
             WHERE gm.group_uuid = :group_uuid
             ORDER BY gm.sent_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt_messages->bindValue(':group_uuid', $groupUuid, PDO::PARAM_STR);
        $stmt_messages->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt_messages->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_messages->execute();
        $messages_raw = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
        $messages = [];

        foreach ($messages_raw as $msg) {
            $dt = new DateTime($msg['timestamp']);

            $message_item = [
                'message_id' => $msg['message_id'],
                'user_id' => $msg['user_id'],
                'username' => $msg['username'],
                'message' => $msg['is_deleted'] ? 'Mensaje eliminado' : $msg['message'],
                'timestamp' => $dt->format('c'),
                'is_deleted' => (bool)$msg['is_deleted'],
                'image_url' => $msg['image_url'],
                'reply_context' => null
            ];
            
            if (!$msg['is_deleted'] && !empty($msg['replied_username'])) {
                $message_item['reply_context'] = [
                    'username' => $msg['replied_username'],
                    'message_text' => $msg['replied_message_text'],
                    'image_url' => $msg['replied_image_url']
                ];
            }
            $messages[] = $message_item;
        }

       send_json_response(true, 'Mensajes obtenidos.', ['messages' => $messages]);

    } catch (PDOException $e) {
        error_log("API Error (get_chat_messages): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al cargar los mensajes.');
    }
}

// ACCIÓN PARA REPORTAR MENSAJES
if ($action === 'report_message') {
    if (empty($userId)) {
        send_json_response(false, 'Debes iniciar sesión para reportar un mensaje.');
    }

    $messageId = $request_data['message_id'];
    $reportImage = $request_data['report_image'] ?? false;

    try {
        $pdo->beginTransaction();
        $stmt_msg = $pdo->prepare("SELECT user_id, group_uuid, image_url FROM group_messages WHERE id = :message_id");
        $stmt_msg->execute(['message_id' => $messageId]);
        $message = $stmt_msg->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            $pdo->rollBack(); send_json_response(false, 'El mensaje que intentas reportar no existe.');
        }
        if ($message['user_id'] == $userId) {
            $pdo->rollBack(); send_json_response(false, 'No puedes reportar tus propios mensajes.');
        }
        
        if ($reportImage && empty($message['image_url'])) {
            $pdo->rollBack(); send_json_response(false, 'Este mensaje no contiene una imagen para reportar.');
        }

        $stmt_check = $pdo->prepare("SELECT id FROM message_reports WHERE message_id = :message_id AND reporter_user_id = :reporter_id");
        $stmt_check->execute(['message_id' => $messageId, 'reporter_id' => $userId]);
        if ($stmt_check->fetch()) {
            $pdo->rollBack(); send_json_response(false, 'Ya has reportado este mensaje anteriormente.');
        }

        $stmt_insert = $pdo->prepare(
            "INSERT INTO message_reports (message_id, group_uuid, reported_user_id, reporter_user_id, image_reported)
             VALUES (:message_id, :group_uuid, :reported_user_id, :reporter_user_id, :image_reported)"
        );
        $stmt_insert->execute([
            'message_id' => $messageId,
            'group_uuid' => $message['group_uuid'],
            'reported_user_id' => $message['user_id'],
            'reporter_user_id' => $userId,
            'image_reported' => $reportImage ? 1 : 0
        ]);
        $pdo->commit();
        send_json_response(true, 'Mensaje reportado correctamente.');

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (report_message): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al procesar el reporte.');
    }
}


// ACCIÓN PARA ELIMINAR MENSAJE
if ($action === 'delete_message') {
    if (empty($userId)) {
        send_json_response(false, 'Debes iniciar sesión para eliminar un mensaje.');
    }
    $messageId = $request_data['message_id'];
    try {
        $stmt = $pdo->prepare("SELECT user_id, sent_at FROM group_messages WHERE id = :message_id AND is_deleted = 0");
        $stmt->execute(['message_id' => $messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            send_json_response(false, 'El mensaje no existe o ya fue eliminado.');
        }
        if ($message['user_id'] != $userId) {
            send_json_response(false, 'No tienes permiso para eliminar este mensaje.');
        }
        $sent_time = new DateTime($message['sent_at']);
        $current_time = new DateTime();
        $interval = $current_time->getTimestamp() - $sent_time->getTimestamp();
        if ($interval > 600) { // 10 minutos * 60 segundos
            send_json_response(false, 'Ya no puedes eliminar este mensaje.');
        }

        $stmt_update = $pdo->prepare(
            "UPDATE group_messages
             SET is_deleted = 1, deleted_at = NOW(), message_text = 'Mensaje eliminado'
             WHERE id = :message_id"
        );
        $stmt_update->execute(['message_id' => $messageId]);
        send_json_response(true, 'Mensaje eliminado correctamente.');
    } catch (PDOException $e) {
        error_log("API Error (delete_message): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al eliminar el mensaje.');
    }
}

// ACCIÓN: OBTENER TODOS LOS MUNICIPIOS
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
        send_json_response(true, 'Municipios obtenidos.', ['municipalities' => $municipalities]);
    } catch (PDOException $e) {
        error_log("API Error (get_municipalities): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al obtener los municipios.');
    }
}

// ACCIÓN: OBTENER GRUPOS DE MUNICIPIOS
if ($action === 'get_municipality_groups') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
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

        send_json_response(true, 'Grupos de municipios obtenidos.', ['groups' => $groups]);
    } catch (PDOException $e) {
        error_log("API Error (get_municipality_groups): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al obtener los grupos.');
    }
}

// ACCIÓN: OBTENER GRUPOS DE UNIVERSIDADES (CON FILTRO)
if ($action === 'get_university_groups') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
    }

    $municipalityId = $request_data['municipality_id'];

    try {
        $sql = "SELECT u.uuid, u.group_title, u.privacy, u.members,
                       m.group_title as municipality_name,
                       (gm.user_id IS NOT NULL) AS is_member
                FROM group_university u
                JOIN group_municipality m ON u.municipality_id = m.id
                LEFT JOIN group_members gm ON u.uuid = gm.group_uuid AND gm.user_id = :user_id AND gm.group_type = 'university'";
        $params = ['user_id' => $userId];
        if ($municipalityId !== 'all') {
            $sql .= " WHERE u.municipality_id = :municipality_id";
            $params['municipality_id'] = (int)$municipalityId;
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

        send_json_response(true, 'Grupos de universidades obtenidos.', ['groups' => $groups]);
    } catch (PDOException $e) {
        error_log("API Error (get_university_groups): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al obtener las universidades.');
    }
}

// ACCIÓN: UNIRSE O ABANDONAR UN GRUPO
if ($action === 'toggle_group_membership') {
    if (empty($userId)) {
        send_json_response(false, 'Debes iniciar sesión para unirte a un grupo.');
    }
    $groupUuid = $request_data['group_uuid'];
    $groupType = $request_data['group_type'];
    $groupTable = 'group_' . $groupType;
    try {
        $pdo->beginTransaction();
        $stmt_group = $pdo->prepare("SELECT id, members, privacy FROM `$groupTable` WHERE uuid = :uuid");
        $stmt_group->execute(['uuid' => $groupUuid]);
        $group = $stmt_group->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            $pdo->rollBack(); send_json_response(false, 'El grupo no existe.');
        }
        $stmt_member = $pdo->prepare("SELECT id FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid AND group_type = :group_type");
        $stmt_member->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
        if ($stmt_member->fetch()) {
            $stmt_delete = $pdo->prepare("DELETE FROM group_members WHERE user_id = :user_id AND group_uuid = :group_uuid AND group_type = :group_type");
            $stmt_delete->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
            $newMemberCount = max(0, $group['members'] - 1);
            $stmt_update = $pdo->prepare("UPDATE `$groupTable` SET members = :members WHERE id = :id");
            $stmt_update->execute(['members' => $newMemberCount, 'id' => $group['id']]);
            $pdo->commit();
            send_json_response(true, 'Has abandonado el grupo.', ['action' => 'left', 'newMemberCount' => $newMemberCount]);
        } else {
            if ($group['privacy'] === 'public') {
                $stmt_insert = $pdo->prepare("INSERT INTO group_members (user_id, group_uuid, group_type) VALUES (:user_id, :group_uuid, :group_type)");
                $stmt_insert->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
                $newMemberCount = $group['members'] + 1;
                $stmt_update = $pdo->prepare("UPDATE `$groupTable` SET members = :members WHERE id = :id");
                $stmt_update->execute(['members' => $newMemberCount, 'id' => $group['id']]);
                $pdo->commit();
                send_json_response(true, 'Te has unido al grupo.', ['action' => 'joined', 'newMemberCount' => $newMemberCount]);
            } else {
                $pdo->rollBack();
                send_json_response(true, 'Este grupo es privado y requiere un código de acceso.', ['action' => 'private']);
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (toggle_group_membership): " . $e->getMessage());
        send_json_response(false, 'Error del servidor.');
    }
}

// ACCIÓN: UNIRSE A GRUPO PRIVADO CON CÓDIGO
if ($action === 'join_private_group') {
    if (empty($userId)) {
        send_json_response(false, 'Debes iniciar sesión para unirte.');
    }
    $groupUuid = $request_data['group_uuid'];
    $accessCode = $request_data['access_code'];
    $groupType = $request_data['group_type'];
    $groupTable = 'group_' . $groupType;
    try {
        $pdo->beginTransaction();
        $stmt_group = $pdo->prepare("SELECT id, members, access_code FROM `$groupTable` WHERE uuid = :uuid AND privacy = 'private'");
        $stmt_group->execute(['uuid' => $groupUuid]);
        $group = $stmt_group->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            $pdo->rollBack(); send_json_response(false, 'Este grupo no es privado o no existe.');
        }
        if ($group['access_code'] !== $accessCode) {
            $pdo->rollBack(); send_json_response(false, 'El código de acceso es incorrecto.');
        }

        $stmt_insert = $pdo->prepare("INSERT INTO group_members (user_id, group_uuid, group_type) VALUES (:user_id, :group_uuid, :group_type) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt_insert->execute(['user_id' => $userId, 'group_uuid' => $groupUuid, 'group_type' => $groupType]);
        $newMemberCount = ($stmt_insert->rowCount() > 0) ? $group['members'] + 1 : $group['members'];
        if ($stmt_insert->rowCount() > 0) {
            $stmt_update = $pdo->prepare("UPDATE `$groupTable` SET members = :members WHERE id = :id");
            $stmt_update->execute(['members' => $newMemberCount, 'id' => $group['id']]);
        }
        $pdo->commit();
        send_json_response(true, 'Te has unido al grupo con éxito.', ['action' => 'joined', 'newMemberCount' => $newMemberCount]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (join_private_group): " . $e->getMessage());
        send_json_response(false, 'Error del servidor.');
    }
}

// ACCIÓN: ACTUALIZAR UNA PREFERENCIA DE USUARIO
if ($action === 'update_preference') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
    }
    $field = $request_data['field'];
    $value = $request_data['value'];
    $allowed_fields = ['language', 'usage_type', 'open_links_in_new_tab', 'show_sensitive_content', 'theme', 'shortcuts_need_modifier', 'high_contrast_colors'];
    if (!in_array($field, $allowed_fields)) {
        send_json_response(false, 'Datos inválidos para la actualización.');
    }
    if (in_array($field, ['open_links_in_new_tab', 'show_sensitive_content', 'shortcuts_need_modifier', 'high_contrast_colors'])) {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
    if ($field === 'language' && !in_array($value, ['es-MX', 'en-US'])) {
        send_json_response(false, 'Idioma no soportado.');
    }
    try {
        $stmt = $pdo->prepare("UPDATE user_preferences SET `$field` = :value WHERE user_id = :user_id");
        $stmt->execute(['value' => $value, 'user_id' => $userId]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['user_preferences'][$field] = $value;
            send_json_response(true, 'Preferencia actualizada con éxito.');
        } else {
            send_json_response(true, 'No se realizaron cambios.');
        }
    } catch (PDOException $e) {
        error_log("API Error (update_preference): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al actualizar la preferencia.');
    }
}

// ACCIÓN: OBTENER FECHAS DE LA CUENTA
if ($action === 'get_account_dates') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
    }
    try {
        setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'Spanish');
        $stmt_creation = $pdo->prepare("SELECT created_at FROM users WHERE id = :user_id");
        $stmt_creation->execute(['user_id' => $userId]);
        $creation_date = $stmt_creation->fetchColumn();

        $stmt_pass_update = $pdo->prepare("SELECT updated_at FROM user_update_history WHERE user_id = :user_id AND field_changed = 'password' ORDER BY updated_at DESC LIMIT 1");
        $stmt_pass_update->execute(['user_id' => $userId]);
        $last_password_update = $stmt_pass_update->fetchColumn();

        $data = [
            'creation_date' => $creation_date ? strftime('%e de %B de %Y a las %I:%M %p', strtotime($creation_date)) : 'No disponible',
            'last_password_update' => $last_password_update ? 'Última actualización: ' . strftime('%e de %B de %Y a las %I:%M %p', strtotime($last_password_update)) : 'Aún no has actualizado tu contraseña.'
        ];
        send_json_response(true, 'Fechas obtenidas.', $data);
    } catch (PDOException $e) {
        error_log("API Error (get_account_dates): " . $e->getMessage());
        send_json_response(false, 'Error del servidor.');
    }
}

// ACCIÓN: ACTUALIZAR PERFIL
if ($action === 'update_profile') {
    $field = $request_data['field'];
    $value = $request_data['value'];
    if (empty($userId)) {
        send_json_response(false, 'Faltan datos para realizar la actualización.');
    }
    try {
        $stmt_old = $pdo->prepare("SELECT `{$field}` FROM users WHERE id = :user_id");
        $stmt_old->execute(['user_id' => $userId]);
        if ($stmt_old->fetchColumn() === $value) {
            send_json_response(true, 'No se realizaron cambios.', ['newValue' => $value]);
        }
        $stmt_time = $pdo->prepare("SELECT updated_at FROM user_update_history WHERE user_id = :user_id AND field_changed = :field ORDER BY updated_at DESC LIMIT 1");
        $stmt_time->execute(['user_id' => $userId, 'field' => $field]);
        if ($last_update = $stmt_time->fetch(PDO::FETCH_ASSOC)) {
            $interval = (new DateTime())->getTimestamp() - (new DateTime($last_update['updated_at']))->getTimestamp();
            if ($interval < (30 * 24 * 60 * 60)) {
                send_json_response(false, "Debes esperar " . ceil(((30 * 24 * 60 * 60) - $interval) / (24 * 60 * 60)) . " día(s) para volver a cambiarlo.");
            }
        }
        if ($field === 'email' && (!filter_var($value, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._-]+@(gmail\.com|outlook\.com)$/i', $value))) {
            send_json_response(false, 'Solo se permiten correos válidos de @gmail.com o @outlook.com.');
        } elseif ($field === 'username' && !preg_match('/^[a-zA-Z0-9_]{4,25}$/', $value)) {
            send_json_response(false, 'El nombre de usuario debe tener entre 4 y 25 caracteres y solo puede contener letras, números y guiones bajos.');
        }
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE `{$field}` = :value AND id != :user_id");
        $stmt_check->execute(['value' => $value, 'user_id' => $userId]);
        if ($stmt_check->fetch()) {
            send_json_response(false, 'Ese ' . ($field === 'username' ? 'nombre de usuario' : 'correo') . ' ya está en uso.');
        }
        $pdo->beginTransaction();
        $stmt_update = $pdo->prepare("UPDATE users SET `{$field}` = :value WHERE id = :user_id");
        $stmt_update->execute(['value' => $value, 'user_id' => $userId]);
        $stmt_history = $pdo->prepare("INSERT INTO user_update_history (user_id, field_changed, old_value, new_value) VALUES (:user_id, :field, :old, :new)");
        $stmt_history->execute(['user_id' => $userId, 'field' => $field, 'old' => $stmt_old->fetchColumn(), 'new' => $value]);
        $pdo->commit();
        $_SESSION[$field] = $value;
        send_json_response(true, '¡Tu ' . ($field === 'username' ? 'nombre de usuario' : 'correo') . ' se ha actualizado!', ['newValue' => $value]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (update_profile): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al actualizar los datos.');
    }
}

// ACCIÓN: ACTUALIZAR CONTRASEÑA
if ($action === 'update_password') {
    $current_password = $request_data['current_password'];
    $new_password = $request_data['new_password'] ?? '';
    $confirm_password = $request_data['confirm_password'] ?? '';
    try {
        $stmt_user = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt_user->execute(['user_id' => $userId]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($current_password, $user['password'])) {
            send_json_response(false, 'La contraseña actual es incorrecta.');
        }
        if (empty($new_password) && empty($confirm_password)) {
            send_json_response(true, 'Contraseña confirmada.');
        }
        if (password_verify($new_password, $user['password'])) {
            send_json_response(false, 'La nueva contraseña no puede ser igual a la actual.');
        }
        if ($new_password !== $confirm_password) {
            send_json_response(false, 'Las nuevas contraseñas no coinciden.');
        }
        if (strlen($new_password) < 8) {
            send_json_response(false, 'La nueva contraseña debe tener al menos 8 caracteres.');
        }
        $pdo->beginTransaction();
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt_update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :user_id");
        $stmt_update->execute(['password' => $hashed_password, 'user_id' => $userId]);
        $stmt_history = $pdo->prepare("INSERT INTO user_update_history (user_id, field_changed) VALUES (:user_id, 'password')");
        $stmt_history->execute(['user_id' => $userId]);
        $pdo->commit();
        send_json_response(true, '¡Tu contraseña ha sido actualizada con éxito!');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("API Error (update_password): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al actualizar la contraseña.');
    }
}

// ACCIÓN: ELIMINAR CUENTA
if ($action === 'delete_account') {
    $password = $request_data['password'];
    if (empty($userId)) {
        send_json_response(false, 'Se requiere contraseña para eliminar la cuenta.');
    }
    try {
        $stmt_user = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt_user->execute(['user_id' => $userId]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            send_json_response(false, 'No se encontró el usuario.');
        }
        if (password_verify($password, $user['password'])) {
            $stmt_delete = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = :user_id");
            $stmt_delete->execute(['user_id' => $userId]);
            session_unset();
            session_destroy();
            send_json_response(true, 'Cuenta eliminada.', ['redirect_url' => '../']);
        } else {
            send_json_response(false, 'La contraseña es incorrecta.');
        }
    } catch (PDOException $e) {
        error_log("API Error (delete_account): " . $e->getMessage());
        send_json_response(false, 'Error del servidor al eliminar la cuenta.');
    }
}

if ($action === 'upload_image') {
    if (empty($userId)) {
        send_json_response(false, 'Usuario no autenticado.');
    }

    $groupUuid = $request_data['group_uuid'];

    if (!isset($_FILES['image'])) {
        send_json_response(false, 'No se recibió ninguna imagen.');
    }

    $image = $_FILES['image'];

    if ($image['error'] !== UPLOAD_ERR_OK) {
        send_json_response(false, 'Error al subir la imagen.');
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($image['type'], $allowed_types)) {
        send_json_response(false, 'Tipo de archivo no permitido.');
    }

    if ($image['size'] > 5 * 1024 * 1024) { // 5 MB
        send_json_response(false, 'La imagen es demasiado grande.');
    }

    $upload_dir = __DIR__ . '/../uploads/chat_images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = uniqid() . '-' . basename($image['name']);
    $destination = $upload_dir . $filename;

    if (move_uploaded_file($image['tmp_name'], $destination)) {
        $base_url = str_replace('api/api_handler.php', '', $_SERVER['PHP_SELF']);
        $image_url = $base_url . 'uploads/chat_images/' . $filename;
        send_json_response(true, 'Imagen subida correctamente.', ['image_url' => $image_url]);
    } else {
        send_json_response(false, 'Error al guardar la imagen.');
    }
}
?>