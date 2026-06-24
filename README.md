# Tarrlok — Blockchain-Based Blood Bank Management System

Final-year project for **Ghana hospitals**: a blood bank management platform with blockchain-verified traceability. Hospitals register on the network, are verified by a central platform administrator, and (planned) exchange blood units with an auditable on-chain trail.

## Tech stack

| Layer | Technology |
|--------|------------|
| Backend | **Laravel 13** (PHP 8.3+) |
| Frontend | **Blade** templates + plain CSS (no React, no Tailwind) |
| Auth | Laravel Breeze (Blade) |
| Database | **SQLite** (local dev) or **MySQL** |
| Blockchain | **Planned** — Hardhat / Ganache / Solidity |

## Project structure

```
bbbms-project/
├── tarrlok/                    # Laravel application (main codebase)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Admin/          # Platform admin (approvals)
│   │   │   └── Auth/           # Login + hospital registration wizard
│   │   └── Models/             # User, Hospital
│   ├── config/tarrlok.php      # Ghana regions, institution types
│   ├── database/migrations/
│   ├── public/assets/          # CSS, fonts, icons (Tarrlok UI)
│   ├── resources/views/
│   │   ├── auth/               # Login, 3-step register wizard
│   │   └── admin/              # Platform admin dashboard
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

---

## Application URLs

| URL | Who | Purpose |
|-----|-----|---------|
| `/login` | Everyone | Sign in |
| `/register` | Hospitals (guest) | 3-step facility registration wizard |
| `/register/pending` | Hospitals (guest) | Post-submission confirmation |
| `/admin` | Platform admin | Overview & pending approvals |
| `/admin/registrations` | Platform admin | List / filter registrations |
| `/dashboard` | Approved hospital users | Hospital dashboard (placeholder) |

---

## User roles

| Role | Description |
|------|-------------|
| **admin** | Central Tarrlok platform administrator — approves or rejects hospital registrations |
| **hospital** | Hospital blood bank administrator — registers facility, manages inventory (planned) |
| **lab** | Blood lab staff (planned) |
| **donor** | Donor portal (planned) |

---

## Demo flow

### Hospital registration

1. Go to **http://127.0.0.1:8000/register**
2. **Step 1 — Facility details:** name, institution type, Ghana region, city, HeFRA license (`HFRA-XXX-1234`), phone (+233), official email
3. **Step 2 — Account holder:** admin name, job title, work email, password
4. **Step 3 — Review & submit** → status **pending** (cannot log in yet)

### Platform admin approval

1. Sign in at `/login` with the platform admin credentials from `.env`
2. Open **http://127.0.0.1:8000/admin**
3. Review a pending registration → **Approve** or **Reject** (with reason)
4. On approval, the hospital admin account becomes **active** and can sign in

### Hospital login (after approval)

1. Sign in at `/login` with the work email and password from registration
2. Redirected to `/dashboard`

---

## What is implemented

- [x] Tarrlok-branded login page (plain CSS, local Inter + Material Symbols fonts)
- [x] 3-step hospital registration wizard (session-based, Ghana regions & institution types)
- [x] `hospitals` and extended `users` tables with pending / approved / rejected workflow
- [x] Platform admin dashboard and registration review (approve / reject)
- [x] Login blocks pending users and unapproved facilities
- [x] Admin credentials configurable via `.env`

## Planned next

- [ ] Hospital dashboard (inventory, requests, partner exchange)
- [ ] Blockchain module (`BloodBank.sol` + Laravel `BlockchainService`)
- [ ] Email notifications on approval / rejection
- [ ] Donor and lab portals
- [ ] Restyle forgot-password and profile pages to match Tarrlok UI

---

## Key configuration

**Ghana regions & institution types** — `tarrlok/config/tarrlok.php`

**Styling** — `tarrlok/public/assets/css/` (`login.css`, `register.css`, `admin.css`)

**Sessions** — stored in database (`SESSION_DRIVER=database` in `.env`)

---

## Development notes

- UI uses **Blade + external CSS** only. Do not rely on Tailwind for auth/admin pages.
- Apache: point the document root to `tarrlok/public`, or use `php artisan serve` for local dev.
- Run tests: `php artisan test` (from the `tarrlok` directory).

---

## License

Final-year academic project. Laravel framework components are [MIT licensed](https://opensource.org/licenses/MIT).
