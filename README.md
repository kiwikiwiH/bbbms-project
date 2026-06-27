# Tarrlok — Blockchain-Based Blood Bank Management System

**Tarrlok** is a final-year project for **Ghana hospitals**: a blood bank management platform for HeFRA-licensed facilities. Hospitals register on the network, are verified by a central platform administrator, lab staff register and screen blood units, and partner hospitals exchange blood through requests. Critical lifecycle events are also anchored on a local Ethereum smart contract for **immutable audit traceability**.

**Authors:** Ofei-Palm Valentino Papa Ayitey & Asiedu Enoch Ofori Kwasi (BSc. Computer Engineering, K.N.U.S.T.)

---

## Overview

| Layer | Role |
|-------|------|
| **Laravel + MySQL** | Day-to-day operations — users, inventory, screening, partner requests |
| **Solidity + Hardhat** | Tamper-evident audit log — registration, screening, partner issue |
| **Trace page** | Full unit lifecycle in the database + on-chain transaction hashes |

MySQL is the **operational database**. The blockchain is an **audit trail** — events cannot be altered once mined.

---

## Tech stack

| Layer | Technology |
|--------|------------|
| Backend | **Laravel 13** (PHP 8.3+) |
| Frontend | **Blade** templates + plain CSS |
| Auth | Laravel Breeze (Blade) |
| Database | **MySQL** (recommended) or **SQLite** |
| Blockchain | **Hardhat** + `BloodBank.sol` + Laravel `BlockchainService` |
| Local chain | Hardhat node (`http://127.0.0.1:8545`) |

---

## Architecture

```
Browser (Blade UI)
       │
       ▼
Laravel (controllers + models)
       │
       ├──────────────────┐
       ▼                  ▼
   MySQL            BlockchainService
 (operations)              │
                           ▼
                    anchor-event.js
                           │
                           ▼
              BloodBank.sol (local Ethereum)
```

**On-chain events** (via `BloodBank.sol`):

| App action | Smart contract | Event |
|------------|----------------|-------|
| Lab registers unit | `registerUnit()` | `UnitRegistered` |
| Screening cleared/failed | `recordScreening()` | `UnitScreened` |
| Partner issue | `recordIssue()` | `UnitIssued` |

Transaction hashes are saved on `blood_units` and shown on **Trace Unit**.

---

## Project structure

```
bbbms-project/
├── blockchain/                    # Hardhat + Solidity
│   ├── contracts/BloodBank.sol
│   ├── scripts/deploy.js
│   ├── scripts/anchor-event.js
│   ├── deployments/local.json     # Created after deploy
│   └── README.md
├── tarrlok/                       # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/      # Admin, Hospital, Lab, Auth
│   │   ├── Models/                # User, Hospital, BloodUnit, BloodRequest
│   │   └── Services/BlockchainService.php
│   ├── config/tarrlok.php         # Ghana regions, blood groups, screening tests
│   ├── config/blockchain.php
│   ├── database/migrations/
│   ├── database/seeders/          # AdminSeeder, DemoSeeder
│   ├── public/assets/css/         # Tarrlok UI styles
│   └── resources/views/
│       ├── auth/                  # Login, register, forgot/reset password
│       ├── admin/                 # Platform admin
│       ├── hospital/              # Hospital portal
│       ├── lab/                   # Lab portal
│       └── shared/trace/          # Unit trace page
└── README.md
```

---

## Prerequisites

- **PHP 8.3+** — `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`
- **Composer**
- **Node.js 18+** — required for blockchain
- **MySQL 8** (recommended) or SQLite for local dev

---

## Quick start

### 1. Laravel app

```bash
cd tarrlok
composer install
copy .env.example .env
php artisan key:generate
```

**MySQL** (recommended) — in `.env`:

```env
APP_NAME=Tarrlok
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tarrlok
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database:

```sql
CREATE DATABASE tarrlok CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Database & seed data

```bash
php artisan migrate
php artisan db:seed
```

This creates:

- Platform admin (from `.env`)
- Demo hospitals **Korle Bu** and **Ridge** with lab accounts
- **5 cleared blood units** at Ridge Hospital (ready for partner requests)

