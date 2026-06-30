# Docker — run and share Tarrlok

Run the full stack (Laravel + MySQL + Hardhat blockchain) with one command. No local PHP, Composer, or Node install required on the host.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/macOS) or Docker Engine + Compose (Linux)
- 4 GB+ free RAM recommended

## Quick start

From the project root (`bbbms-project/`):

```bash
cp docker/.env.example .env
docker compose up --build
```

First build takes a few minutes (Composer + npm inside the images).

| URL | Purpose |
|-----|---------|
| http://localhost:8080 | Tarrlok web app |
| http://localhost:8080/track/UNIT-002-00001 | Public donor tracking (demo unit) |
| http://localhost:8545 | Hardhat JSON-RPC (optional debugging) |

### Demo logins

| Role | Email | Password |
|------|--------|----------|
| Platform admin | `admin@tarrlok.gh` | `TarrlokAdmin2024!` |
| Korle Bu hospital | `kwame.mensah@korlebu.gov.gh` | `KorleBu2024!` |
| Korle Bu lab | `ama.osei@korlebu.gov.gh` | `KorleBuLab2024!` |
| Ridge hospital | `efua.adjei@ridge.gov.gh` | `Ridge2024!` |
| Ridge lab | `kofi.boateng@ridge.gov.gh` | `RidgeLab2024!` |

Change the host port in `.env` (`APP_PORT=9090`) if 8080 is already in use, then open `http://localhost:9090`.

## What starts automatically

On each `app` container start:

1. Waits for MySQL and the Hardhat node
2. Deploys `BloodBank.sol` to the local chain
3. Runs migrations
4. Seeds demo hospitals, users, and blood units (first run)
5. If the blockchain was reset (new contract deploy), runs `migrate:fresh --seed` so on-chain hashes match the database

## Common commands

```bash
# Run in background
docker compose up -d --build

# View logs
docker compose logs -f app

# Stop (keeps database volume)
docker compose down

# Full reset — wipe DB and demo data
docker compose down -v
docker compose up --build

# Run artisan inside the app container
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate:fresh --seed --force

# Open a shell in the app container
docker compose exec app bash
```

## Architecture

```
┌─────────────┐     ┌─────────────┐     ┌──────────────────┐
│   Browser   │────▶│  app :8080  │────▶│  mysql :3306     │
│             │     │ PHP+Apache  │     │  (persistent)    │
└─────────────┘     │ + Node.js   │     └──────────────────┘
                    └──────┬──────┘
                           │
                           ▼
                    ┌──────────────────┐
                    │ blockchain :8545 │
                    │ Hardhat node     │
                    └──────────────────┘
```

| Service | Image | Notes |
|---------|--------|--------|
| `app` | `docker/app/Dockerfile` | Laravel, Apache, Node (for `anchor-event.js`) |
| `mysql` | `mysql:8.0` | Data in Docker volume `mysql_data` |
| `blockchain` | `docker/blockchain/Dockerfile` | Ephemeral chain — restarts wipe on-chain state |

Environment variables for ports and `APP_KEY` are in `.env` (copy from `docker/.env.example`). Laravel runtime config is set in `docker-compose.yml`.

## Sharing with a friend (no PHP / Node / MySQL on their PC)

Your friend only needs **[Docker Desktop](https://www.docker.com/products/docker-desktop/)**. They do **not** run Composer, npm, or install PHP.

### You — create a share zip (one time)

From the project root on your machine (needs internet to build images):

**Windows (PowerShell):**
```powershell
.\docker\scripts\package-for-friend.ps1
```

This creates **`tarrlok-docker-share.zip`** in the project folder (~1–2 GB). Send it via Google Drive, OneDrive, USB, etc.

**Manual steps** (if you prefer):
```bash
docker compose build
docker tag bbbms-project-app tarrlok-app:demo
docker tag bbbms-project-blockchain tarrlok-blockchain:demo
docker pull mysql:8.0
docker save -o tarrlok-images.tar tarrlok-app:demo tarrlok-blockchain:demo mysql:8.0
```
Then zip together: `tarrlok-images.tar`, `docker-compose.share.yml` (rename to `docker-compose.yml`), `docker/.env.example` (rename to `.env`), and `docker/README-FOR-FRIEND.md`.

### Your friend — run it

1. Install and start **Docker Desktop**
2. Unzip `tarrlok-docker-share.zip`
3. Open a terminal in that folder:
   ```bash
   docker load -i tarrlok-images.tar
   docker compose up
   ```
4. Open **http://localhost:8080**

Full friend instructions are in `docker/README-FOR-FRIEND.md` (copied into the zip as `README.md`).

> **Note:** The zip is large because it contains pre-built images. Your friend never runs `docker compose build` — no package downloads except Docker Desktop itself.

---

## Sharing with others (developers)

### Option A — Git + Compose (they build on first run)

Share the repository. Recipients need internet for the first `docker compose up --build`:

```bash
git clone <repo-url>
cd bbbms-project
cp docker/.env.example .env
docker compose up --build
```

### Option B — Publish images to a registry

```bash
docker compose build
docker tag bbbms-project-app YOUR_USER/tarrlok-app:latest
docker tag bbbms-project-blockchain YOUR_USER/tarrlok-blockchain:latest
docker push YOUR_USER/tarrlok-app:latest
docker push YOUR_USER/tarrlok-blockchain:latest
```

Give them `docker-compose.share.yml` with `image: YOUR_USER/tarrlok-app:latest` instead of local tags.

### Option C — Offline `.tar` only

Same as the friend zip above, without the packaging script.

## Blockchain notes

- The Hardhat dev private key in compose is **for local demo only** — never use it on a public network.
- Restarting only the `blockchain` service triggers an automatic DB refresh so trace/track hashes stay valid.
- Restarting `mysql` or `app` alone does not wipe data.

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Port 8080 in use | Set `APP_PORT=9090` in `.env` |
| `app` keeps restarting | `docker compose logs app` — often MySQL not ready; wait or `docker compose down -v` and retry |
| No blockchain hashes on trace | Check `docker compose logs blockchain` and Admin → Blockchain |
| Permission errors on storage | `docker compose exec app chown -R www-data:www-data storage bootstrap/cache` |
| Stale demo after manual chain reset | `docker compose exec app php artisan migrate:fresh --seed --force` |

## Production

This setup is intended for **local demos, viva, and development**. For production (e.g. `tarrlok.tesnet.xyz`), use the Apache + Cloudflare Tunnel guide: [DEPLOY-LOCAL-CLOUDFLARE.md](DEPLOY-LOCAL-CLOUDFLARE.md).
