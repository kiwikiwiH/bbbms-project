from flask import Blueprint, jsonify, request
from flask_jwt_extended import get_jwt, jwt_required

from database.db import get_connection
from services.blockchain_service import BlockchainService
from utils.serializers import serialize_row, serialize_rows

inventory_bp = Blueprint("inventory", __name__, url_prefix="/api/inventory")
blockchain_service = BlockchainService()


@inventory_bp.get("")
@jwt_required()
def list_inventory():
    connection = get_connection()
    cursor = connection.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            SELECT inventory_id, unit_id, blood_group, collection_date,
                   expiry_date, status, facility_name, created_at
            FROM blood_inventory
            ORDER BY created_at DESC
            """
        )
        return jsonify({"items": serialize_rows(cursor.fetchall())})
    finally:
        cursor.close()
        connection.close()


@inventory_bp.post("")
@jwt_required()
def add_inventory():
    claims = get_jwt()
    if claims.get("role") not in {"admin", "lab"}:
        return jsonify({"error": "only admin or lab can add inventory"}), 403

    payload = request.get_json(silent=True) or {}
    unit_id = payload.get("unit_id", "").strip()
    blood_group = payload.get("blood_group", "").strip().upper()
    collection_date = payload.get("collection_date")
    expiry_date = payload.get("expiry_date")
    facility_name = payload.get("facility_name", "Main Blood Bank")

    if not unit_id or not blood_group or not collection_date or not expiry_date:
        return jsonify(
            {
                "error": "unit_id, blood_group, collection_date, and expiry_date are required"
            }
        ), 400

    connection = get_connection()
    cursor = connection.cursor()
    try:
        cursor.execute(
            """
            INSERT INTO blood_inventory
                (unit_id, blood_group, collection_date, expiry_date, facility_name, status)
            VALUES (%s, %s, %s, %s, %s, 'collected')
            """,
            (unit_id, blood_group, collection_date, expiry_date, facility_name),
        )
        connection.commit()

        blockchain_result = None
        try:
            blockchain_result = blockchain_service.record_donation(unit_id, blood_group)
            cursor.execute(
                """
                INSERT INTO blockchain_transactions (unit_id, action, transaction_hash, block_number)
                VALUES (%s, %s, %s, %s)
                """,
                (
                    unit_id,
                    "record_donation",
                    blockchain_result["transaction_hash"],
                    blockchain_result["block_number"],
                ),
            )
            connection.commit()
        except RuntimeError as exc:
            blockchain_result = {"warning": str(exc)}

        return jsonify(
            {
                "message": "blood unit recorded",
                "unit_id": unit_id,
                "blockchain": blockchain_result,
            }
        ), 201
    except Exception as exc:
        connection.rollback()
        return jsonify({"error": str(exc)}), 400
    finally:
        cursor.close()
        connection.close()


@inventory_bp.get("/<unit_id>/trace")
@jwt_required()
def trace_unit(unit_id):
    connection = get_connection()
    cursor = connection.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            SELECT inventory_id, unit_id, blood_group, collection_date,
                   expiry_date, status, facility_name, created_at
            FROM blood_inventory
            WHERE unit_id = %s
            """,
            (unit_id,),
        )
        inventory_item = cursor.fetchone()
        if not inventory_item:
            return jsonify({"error": "unit not found"}), 404

        cursor.execute(
            """
            SELECT action, transaction_hash, block_number, recorded_at
            FROM blockchain_transactions
            WHERE unit_id = %s
            ORDER BY recorded_at ASC
            """,
            (unit_id,),
        )
        chain_events = cursor.fetchall()

        on_chain = None
        try:
            on_chain = blockchain_service.get_blood_unit(unit_id)
        except RuntimeError:
            on_chain = None

        return jsonify(
            {
                "inventory": serialize_row(inventory_item),
                "blockchain_transactions": serialize_rows(chain_events),
                "on_chain": on_chain,
            }
        )
    finally:
        cursor.close()
        connection.close()
