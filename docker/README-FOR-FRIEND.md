# Tarrlok — run without installing PHP, Node, or MySQL

Your friend only needs **Docker Desktop**.

## 1. Install Docker Desktop

- Windows / Mac: https://www.docker.com/products/docker-desktop/
- Start Docker Desktop and wait until it says **Running**.

## 2. Load the images (one time)

Open a terminal in **this folder** (where `tarrlok-images.tar` is):

**Windows (PowerShell):**
```powershell
docker load -i tarrlok-images.tar
```

**Mac / Linux:**
```bash
docker load -i tarrlok-images.tar
```

This takes a few minutes. You should see `tarrlok-app:demo`, `tarrlok-blockchain:demo`, and `mysql:8.0` loaded.

## 3. Start Tarrlok

**Windows:**
```powershell
docker compose up
```

**Mac / Linux:**
```bash
docker compose up
```

First start may take 1–2 minutes (database setup + demo data).

## 4. Open in browser

| URL | What |
|-----|------|
| http://localhost:8080 | Login page |
| http://localhost:8080/track/UNIT-002-00001 | Donor tracking demo |

### Login

| Role | Email | Password |
|------|--------|----------|
| Admin | `admin@tarrlok.gh` | `TarrlokAdmin2024!` |
| Ridge lab | `kofi.boateng@ridge.gov.gh` | `RidgeLab2024!` |

## Stop

Press `Ctrl+C` in the terminal, or in another terminal:

```bash
docker compose down
```

## Problems?

| Issue | Fix |
|-------|-----|
| Port 8080 busy | Edit `.env` → `APP_PORT=9090`, open http://localhost:9090 |
| `docker` not found | Install / start Docker Desktop |
| App keeps restarting | Run `docker compose logs app` and wait 2 min, or `docker compose down -v` then `docker compose up` again |
