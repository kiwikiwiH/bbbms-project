# Tarrlok — Blockchain-Based Blood Bank Management System

**Tarrlok** is a final-year project for **Ghana hospitals**: a blood bank management platform for HeFRA-licensed facilities. Hospitals register on the network, are verified by a central platform administrator, lab staff register and screen blood units, and partner hospitals exchange blood through requests. Critical lifecycle events are also anchored on a local Ethereum smart contract for **immutable audit traceability**. Admin, hospital, and lab portals all read the **same shared ledger**, compare MySQL to on-chain `getUnit()` state, and list **blocked write attempts**.

**Authors:** Ofei-Palm Valentino Papa Ayitey (1825922) & Asiedu Enoch Ofori Kwasi (1818522)  
**Programme:** BSc. Computer Engineering, KNUST  
**Supervisor:** Ing. Dr Bright Yeboah-Akowuah  
**Report:** [Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx](Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx)

---

## Overview

| Layer | Role |
|-------|------|
| **Laravel + MySQL** | Day-to-day operations — users, inventory, **component-labelled bags**, screening, partner requests, expiry |
| **Solidity + Hardhat** | Tamper-evident audit log — registration, screening, partner issue |
| **Shared ledger** | Role-scoped app access to the same chain (admin = full; hospital/lab = relevant units) |
| **Trace / Track** | Staff: timeline + block/tx trail · Donors `/track`: status only |

MySQL is the **operational database**. The blockchain is an **audit trail** — events cannot be altered once mined. Portals share **one** local chain; **who sees which events** is role-based in the app (not “only admin can access the blockchain”).

---

## Tech stack

| Layer | Technology |
|--------|------------|
| Backend | **Laravel 13** (PHP 8.3+) |
| Frontend | **Blade** templates + plain CSS |
| Auth | Session login (admin / hospital / lab) |
| Database | **MySQL** (recommended) or **SQLite** |
| Blockchain | **Hardhat** + `BloodBank.sol` + Laravel `BlockchainService` |
| Local chain | Hardhat node (`http://127.0.0.1:8545`) |
| Public access (optional) | **Cloudflare Tunnel** — see [deploy guide](docs/DEPLOY-LOCAL-CLOUDFLARE.md) |

---

## Architecture

```
Browser (Blade UI — landing, portals, /track, shared ledger)
       │
       ▼
Laravel (controllers + services)
       │
       ├──────────────────┐
       ▼                  ▼
   MySQL            BlockchainService / LedgerService
 (operations)              │
                           ▼
              anchor-event.js  |  read-ledger.js
                           │
                           ▼
              BloodBank.sol (local Ethereum :8545)
```

**On-chain events** (via `BloodBank.sol`):

| App action | Smart contract | Event |
|------------|----------------|-------|
| Lab registers unit | `registerUnit()` | `UnitRegistered` |
| Screening cleared/failed | `recordScreening()` | `UnitScreened` |
| Partner issue | `recordIssue()` | `UnitIssued` |

Events include **block numbers** and tx hashes. Admin sees the full network + unit history search. Hospital and lab see scoped history for units they handle. Public `/track` shows **status only** (no raw hash dump). Staff **Trace Unit** shows the block/event trail. Portals also compare MySQL to on-chain `getUnit()` and list blocked writes in `blockchain_tamper_attempts`.

---

## Project structure

```
bbbms-project/
├── Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx
├── scripts/
│   ├── build_fyp_report.py        # Regenerates the Word report
│   └── report_figures/            # Architecture diagrams used in the report
├── blockchain/                    # Hardhat + Solidity
│   ├── contracts/BloodBank.sol
│   ├── scripts/deploy.js
│   ├── scripts/anchor-event.js
│   ├── scripts/chain-status.js    # Admin chain health probe
│   ├── scripts/read-ledger.js     # Shared ledger reader
│   ├── deployments/local.json     # Created after deploy
│   └── README.md
├── deploy/
│   └── cloudflared-config.example.yml
├── docker/                        # Dockerfiles + compose env template
├── docker-compose.yml             # Laravel + MySQL + Hardhat stack
├── docs/
│   ├── DOCKER.md                  # Run and share via Docker
│   └── DEPLOY-LOCAL-CLOUDFLARE.md # Local server + Cloudflare Tunnel
├── apache/
│   └── README.md                  # Apache vhost (tarrlok.localhost)
├── tarrlok/                       # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/      # Admin, Hospital, Lab, DonationTrack, ledger
│   │   ├── Models/                # User, Hospital, Donor, BloodUnit, BloodRequest, BlockchainTamperAttempt
│   │   └── Services/              # Blockchain, Status, Integrity, Ledger
│   ├── config/tarrlok.php         # Regions, blood groups, shelf life
│   ├── config/blockchain.php
│   ├── database/seeders/          # AdminSeeder, DemoSeeder
│   ├── public/assets/css/         # Landing, portals, ledger
│   ├── tests/Feature/             # Auth, navigation, shared-ledger tests
│   └── resources/views/
│       ├── landing.blade.php      # Public home
│       ├── admin/                 # Platform admin + blockchain dashboard
│       ├── auth/                  # Login, hospital register, password reset
│       ├── hospital/              # Hospital portal
│       ├── lab/                   # Lab portal
│       ├── track/                 # Public donor tracking by unit ID
│       └── shared/                # Trace + shared blockchain ledger
└── README.md
```

