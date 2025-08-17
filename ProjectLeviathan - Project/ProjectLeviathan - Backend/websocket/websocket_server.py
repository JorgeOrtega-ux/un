import asyncio
import websockets
import json

# Un diccionario para mantener un registro de los clientes conectados a cada grupo.
# La estructura será: { "group_uuid": {websocket1, websocket2, ...} }
connected_clients = {}

async def broadcast(group_uuid, message):
    """
    Envía un mensaje a todos los clientes conectados a un grupo específico.
    """
    if group_uuid in connected_clients:
        # Hacemos una copia para evitar problemas si el conjunto cambia durante la iteración.
        clients_to_notify = connected_clients[group_uuid].copy()
        for client in clients_to_notify:
            try:
                await client.send(message)
            except websockets.exceptions.ConnectionClosed:
                # Si un cliente se ha desconectado, lo eliminamos.
                connected_clients[group_uuid].remove(client)

async def handler(websocket, path):
    """
    Maneja las conexiones entrantes de los clientes.
    """
    # El path de la URL se usará para identificar el grupo. Ej: /chat/uuid-del-grupo
    group_uuid = path.strip('/').split('/')[-1]

    # Registrar el nuevo cliente en el grupo correspondiente.
    if group_uuid not in connected_clients:
        connected_clients[group_uuid] = set()
    connected_clients[group_uuid].add(websocket)
    print(f"Cliente conectado al grupo {group_uuid}. Clientes totales en el grupo: {len(connected_clients[group_uuid])}")

    try:
        # Escuchar mensajes entrantes del cliente.
        async for message in websocket:
            try:
                data = json.loads(message)
                # Asegurarse de que el mensaje tenga el formato esperado.
                if 'action' in data and data['action'] == 'send_message' and 'payload' in data:
                    # Aquí es donde guardarías el mensaje en la base de datos (lógica no incluida aquí).
                    # Por ahora, solo lo retransmitimos a los demás clientes del grupo.
                    print(f"Mensaje recibido del grupo {group_uuid}: {data['payload']}")

                    # Prepara el mensaje para ser enviado a otros clientes.
                    # Puedes enriquecerlo con información del usuario, timestamp, etc.
                    outgoing_message = json.dumps({
                        "type": "new_message",
                        "payload": data['payload']
                    })

                    # Retransmitir el mensaje a todos los clientes del mismo grupo.
                    await broadcast(group_uuid, outgoing_message)

            except json.JSONDecodeError:
                print(f"Error: Mensaje no es un JSON válido: {message}")
            except Exception as e:
                print(f"Error al procesar el mensaje: {e}")

    finally:
        # Cuando un cliente se desconecta, lo eliminamos del grupo.
        if group_uuid in connected_clients and websocket in connected_clients[group_uuid]:
            connected_clients[group_uuid].remove(websocket)
            print(f"Cliente desconectado del grupo {group_uuid}. Clientes restantes: {len(connected_clients[group_uuid])}")


async def main():
    # Inicia el servidor de WebSockets en localhost, puerto 8765.
    async with websockets.serve(handler, "localhost", 8765):
        print("Servidor de WebSockets iniciado en ws://localhost:8765")
        await asyncio.Future()  # Mantener el servidor corriendo indefinidamente.

if __name__ == "__main__":
    asyncio.run(main())