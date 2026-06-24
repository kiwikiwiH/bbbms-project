import mysql.connector
from mysql.connector import Error

from config import Config


def get_connection():
    return mysql.connector.connect(
        host=Config.MYSQL_HOST,
        port=Config.MYSQL_PORT,
        user=Config.MYSQL_USER,
        password=Config.MYSQL_PASSWORD,
        database=Config.MYSQL_DATABASE,
    )


def ping_database():
    connection = get_connection()
    try:
        connection.ping(reconnect=True, attempts=1, delay=0)
        cursor = connection.cursor()
        cursor.execute("SELECT 1")
        cursor.fetchone()
        cursor.close()
        return True, "connected"
    except Error as exc:
        return False, str(exc)
    finally:
        connection.close()