---

## Prerequisites

- **PHP 8.3+** — `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`
- **Composer**
- **Node.js 18+** — required for blockchain
- **MySQL 8** (recommended) or SQLite for local dev

**Or use Docker** (no local PHP/MySQL/Node): see **[docs/DOCKER.md](docs/DOCKER.md)**

```bash
cp docker/.env.example .env
docker compose up --build
# → http://localhost:8080
```

---

## Quick start

### 1. Laravel app

```bash
cd tarrlok
composer install
copy .env.example .env
php artisan key:generate
php artisan config:clear
```

Set `APP_URL` in `.env` to **exactly** the URL you open in the browser. If it is wrong, CSS/JS will 404 and the site looks unstyled.

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

After every `git pull` that includes new features, run migrations again:

```bash
cd tarrlok
php artisan migrate
php artisan config:clear
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
+----------------+-----------------------------+-----------------+
| Role           | Email                       | Password        |
+----------------+-----------------------------+-----------------+
| Korle Bu admin | kwame.mensah@korlebu.gov.gh | KorleBu2024!    |
| Korle Bu lab   | ama.osei@korlebu.gov.gh     | KorleBuLab2024! |
| Ridge admin    | efua.adjei@ridge.gov.gh     | Ridge2024!      |
| Ridge lab      | kofi.boateng@ridge.gov.gh   | RidgeLab2024!   |
+----------------+-----------------------------+-----------------+

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
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
```

Then: `php artisan config:clear`

If the chain is down, Tarrlok still works — anchoring is skipped and no tx hashes appear on trace/track.

See [`blockchain/README.md`](blockchain/README.md) for details.

---

## Demo accounts

| Role | Email | Password |
|------|--------|----------|
| Role           | Email                       | Password        |
+----------------+-----------------------------+-----------------+
| Korle Bu admin | kwame.mensah@korlebu.gov.gh | KorleBu2024!    |
| Korle Bu lab   | ama.osei@korlebu.gov.gh     | KorleBuLab2024! |
| Ridge admin    | efua.adjei@ridge.gov.gh     | Ridge2024!      |
| Ridge lab      | kofi.boateng@ridge.gov.gh   | RidgeLab2024!   |
+----------------+-----------------------------+-----------------+

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
| `/` | Public landing — live stock bars, track / login / register |
| `/login` | Sign in (hospital, lab, admin) |
| `/track` | **Public** — track one donation by unit ID (no login; consent required) |
| `/track/{unitCode}` | Direct link to a unit’s donor-safe status page |
| `/register` | 3-step hospital registration wizard |
| `/register/pending` | Post-submission confirmation |
| `/forgot-password` | Request password reset link |
| `/profile` | Update name, email, password |

### Platform admin

| URL | Purpose |
|-----|---------|
| `/admin` | Overview & pending registrations |
| `/admin/auth-log` | **Sign-in log** — who logged in / out / failed (admin only) |
| `/admin/blockchain` | **Full audit trail**, chain health, **unit history search** (Block N · tx), integrity, blocked attempts |
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
| `/hospital/blockchain` | **Unit audit trail** — scoped to units requested / held / issued |
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
| `/lab/blockchain` | **Facility ledger** — scoped to units at this hospital |

---

## User roles

| Role | Access |
|------|--------|
| **admin** | Approve/reject hospitals; chain health + shared ledger |
| **hospital** | Inventory, partner requests, lab staff, trace, network ledger |
| **lab** | Register units (with donor phone), screening, trace, network ledger |
| **Public donor** | No login — `/track` with unit ID only (one unit per lookup, if consent) |

Login redirects: **admin** → `/admin`, **hospital** → `/hospital`, **lab** → `/lab`.

---

## Blood workflow

