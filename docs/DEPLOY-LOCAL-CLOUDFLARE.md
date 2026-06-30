# Deploy Tarrlok on a local server + Cloudflare Tunnel

Expose your **local** Tarrlok install to the internet via [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/) (`cloudflared`). No port-forwarding on your router.

**Already have `tesnet.xyz` on Cloudflare?** Use **`tarrlok.tesnet.xyz`** — see [Tesnet.xyz quick setup](#tesnetxyz-quick-setup-paytesnetxyz--tarrloktesnetxyz) below.

---

## What you need

| Item | Notes |
|------|--------|
| **Server** | Windows PC with PHP 8.3+, Composer, MySQL (or SQLite for demos) |
| **Domain on Cloudflare** | e.g. `tesnet.xyz` — DNS managed by Cloudflare |
| **cloudflared** | [Install](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/) on the same machine as Tarrlok |
| **Project** | Clone or copy `bbbms-project` to the server |

Blockchain (optional): Hardhat node + deploy on the **same server** if you want on-chain hashes through the tunnel. The tunnel only exposes the Laravel app, not port 8545.

---

## Tesnet.xyz quick setup (`pay.tesnet.xyz` + `tarrlok.tesnet.xyz`)

You already run **`tesnet.xyz`** on this server (e.g. `pay.tesnet.xyz` for hotspot-pay). Add Tarrlok as a **second hostname** on the same tunnel.

| Public URL | Serves |
|------------|--------|
| `https://pay.tesnet.xyz` | hotspot-pay (unchanged) |
| `https://tarrlok.tesnet.xyz` | Tarrlok Laravel app |

### Step 1 — Laravel on the server

Project is already at `C:\Apache24\htdocs\bbbms-project\tarrlok`. In `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tarrlok.tesnet.xyz

SESSION_SECURE_COOKIE=true
```

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
composer install --no-dev
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
```

### Step 2 — Run the web app locally

**Easiest (recommended first):**

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
php artisan serve --host=127.0.0.1 --port=8080
```

Keep this window open. Tunnel will point to `http://127.0.0.1:8080`.

**Or Apache:** add vhost for `tarrlok.tesnet.xyz` (section 5) and tunnel to `http://127.0.0.1:80`.

### Step 3 — DNS for the new subdomain

If you use an **existing** named tunnel:

```powershell
cloudflared tunnel route dns YOUR_TUNNEL_NAME tarrlok.tesnet.xyz
```

Or in **Cloudflare Dashboard** → DNS → Add record:

- Type: `CNAME`
- Name: `tarrlok`
- Target: `<your-tunnel-id>.cfargotunnel.com` (same pattern as `pay`)

### Step 4 — Update cloudflared config

Edit `C:\Users\<you>\.cloudflared\config.yml` — add Tarrlok **above** the catch-all `404` rule.

Example with both apps: **[deploy/cloudflared-tesnet.xyz.example.yml](../deploy/cloudflared-tesnet.xyz.example.yml)**

```yaml
ingress:
  - hostname: pay.tesnet.xyz
    service: http://127.0.0.1:80
  - hostname: tarrlok.tesnet.xyz
    service: http://127.0.0.1:8080
  - service: http_status:404
```

Restart the tunnel:

```powershell
cloudflared tunnel run YOUR_TUNNEL_NAME
```

### Step 5 — Test

1. `https://tarrlok.tesnet.xyz/up` → Laravel health OK  
2. `https://tarrlok.tesnet.xyz/login` → Tarrlok login  
3. `https://tarrlok.tesnet.xyz/track` → public donor tracking  

### Blockchain (same server, not public)

```powershell
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm run node
# new terminal:
npm run deploy
```

Set `BLOCKCHAIN_ENABLED=true` in `tarrlok/.env`, then `php artisan config:clear`.  
Admin: `https://tarrlok.tesnet.xyz/admin/blockchain`

---

## 1. Put the project on the server

### Option A — Git (recommended)

```powershell
cd C:\Apache24\htdocs
git clone <your-repo-url> bbbms-project
cd bbbms-project\tarrlok
```

### Option B — Copy folder

Copy the whole `bbbms-project` folder to e.g. `C:\Apache24\htdocs\bbbms-project`.

---

## 2. Laravel setup

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok

composer install --no-dev --optimize-autoloader
copy .env.example .env
php artisan key:generate
```

### `.env` for tunneled production

Replace values with your tunnel hostname (example: `tarrlok.tesnet.xyz`):

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
DB_PASSWORD=your-strong-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

# Admin (then: php artisan db:seed --class=AdminSeeder)
TARRLOK_ADMIN_EMAIL=admin@tarrlok.gh
TARRLOK_ADMIN_PASSWORD=change-this-password

# Optional blockchain on same machine
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

```powershell
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Create MySQL database/user first if using MySQL.

---

## 3. Run the web app locally

Pick **one** of these on the server.

### A) Apache (already configured on this machine)

- Project path: `C:\Apache24\htdocs\bbbms-project\tarrlok`
- Local URL: `http://tarrlok.localhost` (see `apache/README.md`)
- Tunnel target: `http://127.0.0.1:80`
- Add a vhost for your public hostname if Apache must match `Host:` (see section 5).

### B) PHP built-in server (simplest for tunnel testing)

```powershell
cd C:\Apache24\htdocs\bbbms-project\tarrlok
php artisan serve --host=127.0.0.1 --port=8080
```

- Tunnel target: `http://127.0.0.1:8080`

Keep this window open (or run as a Windows service / NSSM).

---

## 4. Cloudflare Tunnel

### One-time: login and create tunnel

```powershell
cloudflared tunnel login
cloudflared tunnel create tarrlok
```

Note the tunnel UUID and credentials file path.

### DNS (Cloudflare dashboard or CLI)

```powershell
cloudflared tunnel route dns tarrlok tarrlok.tesnet.xyz
```

### Config file

Copy `deploy/cloudflared-config.example.yml` to e.g.  
`C:\Users\<you>\.cloudflared\config.yml` and set:

- `tunnel:` your tunnel ID  
- `credentials-file:` path from `tunnel create`  
- `hostname:` your subdomain  
- `service:` `http://127.0.0.1:8080` (artisan) **or** `http://127.0.0.1:80` (Apache)

### Run tunnel

```powershell
cloudflared tunnel run tarrlok
```

Or install as a Windows service:

```powershell
cloudflared service install
```

### Quick test (no named tunnel)

Temporary public URL (good for a 2-minute test only):

```powershell
cloudflared tunnel --url http://127.0.0.1:8080
```

Update `APP_URL` to the `*.trycloudflare.com` URL, then `php artisan config:clear`.

---

## 5. Apache + custom hostname (if using port 80)

If you use Apache instead of `artisan serve`, add a vhost so the tunnel hostname reaches Laravel’s `public` folder. Example (adjust paths):

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

Restart Apache, then point cloudflared `service` to `http://127.0.0.1:80`.

---

## 6. Blockchain on the server (optional)

Same machine as Laravel:

**Terminal 1**

```powershell
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm install
npm run node
```

**Terminal 2** (after each node restart)

```powershell
cd C:\Apache24\htdocs\bbbms-project\blockchain
npm run deploy
```

Admin → **Blockchain** should show **healthy**. Visitors through the tunnel see tx hashes on trace/track pages; they do not connect to port 8545 directly.

---

## 7. Daily jobs (expiry)

Windows Task Scheduler — run every minute:

```text
php C:\Apache24\htdocs\bbbms-project\tarrlok\artisan schedule:run
```

Notifications use `QUEUE_CONNECTION=sync` by default (no separate queue worker required).

---

## 8. Checklist before sharing the URL

- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` matches `https://your-subdomain.domain`
- [ ] Strong admin password (`AdminSeeder` after `.env` change)
- [ ] Tunnel running (`cloudflared tunnel run tarrlok`)
- [ ] Web server running (Apache or `artisan serve`)
- [ ] `https://your-subdomain/up` returns OK (Laravel health)
- [ ] Login and one full demo flow work over HTTPS

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Redirect loop or HTTP links | Set `APP_URL=https://...`, `php artisan config:clear` |
| 502 from Cloudflare | Web app not running on the port in cloudflared config |
| CSRF / session errors | `SESSION_SECURE_COOKIE=true`, trust proxies (already in app) |
| Assets 404 | `APP_URL` wrong; run from `public/` if using Apache |
| No blockchain hashes | Node not running on server; `BLOCKCHAIN_ENABLED=true` |

---

## Security note

This setup is suitable for **demo / pilot / viva**. A real hospital deployment needs hardened secrets, backups, HTTPS-only admin, and a proper hosting model — not a dev Hardhat key on a tunneled PC.
