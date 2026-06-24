from flask import Blueprint, jsonify

from database.db import ping_database
from services.blockchain_service import BlockchainService

health_bp = Blueprint("health", __name__, url_prefix="/api")
blockchain_service = BlockchainService()


@health_bp.get("/health")
def health():
    db_ok, db_message = ping_database()
    chain_status = blockchain_service.status()

    return jsonify(
        {
            "status": "ok" if db_ok else "degraded",
            "database": {"connected": db_ok, "message": db_message},
            "blockchain": chain_status,
        }
    )
