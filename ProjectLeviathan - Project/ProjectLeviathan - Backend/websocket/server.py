import asyncio
import websockets
import json
from config import get_db_connection
from datetime import datetime, timedelta, timezone
from collections import deque

# Parámetros del sistema Anti-Spam
SPAM_MESSAGE_LIMIT = 5
SPAM_TIME_WINDOW_SECONDS = 5
SPAM_MUTE_DURATION_SECONDS = 60

# --- INICIO DE LA MODIFICACIÓN: Límite de caracteres para mensajes ---
MAX_MESSAGE_LENGTH = 500
# --- FIN DE LA MODIFICACIÓN ---

user_message_timestamps = {}
muted_users = {}

async def log_spam_incident(user_id, group_uuid, message_rate, message_text):
    connection = get_db_connection()
    if not connection:
        return

    cursor = connection.cursor()
    try:
        blocked_until = datetime.now() + timedelta(seconds=SPAM_MUTE_DURATION_SECONDS)
        query = """
            INSERT INTO chat_spam_logs
            (user_id, group_uuid, last_message_content, message_rate, blocked_until)
            VALUES (%s, %s, %s, %s, %s)
        """
        cursor.execute(query, (user_id, group_uuid, message_text, message_rate, blocked_until.strftime('%Y-%m-%d %H:%M:%S')))
        connection.commit()
    except Exception as e:
        print(f"Error al registrar incidente de spam: {e}")
        connection.rollback()
    finally:
        cursor.close()
        connection.close()

connected_clients = {}

async def validate_token(token):
    connection = get_db_connection()
    if not connection:
        return None

    cursor = connection.cursor(dictionary=True)
    try:
        query = """
            SELECT t.user_id, u.username
            FROM websocket_auth_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = %s AND t.expires_at > NOW()
        """
        cursor.execute(query, (token,))
        result = cursor.fetchone()

        if result:
            delete_query = "DELETE FROM websocket_auth_tokens WHERE token = %s"
            cursor.execute(delete_query, (token,))
            connection.commit()
            return result
        else:
            return None
    except Exception as e:
        print(f"Error al validar token: {e}")
        connection.rollback()
        return None
    finally:
        cursor.close()
        connection.close()

async def broadcast_message(group_uuid, message_data, exclude_socket=None):
    if group_uuid in connected_clients:
        message_json = json.dumps(message_data)
        for client_socket in list(connected_clients[group_uuid].keys()):
            if client_socket != exclude_socket:
                try:
                    await client_socket.send(message_json)
                except websockets.exceptions.ConnectionClosed:
                    pass

async def broadcast_user_status(group_uuid):
    if group_uuid in connected_clients:
        online_users = [client_data["user_id"] for client_data in connected_clients[group_uuid].values()]
        status_message = {
            "type": "user_status_update",
            "online_users": online_users
        }
        await broadcast_message(group_uuid, status_message)


async def register_client(websocket, group_uuid, user_id, username):
    if group_uuid not in connected_clients:
        connected_clients[group_uuid] = {}

    connected_clients[group_uuid][websocket] = {
        "user_id": user_id,
        "username": username
    }
    print(f"Cliente {username} (ID: {user_id}) conectado al grupo {group_uuid}")

    user_count = len(connected_clients[group_uuid])
    await broadcast_message(group_uuid, {"type": "user_count_update", "count": user_count})
    await broadcast_user_status(group_uuid)

async def unregister_client(websocket, group_uuid, username):
    if group_uuid in connected_clients and websocket in connected_clients[group_uuid]:
        del connected_clients[group_uuid][websocket]
        print(f"Cliente {username} desconectado del grupo {group_uuid}")

        user_count = len(connected_clients[group_uuid])
        await broadcast_message(group_uuid, {"type": "user_count_update", "count": user_count})
        await broadcast_user_status(group_uuid)

        if not connected_clients[group_uuid]:
            del connected_clients[group_uuid]

async def broadcast_message(group_uuid, message_data):
    if group_uuid in connected_clients:
        message_json = json.dumps(message_data)
        for client_socket in list(connected_clients[group_uuid].keys()):
            try:
                await client_socket.send(message_json)
            except websockets.exceptions.ConnectionClosed:
                pass

async def save_message_to_db(group_uuid, user_id, message_text, reply_to_id=None):
    connection = get_db_connection()
    if not connection:
        return None

    cursor = connection.cursor()
    try:
        query = "INSERT INTO group_messages (group_uuid, user_id, message_text, reply_to_message_id) VALUES (%s, %s, %s, %s)"
        cursor.execute(query, (group_uuid, user_id, message_text, reply_to_id))
        new_message_id = cursor.lastrowid
        connection.commit()
        return new_message_id
    except Exception as e:
        print(f"Error al guardar mensaje en la BD: {e}")
        connection.rollback()
        return None
    finally:
        cursor.close()
        connection.close()

async def get_reply_context(reply_to_id):
    if not reply_to_id:
        return None

    connection = get_db_connection()
    if not connection:
        return None

    cursor = connection.cursor(dictionary=True)
    try:
        query = """
            SELECT u.username, gm.message_text
            FROM group_messages gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.id = %s
        """
        cursor.execute(query, (reply_to_id,))
        result = cursor.fetchone()
        return result
    except Exception as e:
        print(f"Error al obtener contexto de respuesta: {e}")
        return None
    finally:
        cursor.close()
        connection.close()

