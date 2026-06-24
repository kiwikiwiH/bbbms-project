from flask import Blueprint, jsonify, request
from flask_jwt_extended import get_jwt, get_jwt_identity, jwt_required

from database.db import get_connection
from utils.serializers import serialize_rows

requests_bp = Blueprint("requests", __name__, url_prefix="/api/requests")


@requests_bp.get("")
@jwt_required()
def list_requests():
    connection = get_connection()
    cursor = connection.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            SELECT r.request_id, r.hospital_user_id, u.name AS hospital_name,
                   r.blood_group, r.quantity_requested, r.urgency,
                   r.status, r.notes, r.created_at
            FROM blood_requests r
            JOIN users u ON u.user_id = r.hospital_user_id
            ORDER BY r.created_at DESC
            """
        )
        return jsonify({"requests": serialize_rows(cursor.fetchall())})
    finally:
        cursor.close()
        connection.close()


@requests_bp.post("")
@jwt_required()
def create_request():
    claims = get_jwt()
    if claims.get("role") != "hospital":
        return jsonify({"error": "only hospital users can create requests"}), 403

    payload = request.get_json(silent=True) or {}
    blood_group = payload.get("blood_group", "").strip().upper()
    quantity_requested = int(payload.get("quantity_requested", 1))
    urgency = payload.get("urgency", "normal")
    notes = payload.get("notes", "")

    if not blood_group:
        return jsonify({"error": "blood_group is required"}), 400

    if urgency not in {"normal", "emergency"}:
        return jsonify({"error": "urgency must be normal or emergency"}), 400

    connection = get_connection()
    cursor = connection.cursor()
    try:
        cursor.execute(
            """
            INSERT INTO blood_requests
                (hospital_user_id, blood_group, quantity_requested, urgency, notes)
            VALUES (%s, %s, %s, %s, %s)
            """,
            (get_jwt_identity(), blood_group, quantity_requested, urgency, notes),
        )
        connection.commit()
        return jsonify({"message": "request created", "request_id": cursor.lastrowid}), 201
    except Exception as exc:
        connection.rollback()
        return jsonify({"error": str(exc)}), 400
    finally:
        cursor.close()
        connection.close()
