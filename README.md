# Tarrlok — Blockchain-Based Blood Bank Management System

Final-year project for **Ghana hospitals**: a blood bank management platform for HeFRA-licensed facilities. Hospitals register on the network, are verified by a central platform administrator, record blood units through lab staff, and fulfill partner blood requests. **Blockchain traceability is planned for a later phase** — the app currently runs entirely on Laravel and the database.

## Tech stack

| Layer | Technology |
|--------|------------|
| Backend | **Laravel 13** (PHP 8.3+) |
| Frontend | **Blade** templates + plain CSS (no React, no Tailwind on Tarrlok pages) |
| Auth | Laravel Breeze (Blade) |
| Database | **SQLite** (local dev) or **MySQL** |
| Blockchain | **Planned (last)** — Hardhat / Ganache / Solidity + Laravel `BlockchainService` |

## Project structure

```
bbbms-project/
├── tarrlok/                         # Laravel application (main codebase)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/           # Platform admin (approvals)
│   │   │   │   ├── Auth/            # Login + hospital registration wizard
│   │   │   │   ├── Hospital/        # Inventory, requests, lab staff
│   │   │   │   └── Lab/             # Blood unit registration
│   │   │   └── Middleware/          # EnsureAdmin, EnsureHospital, EnsureLab
│   │   └── Models/                  # User, Hospital, BloodUnit, BloodRequest
│   ├── config/tarrlok.php           # Ghana regions, institution types, blood groups
│   ├── database/migrations/
│   ├── public/assets/               # CSS, fonts, icons (Tarrlok UI)
│   ├── resources/views/
│   │   ├── auth/                    # Login, 3-step register wizard
│   │   ├── admin/                   # Platform admin dashboard
│   │   ├── hospital/                # Hospital portal
│   │   └── lab/                     # Lab staff portal
│   └── routes/
│       ├── web.php
│       └── auth.php
└── README.md
```

## Prerequisites

