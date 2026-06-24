from flask import Flask
from flask_cors import CORS
from flask_jwt_extended import JWTManager

from config import Config
from routes.auth import auth_bp
from routes.health import health_bp
from routes.inventory import inventory_bp
from routes.requests import requests_bp


def create_app():
    app = Flask(__name__)
    app.config["SECRET_KEY"] = Config.SECRET_KEY
    app.config["JWT_SECRET_KEY"] = Config.JWT_SECRET_KEY

    CORS(app, resources={r"/api/*": {"origins": "*"}})
    JWTManager(app)

    app.register_blueprint(health_bp)
    app.register_blueprint(auth_bp)
    app.register_blueprint(inventory_bp)
    app.register_blueprint(requests_bp)

    @app.get("/")
    def root():
        return {
            "name": "BBBMS API",
            "message": "Blockchain-Based Blood Bank Management System backend",
            "endpoints": {
                "health": "/api/health",
                "register": "POST /api/auth/register",
                "login": "POST /api/auth/login",
                "inventory": "GET/POST /api/inventory",
                "requests": "GET/POST /api/requests",
            },
        }

    return app


app = create_app()


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
