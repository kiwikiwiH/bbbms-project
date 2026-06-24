# BBBMS — Blockchain-Based Blood Bank Management System

Final-year project: a blood bank management system with React, Flask, MySQL, and Ethereum smart contracts.

## Project structure

```
bbbms-project/
├── backend/      Flask REST API + MySQL + Web3.py
├── blockchain/   Hardhat + Solidity (BloodBank.sol)
├── frontend/     React.js UI
└── README.md
```

## Prerequisites

- Node.js (LTS)
- Python 3.10+
- MySQL 8
- Ganache (local Ethereum)

## 1. Database

```bash
mysql -u root -p < backend/database/schema.sql
```

## 2. Backend

```bash
cd backend
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
python app.py
```

Backend runs at **http://127.0.0.1:5000**

## 3. Blockchain

Start Ganache on `http://127.0.0.1:7545`, then:

```bash
cd blockchain
npm install
npm run compile
npm run deploy
```

Copy the printed contract address and private key into `backend/.env`:

```
BLOOD_BANK_CONTRACT_ADDRESS=0x...
BLOCKCHAIN_PRIVATE_KEY=0x...
BLOCKCHAIN_RPC_URL=http://127.0.0.1:7545
```

Restart the Flask backend after updating `.env`.

## 4. Frontend

```bash
cd frontend
npm install
npm start
```

Frontend runs at **http://localhost:3000** and proxies API calls to the backend.

## Demo flow

1. Register users (admin/lab, hospital, donor)
2. Login as **lab** or **admin** → add blood units in Inventory
3. Login as **hospital** → submit a blood request
4. Use **Trace Unit** with a unit ID to view DB + blockchain records
5. Dashboard shows database and blockchain connection status

## API endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | System health |
| POST | `/api/auth/register` | Register |
| POST | `/api/auth/login` | Login |
| GET/POST | `/api/inventory` | Blood inventory |
| GET | `/api/inventory/:id/trace` | Trace unit |
| GET/POST | `/api/requests` | Hospital requests |
