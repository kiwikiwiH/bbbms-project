# Tarrlok — Full deployment guide (Namecheap → Cloudflare → tesnet server)

Complete walkthrough for publishing **Tarrlok** on your **tesnet.xyz** Windows server using **Cloudflare Tunnel** (`cloudflared`). No router port-forwarding required.

**Recommended public URL:** `https://tarrlok.tesnet.xyz`

---

## Architecture

```
Internet user
     │
     ▼
Cloudflare (DNS + HTTPS + WAF)
     │
     ▼
cloudflared tunnel (on your server)
     │
     ├── pay.tesnet.xyz      → http://127.0.0.1:80   (hotspot-pay, existing)
     └── tarrlok.tesnet.xyz  → http://127.0.0.1:8080 (Tarrlok Laravel)
                                    │
                                    ├── MySQL (127.0.0.1:3306)
                                    └── Hardhat node (127.0.0.1:8545, optional, not public)
```

| Component | Public? | Notes |
|-----------|---------|-------|
| Laravel web app | Yes (via tunnel) | Login, admin, lab, hospital, `/track` |
| MySQL | No | Local only |
| Hardhat blockchain | No | Local audit log; users see tx **hashes** in the app |

---

## Part 1 — Domain & DNS (Namecheap + Cloudflare)

