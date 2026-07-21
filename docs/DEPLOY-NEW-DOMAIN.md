# Tarrlok — Deploy on a new domain (Namecheap → Cloudflare → server)

Use this guide when Tarrlok gets its **own domain** — e.g. `tarrlokgh.com` or `bloodbank-demo.com` — instead of a subdomain of `tesnet.xyz`.

**Example public URL:** `https://app.tarrlokgh.com` or `https://tarrlokgh.com`

For deploying under **`tarrlok.tesnet.xyz`** on your existing tesnet server, see [DEPLOY-TESNET-FULL-GUIDE.md](./DEPLOY-TESNET-FULL-GUIDE.md).

---

## Architecture

```
Internet user
     │
     ▼
Cloudflare (your new domain — DNS + HTTPS)
     │
     ▼
cloudflared tunnel (on your server)
     │
     └── app.yourdomain.com  →  http://127.0.0.1:8080  (Tarrlok Laravel)
                                      │
                                      ├── MySQL (127.0.0.1)
                                      └── Hardhat (127.0.0.1:8545, optional)
```

This is **independent** of `tesnet.xyz`. You can run it on the same physical server or a different one.

---

## Part 1 — Buy the domain on Namecheap

1. Go to [namecheap.com](https://www.namecheap.com).
2. Search for your domain, e.g.:
   - `tarrlokgh.com`
   - `tarrlok-bloodbank.com`
   - `yourprojectname.com`
3. Add to cart → checkout → pay.
4. **Domain List** → **Manage** → confirm status is **Active**.

**Tips when choosing a name:**

| Choice | Example | Notes |
|--------|---------|-------|
| Root domain | `https://tarrlokgh.com` | Clean; use `@` DNS record |
| App subdomain | `https://app.tarrlokgh.com` | Keeps root free for a landing page later |
| www | `https://www.tarrlokgh.com` | Add a redirect rule in Cloudflare if you prefer non-www |

This guide uses **`app.yourdomain.com`** as the app hostname. Replace with your real domain everywhere.

---

## Part 2 — Add the domain to Cloudflare

Cloudflare manages DNS and works with Cloudflare Tunnel (free HTTPS, no port-forwarding).

### 2.1 Create the zone

1. Sign up / log in at [dash.cloudflare.com](https://dash.cloudflare.com).
2. **Add a site** → enter `yourdomain.com` → **Free** plan.
3. Cloudflare scans existing DNS (usually empty for a new domain).
4. Copy the **two nameservers** Cloudflare assigns, e.g.:
   - `lola.ns.cloudflare.com`
   - `nick.ns.cloudflare.com`

### 2.2 Point Namecheap to Cloudflare

1. Namecheap → **Domain List** → **Manage** → **Nameservers**.
2. Select **Custom DNS**.
3. Paste Cloudflare’s two nameservers → **Save**.
4. Back in Cloudflare → **Check nameservers**.

Propagation: **15 minutes to 48 hours** (often under 1 hour). Zone status must show **Active**.

### 2.2 SSL settings

Cloudflare dashboard → **SSL/TLS**:

| Setting | Value |
|---------|--------|
| Encryption mode | **Full** |
| Always Use HTTPS | **On** |
| Automatic HTTPS Rewrites | **On** |

---

## Part 3 — Cloudflare Tunnel (new tunnel for this domain)

You can use a **dedicated tunnel** for this project (recommended when the domain is separate from tesnet.xyz).

### 3.1 Install cloudflared on the server

Download: [Cloudflare Tunnel downloads](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/)

```powershell
winget install Cloudflare.cloudflared
cloudflared --version
```

### 3.2 Login and create a tunnel

```powershell
cloudflared tunnel login
```

Browser opens → select **`yourdomain.com`** → authorize.

```powershell
cloudflared tunnel create tarrlok
```

Note:

- **Tunnel name:** `tarrlok`
- **Tunnel UUID:** e.g. `a1b2c3d4-e5f6-...`
- **Credentials file:** `C:\Users\<YOU>\.cloudflared\<UUID>.json`

### 3.3 Route DNS to the tunnel

**App on subdomain (recommended):**

```powershell
cloudflared tunnel route dns tarrlok app.yourdomain.com
```

**App on root domain:**

```powershell
cloudflared tunnel route dns tarrlok yourdomain.com
cloudflared tunnel route dns tarrlok www.yourdomain.com
```

Cloudflare creates proxied CNAME records automatically.

### 3.4 Tunnel config file

Create or edit `C:\Users\<YOU>\.cloudflared\config.yml`:

**Subdomain example (`app.yourdomain.com`):**

```yaml
tunnel: YOUR_TUNNEL_UUID
credentials-file: C:\Users\YOUR_USER\.cloudflared\YOUR_TUNNEL_UUID.json

ingress:
  - hostname: app.yourdomain.com
    service: http://127.0.0.1:8080
  - service: http_status:404
```

**Root domain example:**

```yaml
tunnel: YOUR_TUNNEL_UUID
credentials-file: C:\Users\YOUR_USER\.cloudflared\YOUR_TUNNEL_UUID.json

ingress:
  - hostname: yourdomain.com
    service: http://127.0.0.1:8080
  - hostname: www.yourdomain.com
    service: http://127.0.0.1:8080
  - service: http_status:404
```

Copy the template from [`deploy/cloudflared-config.example.yml`](../deploy/cloudflared-config.example.yml) and replace hostname + UUID.

### 3.5 Run the tunnel

```powershell
cloudflared tunnel run tarrlok
```

**Run at boot (Windows service):**

```powershell
cloudflared service install
net start cloudflared
```

---

## Part 4 — Deploy Tarrlok on the server

Same server as tesnet.xyz or a new machine — steps are identical.

### 4.1 Prerequisites

| Software | Version |
|----------|---------|
| PHP | 8.3+ |
| Composer | latest |
| MySQL | 8.x |
| Node.js | 18+ (blockchain only) |
| Git | any |

### 4.2 Get the code

```powershell
cd C:\Apache24\htdocs
git clone <your-repo-url> bbbms-project
cd bbbms-project\tarrlok
```

Or copy the project folder to the server.

### 4.3 MySQL

```sql
CREATE DATABASE tarrlok CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'tarrlok'@'localhost' IDENTIFIED BY 'your-strong-password';
GRANT ALL PRIVILEGES ON tarrlok.* TO 'tarrlok'@'localhost';
FLUSH PRIVILEGES;
```

### 4.4 Production `.env`

```powershell
copy .env.example .env
notepad .env
```

**Must match your public URL exactly:**

```env
APP_NAME=Tarrlok
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tarrlok
DB_USERNAME=tarrlok
DB_PASSWORD=your-strong-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=sync
CACHE_STORE=database

TARRLOK_ADMIN_EMAIL=admin@tarrlok.gh
TARRLOK_ADMIN_PASSWORD=change-this-before-going-live
TARRLOK_ADMIN_NAME="Tarrlok Platform Admin"

BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

### 4.5 Bootstrap Laravel

```powershell
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4.6 Start the web app (tunnel origin)

```powershell
php artisan serve --host=127.0.0.1 --port=8080
```

Keep running, or install as a Windows service with NSSM.

**Apache alternative:** point a vhost `ServerName app.yourdomain.com` at `tarrlok/public` and set tunnel `service` to `http://127.0.0.1:80`.

---

## Part 5 — Blockchain (optional)

```powershell
# Terminal 1
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm install
npm run compile
npm run node

# Terminal 2 (after node starts)
npm run deploy
```

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
php artisan config:clear
```

Admin → **Blockchain** should show **healthy**. Port 8545 is never exposed publicly.

---

## Part 6 — Optional Cloudflare extras

### Redirect www → non-www (or vice versa)

**Rules** → **Redirect Rules** → e.g. `www.yourdomain.com/*` → `https://yourdomain.com/$1` (301).

### Email on your domain (optional)

Namecheap **Private Email** or Cloudflare **Email Routing** for addresses like `contact@yourdomain.com`. Not required for Tarrlok to run.

### Landing page on root, app on subdomain

| Hostname | Purpose | Tunnel target |
|----------|---------|---------------|
| `yourdomain.com` | Static landing / portfolio | `http://127.0.0.1:3000` or Pages |
| `app.yourdomain.com` | Tarrlok Laravel | `http://127.0.0.1:8080` |

Add both as separate `ingress` rules in `config.yml`.

---

## Part 7 — Verification checklist

- [ ] Namecheap nameservers → Cloudflare (zone **Active**)
- [ ] `cloudflared tunnel route dns` created CNAME for your hostname
- [ ] Tunnel running (`cloudflared tunnel run tarrlok`)
- [ ] `php artisan serve` (or Apache) running on port in config
- [ ] `APP_URL` matches `https://app.yourdomain.com` exactly
- [ ] `https://app.yourdomain.com/up` → OK
- [ ] `https://app.yourdomain.com/login` → loads with HTTPS assets
- [ ] Admin login works after `db:seed`
- [ ] `APP_DEBUG=false`, strong admin password
- [ ] (Optional) Blockchain healthy in admin

---

## Part 8 — New domain vs tesnet subdomain

| | **New domain** (this doc) | **tesnet.xyz subdomain** |
|--|---------------------------|---------------------------|
| URL example | `https://app.tarrlokgh.com` | `https://tarrlok.tesnet.xyz` |
| Namecheap | Buy new domain | Not needed |
| Cloudflare zone | New zone for your domain | Already have `tesnet.xyz` |
| Tunnel | New tunnel (or separate config) | Add hostname to existing tunnel |
| Branding | Own project identity | Under tesnet umbrella |
| Guide | This file | [DEPLOY-TESNET-FULL-GUIDE.md](./DEPLOY-TESNET-FULL-GUIDE.md) |

Both can run on the **same server** with **two tunnels** or one tunnel with hostnames from different zones (each zone needs its own CNAME to the tunnel).

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| DNS not resolving | Wait for propagation; confirm Namecheap uses Cloudflare nameservers |
| **525 / SSL errors** | Cloudflare SSL mode → **Full**; origin is HTTP on localhost |
| **502 Bad Gateway** | App not running on port 8080; check tunnel config |
| Redirect loop | `APP_URL` must be `https://` and match hostname exactly |
| Login fails | `php artisan db:seed --class=AdminSeeder` |
| Wrong site on root vs app | Separate `ingress` hostname rules for each host |
| Tunnel on wrong account | Re-run `cloudflared tunnel login` and select correct zone |

---

## Quick command reference

```powershell
# One-time tunnel
cloudflared tunnel login
cloudflared tunnel create tarrlok
cloudflared tunnel route dns tarrlok app.yourdomain.com

# Deploy app
cd C:\Apache24\htdocs\bbbms-project\tarrlok
composer install --no-dev
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache

# Run (keep open)
php artisan serve --host=127.0.0.1 --port=8080
cloudflared tunnel run tarrlok
```

---

## Security reminder

- Change all default passwords before sharing the URL publicly.
- Hardhat dev key in `.env` is for **demo only**.
- Back up MySQL regularly.
- This setup suits demo/pilot/viva — not a regulated hospital production deployment without further hardening.

---

## Related files

| File | Purpose |
|------|---------|
| [DEPLOY-TESNET-FULL-GUIDE.md](./DEPLOY-TESNET-FULL-GUIDE.md) | Subdomain on existing tesnet.xyz |
| [DEPLOY-LOCAL-CLOUDFLARE.md](./DEPLOY-LOCAL-CLOUDFLARE.md) | Shorter tunnel reference |
| [deploy/cloudflared-config.example.yml](../deploy/cloudflared-config.example.yml) | Single-host tunnel template |
