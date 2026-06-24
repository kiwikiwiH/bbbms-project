# BBBMS Backend (Flask)

REST API for the Blockchain-Based Blood Bank Management System.

## Setup

```bash
cd backend
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
```

Create the database:

```bash
mysql -u root -p < database/schema.sql
```

Run the server:

```bash
python app.py
```

API base URL: `http://127.0.0.1:5000`

## Main endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Database + blockchain status |
| POST | `/api/auth/register` | Register user |
| POST | `/api/auth/login` | Login and get JWT token |
| GET | `/api/inventory` | List blood units |
| POST | `/api/inventory` | Add blood unit (admin/lab) |
| GET | `/api/inventory/<unit_id>/trace` | Trace a blood unit |
| GET | `/api/requests` | List hospital requests |
| POST | `/api/requests` | Create request (hospital) |

## Blockchain

1. Start Ganache on `http://127.0.0.1:7545`
2. Deploy `blockchain/contracts/BloodBank.sol`
3. Copy contract address and a Ganache account private key into `.env`
