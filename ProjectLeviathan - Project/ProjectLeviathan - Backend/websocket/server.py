import asyncio
import websockets
import json
from config import get_db_connection
from datetime import datetime

# --- INICIO DE LA MODIFICACIÓN: Cambiamos cómo se almacenan los clientes ---
# Ahora es un diccionario de diccionarios: { group_uuid: { websocket: {user_info} } }
connected_clients = {}

async def register_client(websocket, group_uuid, user_id, username):
    """Registra un nuevo cliente en el grupo correspondiente."""
    if group_uuid not in connected_clients:
        # El grupo ahora contendrá un diccionario, no un set
        connected_clients[group_uuid] = {}
    
    # La clave es el objeto websocket, el valor son los datos del usuario
    connected_clients[group_uuid][websocket] = {
        "user_id": user_id,
        "username": username
    }
    print(f"Cliente {username} (ID: {user_id}) conectado al grupo {group_uuid}")

async def unregister_client(websocket, group_uuid, username):
    """Elimina un cliente cuando se desconecta."""
    if group_uuid in connected_clients and websocket in connected_clients[group_uuid]:
        del connected_clients[group_uuid][websocket]
        print(f"Cliente {username} desconectado del grupo {group_uuid}")
        if not connected_clients[group_uuid]:
            del connected_clients[group_uuid]

async def broadcast_message(group_uuid, message_data):
    """Envía un mensaje a todos los clientes de un grupo."""
    if group_uuid in connected_clients:
        message_json = json.dumps(message_data)
        # Iteramos sobre una copia de las claves (los sockets)
        for client_socket in list(connected_clients[group_uuid].keys()):
            try:
                await client_socket.send(message_json)
            except websockets.exceptions.ConnectionClosed:
                pass
# --- FIN DE LA MODIFICACIÓN ---

async def save_message_to_db(group_uuid, user_id, message_text):
    """Guarda un mensaje en la base de datos."""
    connection = get_db_connection()
    if not connection:
        return False
    
    cursor = connection.cursor()
    try:
        query = "INSERT INTO group_messages (group_uuid, user_id, message_text) VALUES (%s, %s, %s)"
        cursor.execute(query, (group_uuid, user_id, message_text))
        connection.commit()
        return True
    except Exception as e:
        print(f"Error al guardar mensaje en la BD: {e}")
        connection.rollback()
        return False
    finally:
        cursor.close()
        connection.close()

async def chat_handler(websocket):
    """Maneja las conexiones y mensajes de cada cliente."""
    group_uuid = None
    user_id = None
    username = None
    
    try:
        init_message = await websocket.recv()
        init_data = json.loads(init_message)
        
        if init_data.get('type') == 'auth':
            group_uuid = init_data.get('group_uuid')
            user_id = init_data.get('user_id')
            username = init_data.get('username')
            
            if not all([group_uuid, user_id, username]):
                await websocket.close(reason="Faltan datos de autenticación.")
                return

            await register_client(websocket, group_uuid, user_id, username)

            async for message in websocket:
                data = json.loads(message)
                
                if data.get('type') == 'chat_message':
                    message_text = data.get('message', '').strip()
                    if message_text:
                        if await save_message_to_db(group_uuid, user_id, message_text):
                            message_data = {
                                "type": "new_message",
                                "user_id": user_id,
                                "username": username,
                                "message": message_text,
                                "timestamp": datetime.now().isoformat()
                            }
                            await broadcast_message(group_uuid, message_data)
        else:
            await websocket.close(reason="Se requiere mensaje de autenticación inicial.")

    except websockets.exceptions.ConnectionClosed as e:
        print(f"Conexión cerrada: {e}")
    except Exception as e:
        print(f"Ocurrió un error inesperado: {e}")
    finally:
        if all([group_uuid, username]):
            # --- INICIO DE LA MODIFICACIÓN: unregister ya no necesita user_id ---
            await unregister_client(websocket, group_uuid, username)
            # --- FIN DE LA MODIFICACIÓN ---

async def main():
    """Función principal para iniciar el servidor."""
    async with websockets.serve(chat_handler, "localhost", 8765):
        print("Servidor de WebSocket iniciado en ws://localhost:8765")
        await asyncio.Future()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("Servidor detenido.")