async def chat_handler(websocket):
    group_uuid = None
    user_id = None
    username = None

    try:
        init_message = await websocket.recv()
        init_data = json.loads(init_message)

        if init_data.get('type') == 'auth':
            token = init_data.get('token')
            group_uuid = init_data.get('group_uuid')

            if not token or not group_uuid:
                await websocket.close(reason="Faltan datos de autenticación.")
                return

            user_data = await validate_token(token)

            if not user_data:
                await websocket.close(reason="Token de autenticación inválido o expirado.")
                return

            user_id = user_data['user_id']
            username = user_data['username']

            await register_client(websocket, group_uuid, user_id, username)
            user_message_timestamps[websocket] = deque()

            async for message in websocket:
                data = json.loads(message)
                now = datetime.now()

                if websocket in muted_users and now < muted_users[websocket]:
                    remaining_seconds = (muted_users[websocket] - now).total_seconds()
                    await websocket.send(json.dumps({
                        "type": "error",
                        "message": f"Estás silenciado. Podrás enviar mensajes de nuevo en {int(remaining_seconds)} segundos."
                    }))
                    continue

                timestamps = user_message_timestamps[websocket]
                while timestamps and (now - timestamps[0]).total_seconds() > SPAM_TIME_WINDOW_SECONDS:
                    timestamps.popleft()

                if len(timestamps) >= SPAM_MESSAGE_LIMIT:
                    muted_until = now + timedelta(seconds=SPAM_MUTE_DURATION_SECONDS)
                    muted_users[websocket] = muted_until

                    message_rate = len(timestamps) / SPAM_TIME_WINDOW_SECONDS
                    message_text = data.get('message', '')
                    await log_spam_incident(user_id, group_uuid, message_rate, message_text)

                    await websocket.send(json.dumps({
                        "type": "error",
                        "message": f"Has enviado mensajes demasiado rápido. Estás silenciado por {SPAM_MUTE_DURATION_SECONDS} segundos."
                    }))
                    continue

                timestamps.append(now)

                if data.get('type') == 'chat_message':
                    message_text = data.get('message', '').strip()
                    reply_to_id = data.get('reply_to_message_id')

                    if len(message_text) > MAX_MESSAGE_LENGTH:
                        await websocket.send(json.dumps({
                            "type": "error",
                            "message": f"El mensaje no puede exceder los {MAX_MESSAGE_LENGTH} caracteres."
                        }))
                        continue

                    if message_text:
                        new_message_id = await save_message_to_db(group_uuid, user_id, message_text, reply_to_id)
                        if new_message_id:
                            reply_context = await get_reply_context(reply_to_id)
                            message_data = {
                                "type": "new_message",
                                "message_id": new_message_id,
                                "user_id": user_id,
                                "username": username,
                                "message": message_text,
                                "timestamp": datetime.now(timezone.utc).isoformat(),
                                "reply_context": reply_context,
                                "is_deleted": False
                            }
                            await broadcast_message(group_uuid, message_data)
                elif data.get('type') == 'delete_message':
                    message_id_to_delete = data.get('message_id')
                    if message_id_to_delete:
                        connection = get_db_connection()
                        if connection:
                            cursor = connection.cursor(dictionary=True)
                            try:
                                # --- INICIO DE LA MODIFICACIÓN ---
                                # Se pide a la base de datos que calcule la edad del mensaje en segundos.
                                # Esto resuelve el problema de las zonas horarias.
                                query = "SELECT user_id, TIMESTAMPDIFF(SECOND, sent_at, NOW()) as age FROM group_messages WHERE id = %s AND is_deleted = 0"
                                # --- FIN DE LA MODIFICACIÓN ---
                                cursor.execute(query, (message_id_to_delete,))
                                message_record = cursor.fetchone()

                                if not message_record:
                                    continue

                                if message_record['user_id'] != user_id:
                                    continue
                                
                                # --- INICIO DE LA MODIFICACIÓN ---
                                # Se verifica la edad del mensaje calculada por la base de datos.
                                # El límite son 600 segundos (10 minutos).
                                if message_record['age'] > 600:
                                    continue
                                # --- FIN DE LA MODIFICACIÓN ---

                                update_query = "UPDATE group_messages SET is_deleted = 1, deleted_at = NOW(), message_text = 'Mensaje eliminado' WHERE id = %s"
                                cursor.execute(update_query, (message_id_to_delete,))
                                connection.commit()
                                
                                await broadcast_message(group_uuid, {
                                    "type": "message_deleted",
                                    "message_id": message_id_to_delete
                                })

                            except Exception as e:
                                print(f"Error al eliminar mensaje: {e}")
                                connection.rollback()
                            finally:
                                cursor.close()
                                connection.close()
        else:
            await websocket.close(reason="Se requiere mensaje de autenticación inicial.")

    except websockets.exceptions.ConnectionClosed as e:
        print(f"Conexión cerrada: {e}")
    except Exception as e:
        print(f"Ocurrió un error inesperado: {e}")
    finally:
        if websocket in user_message_timestamps:
            del user_message_timestamps[websocket]
        if websocket in muted_users:
            del muted_users[websocket]
        if all([group_uuid, username]):
            await unregister_client(websocket, group_uuid, username)

async def main():
    async with websockets.serve(chat_handler, "localhost", 8765):
        print("Servidor de WebSocket iniciado en ws://localhost:8765")
        await asyncio.Future()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("Servidor detenido.")