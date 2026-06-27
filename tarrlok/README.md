# Tarrlok (Laravel app)

Main Laravel application for **Tarrlok** — the blockchain-based blood bank management system.

Full documentation: **[project README](../README.md)**

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

## With blockchain

Start the local chain and deploy the contract first — see [blockchain/README.md](../blockchain/README.md).

Then in `.env`:

```env
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

## Useful commands

```bash
php artisan migrate:fresh --seed   # Reset DB + demo hospitals
php artisan db:seed --class=DemoSeeder
php artisan config:clear
php artisan test
```

## Key paths

| Path | Purpose |
|------|---------|
| `app/Services/BlockchainService.php` | On-chain anchoring |
| `config/tarrlok.php` | Blood groups, regions, screening tests |
| `config/blockchain.php` | Chain RPC + deploy settings |
| `database/seeders/DemoSeeder.php` | Korle Bu + Ridge demo data |
| `public/assets/css/` | Tarrlok UI styles |
