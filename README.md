# Tarrlok — Blockchain-Based Blood Bank Management System

**Tarrlok** is a final-year project for **Ghana hospitals**: a blood bank management platform for HeFRA-licensed facilities. Hospitals register on the network, are verified by a central platform administrator, lab staff register and screen blood units, and partner hospitals exchange blood through requests. Critical lifecycle events are also anchored on a local Ethereum smart contract for **immutable audit traceability**.

**Authors:** Ofei-Palm Valentino Papa Ayitey & Asiedu Enoch Ofori Kwasi (BSc. Computer Engineering, K.N.U.S.T.)

---

## Overview

| Layer | Role |
|-------|------|
| **Laravel + MySQL** | Day-to-day operations — users, inventory, screening, partner requests, expiry |
| **Solidity + Hardhat** | Tamper-evident audit log — registration, screening, partner issue |
| **Trace / Track** | Unit lifecycle in the database + on-chain transaction hashes |

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
| Public access (optional) | **Cloudflare Tunnel** — see [deploy guide](docs/DEPLOY-LOCAL-CLOUDFLARE.md) |

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

Transaction hashes are saved on `blood_units` and shown on **Trace Unit**, **public `/track`**, and **Admin → Blockchain**.

---

## Project structure

```
bbbms-project/
├── blockchain/                    # Hardhat + Solidity
│   ├── contracts/BloodBank.sol
│   ├── scripts/deploy.js
│   ├── scripts/anchor-event.js
│   ├── scripts/chain-status.js    # Admin chain health probe
│   ├── deployments/local.json       # Created after deploy
│   └── README.md
├── deploy/
│   └── cloudflared-config.example.yml
├── docs/
│   └── DEPLOY-LOCAL-CLOUDFLARE.md # Local server + Cloudflare Tunnel
├── apache/
│   └── README.md                  # Apache vhost (tarrlok.localhost)
├── tarrlok/                       # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/      # Admin, Hospital, Lab, DonationTrack
│   │   ├── Models/                # User, Hospital, Donor, BloodUnit, BloodRequest
│   │   └── Services/              # BlockchainService, BlockchainStatusService
│   ├── config/tarrlok.php         # Regions, blood groups, shelf life
│   ├── config/blockchain.php
│   ├── database/seeders/          # AdminSeeder, DemoSeeder
│   ├── public/assets/css/
│   └── resources/views/
│       ├── admin/                 # Platform admin + blockchain dashboard
│       ├── auth/                  # Login, hospital register, password reset
│       ├── hospital/              # Hospital portal
│       ├── lab/                   # Lab portal
│       ├── track/                 # Public donor tracking by unit ID
│       └── shared/trace/          # Staff unit trace page
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
- **5 cleared blood units** at Ridge (linked to a demo donor record; one unit expiring soon)
- Demo donor phone on file — tracking uses **unit ID**, not a login

For a completely fresh database:

```bash
php artisan migrate:fresh --seed
```

> `DemoSeeder` skips if Korle Bu already exists. Use `migrate:fresh --seed` to reset.

### 3. Run Laravel

**Option A — built-in server:**

```bash
php artisan serve
```

Open **http://127.0.0.1:8000**

**Option B — Apache** (this machine): see [`apache/README.md`](apache/README.md) — **http://tarrlok.localhost**

> Run only **one** web server on your chosen port.

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

Then: `php artisan config:clear`

If the chain is down, Tarrlok still works — anchoring is skipped and no tx hashes appear on trace/track.

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

**Donor tracking (no login):** open `/track` and enter `UNIT-002-00001` after seeding demo data.

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
| `/login` | Sign in (hospital, lab, admin) |
| `/track` | **Public** — track one donation by unit ID (no login) |
| `/track/{unitCode}` | Direct link to a unit’s donor-safe status page |
| `/register` | 3-step hospital registration wizard |
| `/register/pending` | Post-submission confirmation |
| `/forgot-password` | Request password reset link |
| `/profile` | Update name, email, password |

### Platform admin

| URL | Purpose |
|-----|---------|
| `/admin` | Overview & pending registrations |
| `/admin/blockchain` | **Chain health**, contract info, anchor stats, recent tx hashes |
| `/admin/registrations` | List / filter hospital registrations |
| `/admin/registrations/{hospital}` | Approve or reject a facility |

### Hospital portal

| URL | Purpose |
|-----|---------|
| `/hospital` | Dashboard (includes expiry alerts) |
| `/hospital/inventory` | Blood inventory (cleared, non-expired units) |
| `/hospital/requests` | Incoming / outgoing partner requests |
| `/hospital/requests/create` | Request blood from a partner |
| `/hospital/partners` | Browse approved partner hospitals |
| `/hospital/trace` | Trace a unit by ID |
| `/hospital/facility` | Facility profile |
| `/hospital/lab-staff` | Manage lab staff accounts |

### Lab portal

| URL | Purpose |
|-----|---------|
| `/lab` | Lab dashboard (includes expiry alerts) |
| `/lab/units` | Units at this hospital |
| `/lab/units/create` | Register unit + link donor by phone |
| `/lab/units/{unit}/screening` | Lab screening report |
| `/lab/trace` | Trace a unit by ID |

---

## User roles

| Role | Access |
|------|--------|
| **admin** | Approve/reject hospitals; **blockchain monitoring** |
| **hospital** | Inventory, partner requests, lab staff, trace |
| **lab** | Register units (with donor phone), screening, trace |
| **Public donor** | No login — `/track` with unit ID only (one unit per lookup) |

Login redirects: **admin** → `/admin`, **hospital** → `/hospital`, **lab** → `/lab`.

---

## Blood workflow

```
Lab registers unit       →  quarantine, screening: pending (donor linked by phone)
                         →  expires_at set (35-day shelf life)
