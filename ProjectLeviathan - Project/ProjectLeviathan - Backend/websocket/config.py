import mysql.connector
from mysql.connector import Error

# --- DEFINE TUS CREDENCIALES AQUÍ ---
# Asegúrate de que coincidan con tu db_config.php
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'project_db'
}

def get_db_connection():
    """Crea y devuelve una conexión a la base de datos."""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Error al conectar a MySQL: {e}")
        return None