# Apache dev setup (one-time, already applied on this machine)

Config files live under `C:\Apache24\conf\extra\`.

## How it works

| URL | Serves |
|-----|--------|
| `http://<folder>.localhost` | `C:\Apache24\htdocs\<folder>\public` |
| `http://pay.tesnet.xyz` | hotspot-pay (unchanged) |
| `http://pay.localhost` | hotspot-pay |

**New Laravel project:** put it in `C:\Apache24\htdocs\my-app\` (with `public/` inside), open `http://my-app.localhost`, set `APP_URL` in `.env`. No Apache edits.

## Tarrlok

- Folder: `bbbms-project\tarrlok` (also linked as `htdocs\tarrlok`)
- Local dev: **http://tarrlok.localhost**
- Public (Cloudflare): **https://tarrlok.tesnet.xyz** — see [docs/DEPLOY-LOCAL-CLOUDFLARE.md](../docs/DEPLOY-LOCAL-CLOUDFLARE.md)
- `.env`: `APP_URL=https://tarrlok.tesnet.xyz` when tunneled (or `http://tarrlok.localhost` for local only)

## After editing Apache config

Restart Apache **as Administrator** (Services → Apache2.4 → Restart, or Apache Monitor).

```powershell
C:\Apache24\bin\httpd.exe -t
```

## hotspot-pay note

`localhost` no longer points at hotspot-pay. Use **http://pay.tesnet.xyz** (add `127.0.0.1 pay.tesnet.xyz` to `C:\Windows\System32\drivers\etc\hosts` if needed) or **http://pay.localhost**.