For a completely fresh database:

```bash
php artisan migrate:fresh --seed
```

> `DemoSeeder` skips if Korle Bu already exists. Use `migrate:fresh --seed` to reset.

### 3. Run Laravel

```bash
php artisan serve
```

Open **http://127.0.0.1:8000**

> Run only **one** `php artisan serve` on port 8000.

### 4. Blockchain (optional but recommended for full demo)

**Terminal 1** — local chain (keep running):

```bash
cd blockchain
npm install
npm run compile
npm run node
```

**Terminal 2** — deploy contract (once per node restart):

```bash
cd blockchain
npm run deploy
```

**In `tarrlok/.env`:**

```env
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

If the chain is down, Tarrlok still works — anchoring is skipped and no tx hashes appear on trace.

See [`blockchain/README.md`](blockchain/README.md) for details.

---

## Demo accounts

| Role | Email | Password |
|------|--------|----------|
| Platform admin | `admin@tarrlok.gh` | `TarrlokAdmin2024!` |
| Korle Bu admin | `kwame.mensah@korlebu.gov.gh` | `KorleBu2024!` |
| Korle Bu lab | `ama.osei@korlebu.gov.gh` | `KorleBuLab2024!` |
| Ridge admin (supplier) | `efua.adjei@ridge.gov.gh` | `Ridge2024!` |
| Ridge lab | `kofi.boateng@ridge.gov.gh` | `RidgeLab2024!` |

Platform admin credentials are configurable in `.env`:

```env
TARRLOK_ADMIN_EMAIL=admin@tarrlok.gh
TARRLOK_ADMIN_PASSWORD=TarrlokAdmin2024!
TARRLOK_ADMIN_NAME="Tarrlok Platform Admin"
```

Sync after changes: `php artisan db:seed --class=AdminSeeder`

---

## Application URLs

### Public & auth

| URL | Purpose |
|-----|---------|
| `/login` | Sign in |
| `/register` | 3-step hospital registration wizard |
| `/register/pending` | Post-submission confirmation |
| `/forgot-password` | Request password reset link |
| `/profile` | Update name, email, password |

### Platform admin

| URL | Purpose |
|-----|---------|
| `/admin` | Overview & pending registrations |
| `/admin/registrations` | List / filter hospital registrations |
| `/admin/registrations/{hospital}` | Approve or reject a facility |

### Hospital portal

| URL | Purpose |
|-----|---------|
| `/hospital` | Dashboard |
| `/hospital/inventory` | Blood inventory (cleared units only) |
| `/hospital/requests` | Incoming / outgoing partner requests |
| `/hospital/requests/create` | Request blood from a partner |
| `/hospital/partners` | Browse approved partner hospitals |
| `/hospital/trace` | Trace a unit by ID |
| `/hospital/facility` | Facility profile |
| `/hospital/lab-staff` | Manage lab staff accounts |

### Lab portal

| URL | Purpose |
|-----|---------|
| `/lab` | Lab dashboard |
| `/lab/units` | Units at this hospital |
| `/lab/units/create` | Register a new unit |
| `/lab/units/{unit}/screening` | Lab screening report |
| `/lab/trace` | Trace a unit by ID |

---

## User roles

| Role | Access |
|------|--------|
| **admin** | Approve/reject hospital registrations |
| **hospital** | Inventory, partner requests, lab staff, trace |
| **lab** | Register units, complete screening, trace |
| **donor** | Not implemented — donations recorded by lab staff |

Login redirects: **admin** → `/admin`, **hospital** → `/hospital`, **lab** → `/lab`.

---

## Blood workflow

```
Lab registers unit       →  quarantine, screening: pending
Lab screening report     →  cleared → available  |  failed → discarded
Hospital inventory       →  only cleared + available units count as stock
Partner request (outgoing) → blood_requests: pending
Partner approve + issue  →  FIFO; units transfer to requesting hospital
Trace unit               →  timeline + blockchain tx hashes
```

**Unit statuses:** `quarantine` → `available` (after screening) → transferred to partner as `available`, or `discarded`.

**Screening tests:** HIV, Hep B, Hep C, Syphilis — all must be non-reactive to clear.

**Request statuses:** `pending` → `approved` → `fulfilled`, or `rejected`.

**Unit codes:** auto-generated as `UNIT-{hospitalId}-{sequence}` (e.g. `UNIT-002-00001`).

---

## Full demo script (viva / presentation)

Requires blockchain terminals + `php artisan serve` running.

1. **Ridge admin** — log in → **Blood Inventory** — confirm cleared units exist
2. **Korle Bu admin** — **Partner Exchange** → request O+ from Ridge Hospital
3. **Ridge admin** — **Blood Requests → Incoming** → Approve → **Issue unit**
4. **Korle Bu admin** — **Blood Inventory** — units received; **Outgoing** = fulfilled
5. **Either hospital** — **Trace Unit** → search `UNIT-002-00001`
   - Lifecycle timeline
   - **Blockchain audit trail** with tx hashes (if chain is running)

### Verify blockchain is working

| Check | Expected |
|-------|----------|
| Trace page | “Blockchain audit trail” with `0x…` hashes |
| Hardhat node terminal | New mined transactions on register/screen/issue |
| `blood_units` table | `blockchain_register_tx`, `blockchain_screening_tx`, `blockchain_issue_tx` populated |
| `storage/logs/laravel.log` | `Blockchain anchor` warnings only if chain is down |

---

## Database tables

| Table | Purpose |
|-------|---------|
| `hospitals` | Registered facilities (pending / approved / rejected) |
| `users` | Accounts — roles: admin, hospital, lab |
| `blood_units` | Units per hospital + screening + blockchain tx hashes |
| `blood_requests` | Partner exchange requests |
| `blood_request_unit` | Which units were issued for a request |

---

## Features implemented

- [x] Tarrlok-branded login, registration, forgot/reset password, profile
- [x] 3-step hospital registration (Ghana regions, HeFRA license)
- [x] Platform admin — approve / reject registrations
- [x] Hospital portal — inventory, requests, partners, lab staff, facility, trace
- [x] Lab portal — register units, screening reports, inventory
- [x] Lab screening — quarantine → cleared/failed; only cleared units issuable
- [x] Partner exchange + incoming/outgoing blood requests
- [x] Approve, reject (with reason), issue — FIFO, units transfer to requester
- [x] Unit trace — lifecycle timeline + blockchain tx hashes
- [x] Blockchain audit log (`BloodBank.sol` + `BlockchainService`)
- [x] Demo seeder — Korle Bu + Ridge with sample inventory

### Optional / not implemented

- [ ] Email notifications (approval, rejection, requests)
- [ ] Donor self-service portal
- [ ] Blood expiry alerts

---

## Configuration

| File | Contents |
|------|----------|
| `tarrlok/config/tarrlok.php` | Blood groups, Ghana regions, institution types, screening tests |
| `tarrlok/config/blockchain.php` | RPC URL, private key, anchor script path |
| `tarrlok/public/assets/css/` | `login.css`, `register.css`, `admin.css`, `hospital.css` |

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Login fails / wrong credentials | Run `php artisan db:seed --class=AdminSeeder` or use demo table above |
| No partner hospitals | Register a second hospital and approve as admin, or `migrate:fresh --seed` |
| Issue fails — not enough stock | Lab must register + clear units first; Ridge seeder has 5 units |
| No blockchain tx hashes | Start `npm run node`, run `npm run deploy`, set `BLOCKCHAIN_ENABLED=true` |
| 404 on port 8000 | Kill duplicate `php artisan serve` processes |
| Apache issues | Use `php artisan serve` or see `apache/README.md` if configured |

---

## Development

```bash
cd tarrlok
php artisan test          # Run tests
php artisan config:clear  # After .env changes
```

- Tarrlok UI uses **Blade + plain CSS** — not Tailwind on portal pages.
- Blockchain uses the **first Hardhat dev account** private key — local demo only, never production.

---

## License

Final-year academic project. Laravel framework components are [MIT licensed](https://opensource.org/licenses/MIT).
