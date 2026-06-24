from datetime import timedelta

from flask import Blueprint, jsonify, request
from flask_jwt_extended import create_access_token
from werkzeug.security import check_password_hash, generate_password_hash

from config import Config
from database.db import get_connection

auth_bp = Blueprint("auth", __name__, url_prefix="/api/auth")


@auth_bp.post("/register")
def register():
    payload = request.get_json(silent=True) or {}
    name = payload.get("name", "").strip()
    email = payload.get("email", "").strip().lower()
    password = payload.get("password", "")
    role = payload.get("role", "donor")

    if not name or not email or not password:
        return jsonify({"error": "name, email, and password are required"}), 400

    if role not in {"admin", "donor", "hospital", "lab"}:
        return jsonify({"error": "invalid role"}), 400

    connection = get_connection()
    cursor = connection.cursor()
    try:
        cursor.execute(
            """
            INSERT INTO users (name, email, password_hash, role)
            VALUES (%s, %s, %s, %s)
            """,
            (name, email, generate_password_hash(password), role),
        )
        connection.commit()
        user_id = cursor.lastrowid
        return jsonify({"message": "user registered", "user_id": user_id}), 201
    except Exception as exc:
        connection.rollback()
        return jsonify({"error": str(exc)}), 400
    finally:
        cursor.close()
        connection.close()


@auth_bp.post("/login")
def login():
    payload = request.get_json(silent=True) or {}
    email = payload.get("email", "").strip().lower()
    password = payload.get("password", "")

    if not email or not password:
        return jsonify({"error": "email and password are required"}), 400

    connection = get_connection()
    cursor = connection.cursor(dictionary=True)
    try:
        cursor.execute(
            "SELECT user_id, name, email, password_hash, role FROM users WHERE email = %s",
            (email,),
        )
        user = cursor.fetchone()
        if not user or not check_password_hash(user["password_hash"], password):
            return jsonify({"error": "invalid credentials"}), 401

        token = create_access_token(
            identity=str(user["user_id"]),
            additional_claims={"role": user["role"], "name": user["name"]},
            expires_delta=timedelta(hours=8),
        )
        return jsonify(
            {
                "access_token": token,
                "user": {
                    "user_id": user["user_id"],
                    "name": user["name"],
                    "email": user["email"],
                    "role": user["role"],
                },
            }
        )
    finally:
        cursor.close()
        connection.close()