```
Lab registers unit       →  quarantine, screening: pending (donor linked by phone)
                         →  component_type on bag (Whole Blood / RBC / FFP / Platelets / Cryo)
                         →  expires_at from component shelf life (not one global number)
Lab screening report     →  cleared → available  |  failed → discarded
Print bag label (QR)     →  stick on pack; QR opens /track/{unit_code}
Print donor slip         →  handout with QR + unit ID (includes donor name)
Hospital inventory       →  only cleared + available + not expired units count as stock
Partner request          →  blood_group + component_type; pending
Partner approve + issue  →  FIFO matching group **and** component; units transfer
Trace unit (staff)       →  full timeline + Block N · tx trail
Public /track (donor)    →  status timeline only (no raw hashes)
Donor track (public)     →  /track + unit ID — donor-safe view, no patient data
Daily expiry job         →  php artisan blood:mark-expired (also scheduled hourly)
```

**Component types (Phase 1):** each registration is one labelled bag. Component is MySQL operational metadata (not on-chain yet). A future extension is one donation → multiple component bags.

**Shelf life (FYP constants):** Whole Blood / RBC 35 days · Platelets 5 days · FFP / Cryoprecipitate 365 days.

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
6. **Public** — `/track` → `UNIT-002-00001` — status timeline (no raw hashes)
7. **Either hospital or lab** — **Trace Unit** — staff view with Block N · tx
8. **Admin** — `/admin/blockchain?unit=…` — full network + unit history search; hospital/lab see **scoped** ledgers
9. **Tamper demo** — second screening is blocked on-chain and appears under **Blocked attempts**; editing a unit blood group in MySQL after anchoring shows a **Tampered** integrity alert while the chain keeps the original group

### Verify blockchain is working

| Check | Expected |
|-------|----------|
| Admin → Blockchain | Status **healthy**, unit search shows **Block N · tx** |
| Hospital → Unit audit trail | Only units this hospital requested / holds / issued |
| Lab → Facility ledger | Only this facility’s units |
| Trace (staff) | Block/event trail + integrity |
| Track (donor) | Status timeline; no raw hash dump |
| Hardhat node terminal | New mined transactions on register/screen/issue |
| `blood_units` table | `blockchain_register_tx`, `blockchain_screening_tx`, `blockchain_issue_tx` populated |
| `blockchain_tamper_attempts` | Failed / reverted writes attributed to the signed-in user |

---

## Deploy on a local server + Cloudflare Tunnel

Expose via **`https://tarrlok.tesnet.xyz`** (or your subdomain) on an existing **tesnet.xyz** server:

**[docs/DEPLOY-LOCAL-CLOUDFLARE.md](docs/DEPLOY-LOCAL-CLOUDFLARE.md)** — includes tesnet.xyz steps alongside `pay.tesnet.xyz`

Example tunnel config: **[deploy/cloudflared-tesnet.xyz.example.yml](deploy/cloudflared-tesnet.xyz.example.yml)**

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
| `blockchain_tamper_attempts` | Blocked / failed chain writes (actor, action, reason) |

---

## Features implemented

- [x] Public landing page — live stock, honest CTAs (track / login / register)
- [x] Tarrlok-branded login, registration, forgot/reset password, profile
- [x] 3-step hospital registration (Ghana regions, HeFRA license)
- [x] Platform admin — approve / reject registrations
- [x] Platform admin — **revoke access** for long-approved hospitals (suspends staff, closes open requests, email notice)
- [x] **Role-based blockchain visibility** — admin full trail + unit search (Block N · tx); hospital/lab scoped; donor status-only
- [x] Integrity compare (MySQL vs `getUnit()`) and **blocked-attempt** log
- [x] Admin blockchain dashboard — chain health, anchor stats, recent tx log, unit history search
- [x] **Component type** on bags and partner requests (Whole Blood / RBC / FFP / Platelets / Cryo; per-component shelf life)
- [x] **Admin sign-in log** — login / logout / failed attempts visible at `/admin/auth-log`
- [x] **Printable bag QR label** — lab prints sticker (unit ID + group/component/expiry + QR → `/track`); donor slip also has QR
- [x] Lab portal — register units (donor phone lookup), screening, inventory
- [x] Lab screening — quarantine → cleared/failed; only cleared units issuable
- [x] Partner exchange + incoming/outgoing blood requests
- [x] **Stock check before Approve** — insufficient blood type is flagged; Approve/Issue blocked until stock covers quantity
- [x] **Reverse approval** — undo Approve before issue; cancel approved outgoing requests
- [x] Request **audit log** (requested / approved / reversed / rejected / issued)
- [x] Blood-type, **component**, and screening **filters** on inventory and requests
- [x] Simple hospital **analytics** charts (stock by group, request status, screening outcomes)
- [x] Approve, reject (with reason), issue — FIFO, units transfer to requester
- [x] Unit trace — lifecycle timeline + Block N · tx trail
- [x] **Public donor tracking** — `/track` status timeline only (no raw hash dump; consent required)
- [x] Blood expiry — shelf-life, dashboard alerts, `blood:mark-expired` command
- [x] Blockchain audit log (`BloodBank.sol` + `BlockchainService`)
- [x] Demo seeder — Korle Bu + Ridge with sample inventory
- [x] Feature tests — landing, auth, navigation, shared ledger, tamper persistence
- [x] FYP report (IEEE-style references) — see Word file at repo root