- **PHP 8.3+** with extensions: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`
- **Composer**
- **Node.js** (optional — only if you use Vite/Breeze asset builds later)
- **MySQL 8** (optional — SQLite works out of the box for development)

## Quick start

### 1. Install dependencies

```bash
cd tarrlok
composer install
```

### 2. Environment

```bash
copy .env.example .env
php artisan key:generate
```

Default database is **SQLite** (`database/database.sqlite` is created automatically on migrate). To use MySQL instead, set in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tarrlok
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Database & platform admin

```bash
php artisan migrate
php artisan db:seed
```

This creates the **platform administrator** from `.env`:

```env
TARRLOK_ADMIN_EMAIL=admin@tarrlok.gh
TARRLOK_ADMIN_PASSWORD=TarrlokAdmin2024!
TARRLOK_ADMIN_NAME="Tarrlok Platform Admin"
```

After changing these values, sync to the database:

```bash
php artisan db:seed --class=AdminSeeder
```

### 4. Run the app

```bash
php artisan serve
```

Open **http://127.0.0.1:8000**

> **Tip:** Run only one `php artisan serve` instance on port 8000. Multiple processes can cause 404s or stale routes.

---

## Application URLs

### Public & auth

| URL | Who | Purpose |
|-----|-----|---------|
| `/` | Signed-in users | Redirects by role (admin / hospital / lab) |
| `/login` | Everyone | Sign in |
| `/register` | Hospitals (guest) | 3-step facility registration wizard |
| `/register/pending` | Hospitals (guest) | Post-submission confirmation |
| `/forgot-password` | Guests | Password reset (Breeze default styling) |

### Platform admin

| URL | Purpose |
|-----|---------|
| `/admin` | Overview & pending registration count |
| `/admin/registrations` | List / filter hospital registrations |
| `/admin/registrations/{hospital}` | Review a single registration |
| POST approve / reject | Activate or reject a facility |

### Hospital portal

| URL | Purpose |
|-----|---------|
| `/hospital` | Dashboard (units on hand, pending requests, lab staff) |
| `/hospital/inventory` | Blood inventory by group and status |
| `/hospital/requests` | **Incoming** partner requests — approve, reject, issue |
| `/hospital/partners` | Partner exchange *(placeholder)* |
| `/hospital/facility` | Facility profile |
| `/hospital/lab-staff` | List lab staff accounts |
| `/hospital/lab-staff/create` | Create lab staff login |

### Lab portal

| URL | Purpose |
|-----|---------|
| `/lab` | Lab dashboard |
| `/lab/units` | Units recorded at this hospital |
| `/lab/units/create` | Register a new blood unit |

---

## User roles

| Role | Description |
|------|-------------|
| **admin** | Central Tarrlok platform administrator — approves or rejects hospital registrations |
| **hospital** | Hospital blood bank administrator — inventory, incoming requests, lab staff |
| **lab** | Blood lab staff — registers collected units into hospital inventory |
| **donor** | Donor portal *(planned)* |

Login redirects: **admin** → `/admin`, **hospital** → `/hospital`, **lab** → `/lab`. Pending users and unapproved facilities cannot sign in.

---

## Blood workflow (database)

```
Lab staff registers unit     →  blood_units (status: available)
Hospital views inventory     →  grouped counts by blood group
Partner sends request        →  blood_requests (pending)  [outgoing UI not built yet]
Fulfilling hospital issues   →  units marked issued, linked via blood_request_unit
```

**Blood unit statuses:** `available` → `issued` (on fulfillment). Unit codes are auto-generated (`UNIT-{hospitalId}-{sequence}`).

**Request statuses:** `pending` → `approved` → `fulfilled`, or `rejected`.

---

## Demo flow

### 1. Hospital registration

1. Go to **http://127.0.0.1:8000/register**
2. **Step 1 — Facility details:** name, institution type, Ghana region, city, HeFRA license (`HFRA-XXX-1234`), phone (+233), official email
3. **Step 2 — Account holder:** admin name, job title, work email, password
4. **Step 3 — Review & submit** → status **pending** (cannot log in yet)

### 2. Platform admin approval

1. Sign in at `/login` with the platform admin credentials from `.env`
2. Open **http://127.0.0.1:8000/admin**
3. Review a pending registration → **Approve** or **Reject** (with reason)
4. On approval, the hospital admin account becomes **active** and can sign in

### 3. Hospital admin setup

1. Sign in at `/login` with the work email and password from registration
2. Redirected to `/hospital`
3. Create lab staff under **Lab Staff** → lab user can sign in at `/login` and land on `/lab`

### 4. Lab registers blood

1. Lab staff signs in → `/lab`
2. **Register unit** — choose blood group and collection date
3. Unit appears in hospital **Blood Inventory** as available

### 5. Fulfill a partner request

1. Another hospital must have created a `blood_requests` row *(outgoing request UI coming next)*
2. Fulfilling hospital opens **Blood Requests** → approve → **Issue units**
3. System deducts oldest available units of the requested group and marks the request **fulfilled**

---

## Database tables

| Table | Purpose |
|-------|---------|
| `hospitals` | Registered facilities (pending / approved / rejected) |
| `users` | Accounts with `role`: admin, hospital, lab, donor; `status`: active, inactive |
| `blood_units` | Individual units per hospital |
| `blood_requests` | Partner exchange requests between hospitals |
| `blood_request_unit` | Pivot — which units were issued for a request |

No demo blood data is seeded. Tables start empty after migrate/seed except the platform admin account.

---

## What is implemented

- [x] Tarrlok-branded login and registration (plain CSS, local Inter + Material Symbols)
- [x] 3-step hospital registration wizard (session-based, Ghana regions & institution types)
- [x] `hospitals` and extended `users` tables with pending / approved / rejected workflow
- [x] Platform admin dashboard and registration review (approve / reject)
- [x] Hospital portal — dashboard, inventory, facility profile, lab staff management
- [x] Lab portal — register and list blood units
- [x] Incoming blood requests — approve, reject, issue (deducts inventory)
- [x] Role-based middleware and login redirects
- [x] Admin credentials configurable via `.env`

## Planned next (site first, blockchain last)

**Site completion**

- [ ] Partner Exchange page — browse approved partner hospitals
- [ ] Outgoing blood requests — hospital requests blood from a partner
- [ ] Incoming / outgoing tabs on the requests page
- [ ] Unit trace page (database lookup by unit ID)
- [ ] Restyle forgot-password and profile pages to match Tarrlok UI
- [ ] Email notifications on registration approval / rejection
- [ ] Donor portal (if required for project scope)

**Blockchain (final phase)**

- [ ] `blockchain/` — Hardhat + `BloodBank.sol`
- [ ] Laravel `BlockchainService` — anchor unit registration and issuance on-chain
- [ ] Trace page shows transaction hashes alongside database records

---

## Key configuration

**Ghana regions, institution types, blood groups** — `tarrlok/config/tarrlok.php`

**Styling** — `tarrlok/public/assets/css/` (`login.css`, `register.css`, `admin.css`, `hospital.css`)

**Sessions** — stored in database (`SESSION_DRIVER=database` in `.env`)

---

## Development notes

- UI uses **Blade + external CSS** on Tarrlok pages. Do not rely on Tailwind for auth, admin, hospital, or lab portals.
- Apache: point the document root to `tarrlok/public`, or use `php artisan serve` for local dev.
- Run tests: `php artisan test` (from the `tarrlok` directory).
- Blockchain integration will be added after the core site workflow is complete.

---

## License

Final-year academic project. Laravel framework components are [MIT licensed](https://opensource.org/licenses/MIT).