You already run **`tesnet.xyz`** on this server. For Tarrlok you only need a **subdomain**: `tarrlok.tesnet.xyz`. Skip to [Part 2](#part-2--cloudflare-dns-for-tarrloktesnetxyz) if `tesnet.xyz` is already on Cloudflare.

### 1.1 Buy a domain on Namecheap (if you do not have one yet)

1. Go to [namecheap.com](https://www.namecheap.com) → search for your domain (e.g. `tesnet.xyz`).
2. Add to cart → checkout → complete payment.
3. In **Domain List** → **Manage** → note the domain is **Active**.

### 1.2 Point Namecheap to Cloudflare

Cloudflare should manage DNS (required for tunnels and free HTTPS).

1. Create a free account at [cloudflare.com](https://dash.cloudflare.com/sign-up).
2. **Add a site** → enter `tesnet.xyz` → select **Free** plan.
3. Cloudflare shows **two nameservers**, e.g.:
   - `ada.ns.cloudflare.com`
   - `bob.ns.cloudflare.com`
4. In **Namecheap** → Domain List → **Manage** → **Nameservers** → **Custom DNS**.
5. Enter Cloudflare’s two nameservers → save.
6. Back in Cloudflare, click **Check nameservers**. Propagation can take **15 minutes to 48 hours** (often under 1 hour).

When active, Cloudflare shows **Active** for the zone.

### 1.3 Cloudflare SSL settings

In Cloudflare dashboard → **SSL/TLS**:

| Setting | Value |
|---------|--------|
| Encryption mode | **Full** (tunnel terminates HTTPS at Cloudflare; origin is HTTP locally) |
| Always Use HTTPS | **On** (recommended) |
| Minimum TLS Version | TLS 1.2 |

---

## Part 2 — Cloudflare DNS for `tarrlok.tesnet.xyz`

Because you already tunnel **`pay.tesnet.xyz`**, add Tarrlok as a **second hostname on the same tunnel** (not a second tunnel).

### Option A — CLI (recommended)

```powershell
cloudflared tunnel route dns YOUR_TUNNEL_NAME tarrlok.tesnet.xyz
```

Replace `YOUR_TUNNEL_NAME` with the name you used when creating the tunnel (e.g. the same one used for `pay`).

### Option B — Cloudflare Dashboard

1. **DNS** → **Records** → **Add record**
2. Type: **CNAME**
3. Name: `tarrlok`
4. Target: `<your-tunnel-id>.cfargotunnel.com` (same target pattern as your `pay` record)
5. Proxy status: **Proxied** (orange cloud)
6. Save

Result: `tarrlok.tesnet.xyz` → your existing Cloudflare Tunnel.

---

## Part 3 — Server prerequisites

Your tesnet server should have:

| Software | Version | Purpose |
|----------|---------|---------|
| PHP | 8.3+ | Laravel |
| Composer | latest | PHP dependencies |
| MySQL | 8.x | Database |
| Node.js | 18+ | Blockchain (optional) |
| Git | any | Deploy updates |
| cloudflared | latest | Cloudflare Tunnel |

### Install cloudflared (Windows)

Download: [Cloudflare Tunnel downloads](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/)

Or with winget:

```powershell
winget install Cloudflare.cloudflared
```

Verify:

```powershell
cloudflared --version
```

### One-time tunnel setup (if not already done for pay.tesnet.xyz)

```powershell
cloudflared tunnel login
cloudflared tunnel create tesnet
```

Note the **tunnel UUID** and credentials file path (e.g. `C:\Users\You\.cloudflared\<UUID>.json`).

---

## Part 4 — Deploy the project on the server

### 4.1 Get the code onto the server

**Git (recommended):**

```powershell
cd C:\Apache24\htdocs
git clone <your-repo-url> bbbms-project
```

**Or copy** the whole `bbbms-project` folder to `C:\Apache24\htdocs\bbbms-project`.

Expected layout:

```
C:\Apache24\htdocs\bbbms-project\
├── blockchain\
├── deploy\
├── docs\
├── tarrlok\          ← Laravel app
├── docker-compose.yml
└── README.md
```

### 4.2 MySQL database

Open MySQL (command line or phpMyAdmin):

```sql
CREATE DATABASE tarrlok CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'tarrlok'@'localhost' IDENTIFIED BY 'your-strong-password-here';
GRANT ALL PRIVILEGES ON tarrlok.* TO 'tarrlok'@'localhost';
FLUSH PRIVILEGES;
```

### 4.3 Laravel production `.env`

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
copy .env.example .env
notepad .env
```

Production values:

```env
APP_NAME=Tarrlok
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tarrlok.tesnet.xyz

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tarrlok
DB_USERNAME=tarrlok
DB_PASSWORD=your-strong-password-here

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER=log

# Change before going live
TARRLOK_ADMIN_EMAIL=admin@tarrlok.gh
TARRLOK_ADMIN_PASSWORD=use-a-strong-password-here
TARRLOK_ADMIN_NAME="Tarrlok Platform Admin"

# Optional — blockchain on same server
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

### 4.4 Install and bootstrap Laravel

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`db:seed` creates the admin user from `TARRLOK_ADMIN_*` in `.env` plus demo hospitals/units.

---

## Part 5 — Run the web app locally (tunnel origin)

The tunnel connects to a **local** URL on the server. Pick **one** option.

### Option A — PHP built-in server (simplest)

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
php artisan serve --host=127.0.0.1 --port=8080
```

Keep this terminal open. Tunnel target: `http://127.0.0.1:8080`.

**Run as Windows service (optional):** use [NSSM](https://nssm.cc/) to run the command at boot.

### Option B — Apache (production-style)

Add a vhost (e.g. `C:\Apache24\conf\extra\tarrlok-tesnet.conf`):

```apache
<VirtualHost *:80>
    ServerName tarrlok.tesnet.xyz
    DocumentRoot "C:/Apache24/htdocs/bbbms-project/tarrlok/public"
    <Directory "C:/Apache24/htdocs/bbbms-project/tarrlok/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Include it in `httpd.conf`, test, restart Apache:

```powershell
C:\Apache24\bin\httpd.exe -t
# Restart Apache (Services or Apache Monitor)
```

Tunnel target: `http://127.0.0.1:80` (Apache routes by `Host: tarrlok.tesnet.xyz`).

---

## Part 6 — Cloudflare Tunnel config (add Tarrlok to existing tunnel)

Edit `C:\Users\<YOU>\.cloudflared\config.yml`.

Example with **both** pay and Tarrlok (see also `deploy/cloudflared-tesnet.xyz.example.yml`):

```yaml
tunnel: YOUR_TUNNEL_UUID
credentials-file: C:\Users\YOUR_USER\.cloudflared\YOUR_TUNNEL_UUID.json

ingress:
  # Existing — hotspot-pay
  - hostname: pay.tesnet.xyz
    service: http://127.0.0.1:80

  # NEW — Tarrlok (artisan serve on 8080)
  - hostname: tarrlok.tesnet.xyz
    service: http://127.0.0.1:8080

  # Required catch-all
  - service: http_status:404
```

**Important:** Put specific hostnames **above** the final `404` rule.

### Run or restart the tunnel

```powershell
cloudflared tunnel run tesnet
```

**Install as Windows service (runs at boot):**

```powershell
cloudflared service install
# Then start from Services.msc or:
net start cloudflared
```

---

## Part 7 — Blockchain on the server (optional)

For on-chain audit hashes in production demo:

**Terminal 1 — keep running:**

```powershell
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm install
npm run compile
npm run node
```

**Terminal 2 — after every node restart:**

```powershell
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm run deploy
```

Then:

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
php artisan config:clear
```

Verify: Admin → **Blockchain** → status **healthy**.

> Port **8545** stays local. Visitors only see transaction hashes stored in MySQL.

---

## Part 8 — Scheduled tasks

Blood unit expiry checks use Laravel’s scheduler. Add a Windows Task Scheduler job:

| Setting | Value |
|---------|--------|
| Trigger | Every 1 minute |
| Action | `C:\path\to\php.exe C:\Apache24\htdocs\bbbms-project\tarrlok\artisan schedule:run` |

---

## Part 9 — Verification checklist

Run through these before sharing the URL:

- [ ] `https://tarrlok.tesnet.xyz/up` → Laravel health OK
- [ ] `https://tarrlok.tesnet.xyz/login` → login page loads (no mixed HTTP assets)
- [ ] Admin login works (`TARRLOK_ADMIN_EMAIL` / password from `.env`)
- [ ] `https://tarrlok.tesnet.xyz/track/UNIT-002-00001` → public tracking (after seed)
- [ ] `pay.tesnet.xyz` still works (unchanged)
- [ ] `APP_DEBUG=false` in production `.env`
- [ ] Strong admin password set
- [ ] Tunnel service running
- [ ] Web server running (Apache or `artisan serve`)
- [ ] (Optional) Admin → Blockchain → **healthy**

---

## Part 10 — Using a brand-new domain instead

If Tarrlok should have its **own domain** (not under tesnet.xyz), use the dedicated guide:

**[DEPLOY-NEW-DOMAIN.md](./DEPLOY-NEW-DOMAIN.md)** — Namecheap purchase, new Cloudflare zone, new tunnel, and full server setup for e.g. `https://app.yourdomain.com`.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| **502 Bad Gateway** from Cloudflare | Web app not running on the port in `config.yml`; check `artisan serve` or Apache |
| **Redirect loop / HTTP links** | Set `APP_URL=https://tarrlok.tesnet.xyz`, run `php artisan config:clear` |
| **CSRF or session errors** | `SESSION_SECURE_COOKIE=true`; ensure HTTPS via Cloudflare |
| **Login fails** | Run `php artisan db:seed --class=AdminSeeder`; check MySQL credentials |
| **CSS/JS 404** | Wrong `APP_URL`; Apache must use `tarrlok/public` as DocumentRoot |
| **pay.tesnet.xyz broke** | Do not remove pay ingress rule; Tarrlok rule must not catch pay hostname |
| **Blockchain offline** | Start `npm run node`, then `npm run deploy`; `BLOCKCHAIN_ENABLED=true` |
| **DNS not resolving** | Wait for propagation; confirm CNAME points to `*.cfargotunnel.com` |
| **Namecheap still shows old DNS** | Nameservers must be Cloudflare’s custom DNS, not Namecheap BasicDNS |

---

## Quick reference — commands on the tesnet server

```powershell
# Deploy / update app
cd C:\Apache24\htdocs\bbbms-project\tarrlok
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache

# Start web (pick one)
php artisan serve --host=127.0.0.1 --port=8080

# Start tunnel
cloudflared tunnel run tesnet

# Blockchain (optional)
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm run node
# separate terminal:
npm run deploy
```

---

## Security notes (demo vs real deployment)

This guide targets **demo, pilot, and viva** on your tesnet server:

- Change default admin and demo passwords before sharing publicly.
- The Hardhat private key in `.env` is for **local dev only** — never use on a public mainnet.
- Back up MySQL regularly.
- For real hospital use, plan proper hosting, secrets management, backups, and compliance review.

---

## Related files in this repo

| File | Purpose |
|------|---------|
| [DEPLOY-LOCAL-CLOUDFLARE.md](./DEPLOY-LOCAL-CLOUDFLARE.md) | Shorter tunnel-focused guide |
| [deploy/cloudflared-tesnet.xyz.example.yml](../deploy/cloudflared-tesnet.xyz.example.yml) | Multi-host tunnel config (pay + tarrlok) |
| [deploy/cloudflared-config.example.yml](../deploy/cloudflared-config.example.yml) | Single-host tunnel config |
| [apache/README.md](../apache/README.md) | Local Apache dev on the server |
| [DOCKER.md](./DOCKER.md) | Docker alternative (not required for tesnet tunnel setup) |
