# Tarrlok (Laravel app)

Main Laravel application for **Tarrlok** — the blockchain-based blood bank management system.

Full documentation: **[project README](../README.md)**  
FYP report: **[../Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx](../Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx)**  
Docker (all-in-one): **[docs/DOCKER.md](../docs/DOCKER.md)**

## Quick start

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

**URL:** http://127.0.0.1:8000  
**Apache (this machine):** http://tarrlok.localhost — see [../apache/README.md](../apache/README.md)

## With blockchain

**Terminal 1** — chain (keep running):

```bash
cd ../blockchain
npm install
npm run compile
npm run node
```

**Terminal 2** — deploy (once per node restart):

```bash
cd ../blockchain
npm run deploy
```

In `tarrlok/.env`:

```env
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
```

```bash
php artisan config:clear
```

Verify: sign in as admin, hospital, or lab → **Blockchain / Network ledger**. Every portal reads the same on-chain event log, integrity alerts, and blocked attempts.

Details: [blockchain/README.md](../blockchain/README.md)

## Public donor tracking

| URL | Purpose |
|-----|---------|
| `/track` | Enter unit ID from donation slip |
| `/track/UNIT-002-00001` | Direct link (demo unit after seed) |

No login. One unit per lookup — donors cannot see other people’s donations.

## Useful commands

```bash
php artisan migrate:fresh --seed   # Reset DB + demo hospitals
php artisan db:seed --class=DemoSeeder
php artisan blood:mark-expired     # Mark past-shelf-life units discarded
php artisan schedule:run             # Scheduled tasks (incl. hourly expiry)
php artisan config:clear
php artisan route:list --name=track
php artisan test
```

## Deploy + Cloudflare Tunnel

Run on a local server and expose via Cloudflare Tunnel:

**[docs/DEPLOY-LOCAL-CLOUDFLARE.md](../docs/DEPLOY-LOCAL-CLOUDFLARE.md)**

- Tunnel exposes **only the web app**
- Hardhat node runs on `127.0.0.1:8545` on the same machine (not public)
- Set `APP_URL=https://your-subdomain.domain` before `php artisan config:cache`

## Key paths

| Path | Purpose |
|------|---------|
| `app/Services/BlockchainService.php` | Anchors events on-chain + records blocked attempts |
| `app/Services/BlockchainStatusService.php` | Admin chain health + stats |
| `app/Services/BlockchainIntegrityService.php` | MySQL vs `getUnit()` compare |
| `app/Services/BlockchainLedgerService.php` | Shared event feed + integrity snapshot |
| `app/Models/BlockchainTamperAttempt.php` | Failed / reverted chain writes |
| `app/Http/Controllers/DonationTrackController.php` | Public `/track` |
| `app/Http/Controllers/Admin/BlockchainController.php` | Admin blockchain page |
| `app/Http/Controllers/BlockchainLedgerController.php` | Hospital + lab shared ledger |
| `resources/views/landing.blade.php` | Public home |
| `resources/views/shared/blockchain/` | Shared ledger Blade views |
| `config/tarrlok.php` | Blood groups, regions, shelf life, expiry |
| `config/blockchain.php` | RPC, private key, scripts |
| `database/seeders/DemoSeeder.php` | Korle Bu + Ridge demo data |
| `public/assets/css/` | Landing, portal, and ledger styles |
| `tests/Feature/` | Auth, navigation, shared-ledger tests |