Lab screening report     →  cleared → available  |  failed → discarded
Hospital inventory       →  only cleared + available + not expired units count as stock
Partner request          →  blood_requests: pending
Partner approve + issue  →  FIFO; units transfer to requesting hospital
Trace unit (staff)       →  full timeline + blockchain tx hashes
Donor track (public)     →  /track + unit ID — donor-safe view, no patient data
Daily expiry job         →  php artisan blood:mark-expired (also scheduled daily)
```

**Unit statuses:** `quarantine` → `available` (after screening) → transferred to partner as `available`, or `discarded`.

**Screening tests:** HIV, Hep B, Hep C, Syphilis — all must be non-reactive to clear.

**Request statuses:** `pending` → `approved` → `fulfilled`, or `rejected`.

**Unit codes:** auto-generated as `UNIT-{hospitalId}-{sequence}` (e.g. `UNIT-002-00001`). Lab staff give this ID to donors for `/track`.

---

## Full demo script (viva / presentation)

Requires blockchain terminals + web server running.

1. **Platform admin** — `/admin/blockchain` — confirm chain health (or note offline)
2. **Ridge admin** — **Blood Inventory** — confirm cleared units exist
3. **Korle Bu admin** — **Partner Exchange** → request O+ from Ridge Hospital
4. **Ridge admin** — **Blood Requests → Incoming** → Approve → **Issue unit**
5. **Korle Bu admin** — **Blood Inventory** — units received
6. **Public** — `/track` → `UNIT-002-00001` — timeline + blockchain hashes
7. **Either hospital** — **Trace Unit** — staff view of same unit

### Verify blockchain is working

| Check | Expected |
|-------|----------|
| Admin → Blockchain | Status **healthy**, block number, contract address |
| Trace / Track page | “Blockchain verification” with `0x…` hashes |
| Hardhat node terminal | New mined transactions on register/screen/issue |
| `blood_units` table | `blockchain_register_tx`, `blockchain_screening_tx`, `blockchain_issue_tx` populated |

---

## Deploy on a local server + Cloudflare Tunnel

Expose the app publicly without router port-forwarding:

1. Run Laravel on the server (Apache or `php artisan serve`)
2. Run Hardhat node + deploy on the **same server** (blockchain stays on `127.0.0.1:8545` — not tunneled)
3. Point **cloudflared** at your local web port
4. Set `APP_URL=https://your-subdomain.yourdomain.com`

