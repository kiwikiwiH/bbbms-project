# Blockchain (Tarrlok)

Local Ethereum **audit log** for blood unit lifecycle events. The Laravel app calls this layer after saving data to MySQL.

The chain runs on **your server only** (`127.0.0.1:8545`). It is **not** exposed through Cloudflare Tunnel — remote users see **tx hashes** stored in MySQL on trace/track pages and the admin blockchain dashboard.

## What gets anchored

| Laravel action | Contract method | On-chain event | DB column |
|----------------|-----------------|----------------|-----------|
| Lab registers unit | `registerUnit()` | `UnitRegistered` | `blockchain_register_tx` |
| Screening cleared/failed | `recordScreening()` | `UnitScreened` | `blockchain_screening_tx` |
| Partner issue | `recordIssue()` | `UnitIssued` | `blockchain_issue_tx` |

The contract does **not** replace MySQL — it stores an **immutable event log**.

## Prerequisites

- Node.js 18+
- Hardhat node running on port **8545**

## Setup

```bash
cd blockchain
npm install
npm run compile
```

**Terminal 1** — start chain (keep running):

```bash
npm run node
```

**Terminal 2** — deploy (once per node restart):

```bash
npm run deploy
```

Creates `deployments/local.json` with contract address and ABI.

> **Important:** Stopping `npm run node` wipes local chain state. Run `npm run deploy` again after every restart.

## Laravel configuration

In `tarrlok/.env`:

```env
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

Use the **first Hardhat account** private key (printed when `hardhat node` starts). Demo only — never use in production.

```bash
cd ../tarrlok
php artisan config:clear
```

If the chain is unavailable, Laravel continues normally — no tx hashes are recorded.

## How it works

```
Laravel Controller
       ↓
BlockchainService.php
       ↓
node scripts/anchor-event.js
       ↓
BloodBank.sol (ethers.js transaction)
       ↓
tx hash → saved on blood_units → shown on trace / track / admin pages
```

## Verify it works

### Admin dashboard (recommended)

1. Sign in as platform admin
2. Open **Blockchain** → `/admin/blockchain`
3. Expect **healthy** status, block number, contract address, anchor counts

### Lab workflow

1. Register and screen a blood unit in the lab portal
2. Open **Trace Unit** or public `/track/{unitCode}`
3. Confirm **Blockchain verification** shows `0x…` hashes
4. Watch the Hardhat node terminal for mined transactions

### CLI health check

```bash
node scripts/chain-status.js
```

Returns JSON with RPC reachability, block number, contract address, and errors (exits quickly if node is offline).

## Deployed server + Cloudflare Tunnel

| Component | Where it runs | Public? |
|-----------|---------------|---------|
| Laravel web app | Port 80 / 8080 | Yes (via tunnel) |
| Hardhat node | `127.0.0.1:8545` | No |
| MySQL | `127.0.0.1` | No |

On the server, keep **three** processes running for a full demo:

1. `npm run node` (+ `npm run deploy` after restarts)
2. Apache or `php artisan serve`
3. `cloudflared tunnel run …`

See [docs/DEPLOY-LOCAL-CLOUDFLARE.md](../docs/DEPLOY-LOCAL-CLOUDFLARE.md).

## Troubleshooting

| Issue | Fix |
|-------|-----|
| “Contract not deployed” | Run `npm run deploy` while node is running |
| No tx hashes in app | Check `BLOCKCHAIN_ENABLED=true` and node is on port 8545 |
| Connection refused | Start `npm run node` first |
| Admin shows **offline** | Node not running; `node scripts/chain-status.js` to confirm |
| Wrong owner / revert | Redeploy after restarting node; use matching private key |
| Old hashes after restart | Local chain was reset — re-run demo actions or explain in viva |

## Files

| File | Purpose |
|------|---------|
| `contracts/BloodBank.sol` | Smart contract (event emissions) |
| `scripts/deploy.js` | Deploy to local chain |
| `scripts/anchor-event.js` | Called by Laravel to send transactions |
| `scripts/chain-status.js` | Chain health probe for admin dashboard |
| `deployments/local.json` | Contract address + ABI (generated) |