### Optional / not implemented

- [ ] Production email delivery (set `MAIL_MAILER=smtp` and run with `QUEUE_CONNECTION=sync`)
- [ ] Public testnet/mainnet deployment (local Hardhat only)
- [ ] Per-hospital wallets / multi-node consensus
- [ ] Clinical cross-match, cold-chain IoT, or NBS/BSIS export

---

## Configuration

| File | Contents |
|------|----------|
| `tarrlok/config/tarrlok.php` | Blood groups, Ghana regions, shelf life (35d), expiry warning (7d) |
| `tarrlok/config/blockchain.php` | RPC URL, private key, anchor + status + ledger scripts |
| `tarrlok/public/assets/css/` | `landing.css`, `login.css`, `register.css`, `admin.css`, `hospital.css`, `ledger.css` |

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
node scripts/read-ledger.js "{\"action\":\"ledger\",\"unitCodes\":[]}"
```

---

## Final-year report

The completed project report is:

**[Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx](Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx)**

It covers Chapters 1–6 (introduction through conclusion), verified IEEE-style references, architecture figures, demo accounts, and an appendix aligned with this repository (Laravel + MySQL + Hardhat, not React/Flask/Ganache).

To regenerate after code or citation changes:

```bash
python scripts/build_fyp_report.py
```

Requires Python 3 with `python-docx` and `matplotlib`.

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Login fails / wrong credentials | Run `php artisan db:seed --class=AdminSeeder` or use demo table above |
| No partner hospitals | Register a second hospital and approve as admin, or `migrate:fresh --seed` |
| Issue fails — not enough stock | Lab must register + clear units first; Ridge seeder has 5 units |
| No blockchain tx hashes | Start `npm run node`, run `npm run deploy`, set `BLOCKCHAIN_ENABLED=true` |
| Admin / hospital / lab ledger empty | Same as above; then open `/admin/blockchain`, `/hospital/blockchain`, or `/lab/blockchain` |
| Admin blockchain shows offline | Hardhat node not running on the server; chain is local-only |
| Integrity says tampered after node restart | Local chain was wiped — redeploy, or use that mismatch as the viva demo |
| `/track` 404 or empty | Unit must exist **and** donor tracking consent must be on; demo: `UNIT-002-00001` |
| `/track` not found | Run from `tarrlok/` web root; `php artisan route:list --name=track` |
| 404 on port 8000 | Kill duplicate `php artisan serve` processes |
| Apache issues | See `apache/README.md` or use `php artisan serve` |
| HTTPS redirect issues behind Cloudflare | Set `APP_URL=https://...`; app trusts proxies automatically |
| **Pages load but look unstyled** | `APP_URL` does not match the browser URL. Set it, then `php artisan config:clear`. Open DevTools → Network and check `/assets/css/*.css` (should be 200, not 404). |
| Unstyled on Laragon `*.test` | Either point the vhost document root at `tarrlok/public`, or open the repo folder (root `.htaccess` forwards into `tarrlok/public`). Still set `APP_URL` to that host. |
| Unstyled via `localhost/.../tarrlok/public` | Do not serve Laravel from a subfolder if you can avoid it. Use `php artisan serve` or a vhost whose root is `tarrlok/public`. |
| Secure cookie / cannot stay logged in on HTTP | Set `SESSION_SECURE_COOKIE=false` for local HTTP. |
| **500 on Approve / Reject / Reverse / Issue** (`Unknown column 'rejected_by'` or `approved_by`) | New request-audit columns are missing. From `tarrlok/`: `php artisan migrate`. If that fails, start MySQL in Laragon first. |

---

## Development notes

- Tarrlok UI uses **Blade + plain CSS** — not Tailwind on portal pages. There is no React/Flask/Ganache stack.
- Blockchain uses the **first Hardhat dev account** private key — local demo only, never production.
- Restarting Hardhat **wipes chain state** — run `npm run deploy` again; old tx hashes in MySQL may reference a previous chain (acceptable for demo / integrity mismatch).
- Shared ledger = one permissioned chain, many authenticated portals. Not per-hospital Geth nodes.

---

## License

Final-year academic project. Laravel framework components are [MIT licensed](https://opensource.org/licenses/MIT).