Full guide: **[docs/DEPLOY-LOCAL-CLOUDFLARE.md](docs/DEPLOY-LOCAL-CLOUDFLARE.md)**

Example config: **[deploy/cloudflared-config.example.yml](deploy/cloudflared-config.example.yml)**

---

## Database tables

| Table | Purpose |
|-------|---------|
| `hospitals` | Registered facilities (pending / approved / rejected) |
| `users` | Accounts — roles: admin, hospital, lab |
| `donors` | Donor profiles (phone, eligibility); linked when lab registers units |
| `blood_units` | Units + donor + expiry + screening + blockchain tx hashes |
| `blood_requests` | Partner exchange requests |
| `blood_request_unit` | Which units were issued for a request |

---

## Features implemented

- [x] Tarrlok-branded login, registration, forgot/reset password, profile
- [x] 3-step hospital registration (Ghana regions, HeFRA license)
- [x] Platform admin — approve / reject registrations
- [x] **Admin blockchain dashboard** — chain health, anchor stats, recent tx log
- [x] Hospital portal — inventory, requests, partners, lab staff, facility, trace
- [x] Lab portal — register units (donor phone lookup), screening, inventory
- [x] Lab screening — quarantine → cleared/failed; only cleared units issuable
- [x] Partner exchange + incoming/outgoing blood requests
- [x] Approve, reject (with reason), issue — FIFO, units transfer to requester
- [x] Unit trace — lifecycle timeline + blockchain tx hashes
- [x] **Public donor tracking** — `/track` by unit ID (one unit, no login)
- [x] Blood expiry — shelf-life, dashboard alerts, `blood:mark-expired` command
- [x] Blockchain audit log (`BloodBank.sol` + `BlockchainService`)
- [x] Demo seeder — Korle Bu + Ridge with sample inventory

### Optional / not implemented

- [ ] Production email delivery (dev uses `MAIL_MAILER=log`)
- [ ] Public testnet/mainnet deployment (local Hardhat only)

---

## Configuration

| File | Contents |
|------|----------|
| `tarrlok/config/tarrlok.php` | Blood groups, Ghana regions, shelf life (35d), expiry warning (7d) |
| `tarrlok/config/blockchain.php` | RPC URL, private key, anchor + status scripts |
| `tarrlok/public/assets/css/` | `login.css`, `register.css`, `admin.css`, `hospital.css` |

---

## Useful commands

```bash
cd tarrlok
php artisan migrate:fresh --seed    # Reset DB + demo data
php artisan blood:mark-expired      # Discard expired units
php artisan schedule:run            # Run scheduled tasks (expiry)
php artisan config:clear            # After .env changes
php artisan test
```

```bash
cd blockchain
npm run node                        # Start chain (keep running)
npm run deploy                      # Deploy contract (after each node restart)
node scripts/chain-status.js        # Quick chain health check (CLI)
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Login fails / wrong credentials | Run `php artisan db:seed --class=AdminSeeder` or use demo table above |
| No partner hospitals | Register a second hospital and approve as admin, or `migrate:fresh --seed` |
| Issue fails — not enough stock | Lab must register + clear units first; Ridge seeder has 5 units |
| No blockchain tx hashes | Start `npm run node`, run `npm run deploy`, set `BLOCKCHAIN_ENABLED=true` |
| Admin blockchain shows offline | Hardhat node not running on the server; chain is local-only |
| `/track` not found | Run from `tarrlok/` web root; `php artisan route:list --name=track` |
| 404 on port 8000 | Kill duplicate `php artisan serve` processes |
| Apache issues | See `apache/README.md` or use `php artisan serve` |
| HTTPS redirect issues behind Cloudflare | Set `APP_URL=https://...`; app trusts proxies automatically |

---

## Development notes

- Tarrlok UI uses **Blade + plain CSS** — not Tailwind on portal pages.
- Blockchain uses the **first Hardhat dev account** private key — local demo only, never production.
- Restarting Hardhat **wipes chain state** — run `npm run deploy` again; old tx hashes in MySQL may reference a previous chain (acceptable for demo).

---

## License

Final-year academic project. Laravel framework components are [MIT licensed](https://opensource.org/licenses/MIT).
