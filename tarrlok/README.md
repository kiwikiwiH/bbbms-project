# Tarrlok (Laravel app)

Main Laravel application for **Tarrlok** — the blockchain-based blood bank management system.

Full documentation: **[project README](../README.md)**  
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
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

```bash
php artisan config:clear
```

Verify: sign in as admin → **Blockchain** (`/admin/blockchain`).

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
php artisan schedule:run             # Scheduled tasks (incl. daily expiry)
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
| `app/Services/BlockchainService.php` | Anchors events on-chain |
| `app/Services/BlockchainStatusService.php` | Admin chain health + stats |
| `app/Http/Controllers/DonationTrackController.php` | Public `/track` |
| `app/Http/Controllers/Admin/BlockchainController.php` | Admin blockchain page |
| `config/tarrlok.php` | Blood groups, regions, shelf life, expiry |
| `config/blockchain.php` | RPC, private key, scripts |
| `database/seeders/DemoSeeder.php` | Korle Bu + Ridge demo data |
| `public/assets/css/` | Tarrlok UI styles |
