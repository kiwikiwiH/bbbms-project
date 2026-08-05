# Blockchain (Tarrlok)

Local Ethereum **audit log** for blood unit lifecycle events. The Laravel app calls this layer after saving data to MySQL.

The chain runs on **your server only** (`127.0.0.1:8545`). It is **not** exposed through Cloudflare Tunnel.

Stakeholders (admin, hospital, lab) are **consortium readers** of one permissioned chain — not separate Geth nodes. Each portal opens the same shared ledger: on-chain events, DB-vs-chain integrity alerts, and blocked write attempts.

## What gets anchored

| Laravel action | Contract method | On-chain event | DB column |
|----------------|-----------------|----------------|-----------|
| Lab registers unit | `registerUnit()` | `UnitRegistered` (+ actor, expiresAt) | `blockchain_register_tx` |
| Screening cleared/failed | `recordScreening()` | `UnitScreened` (+ actor) | `blockchain_screening_tx` |
| Partner issue | `recordIssue()` | `UnitIssued` (+ actor; rejects expired / uncleared) | `blockchain_issue_tx` |

The contract does **not** replace MySQL — it stores an **immutable event log** and enforces key lifecycle rules (no double-register, screening once, no transfer of expired/uncleared units).

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
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
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
tx hash → saved on blood_units → shown on trace / track / every portal ledger
       ↓ (on revert)
blockchain_tamper_attempts → visible to admin, hospital, and lab
```

## Verify it works

### Shared ledger (recommended)

1. Sign in as admin, hospital, or lab
2. Open **Blockchain** / **Network ledger**
3. Expect the same network activity, integrity alerts, and blocked attempts on every portal
4. Admin also keeps chain health, block number, contract address, and coverage meters

### Lab workflow

1. Register and screen a blood unit in the lab portal
2. Open **Trace Unit** or public `/track/{unitCode}`
3. Confirm **Blockchain verification** shows `0x…` hashes
4. Watch the Hardhat node terminal for mined transactions

### CLI health check

```bash
node scripts/chain-status.js
node scripts/read-ledger.js "{\"action\":\"ledger\",\"unitCodes\":[]}"
```

`chain-status.js` returns RPC reachability, block number, contract address, and errors. `read-ledger.js` returns decoded events and optional `getUnit` snapshots.

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

Project overview and FYP report: [../README.md](../README.md) · [../Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx](../Final_Year_Project_Report_Blockchain_based_blood_bank_management.docx)

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
| `scripts/read-ledger.js` | Shared event + `getUnit` reader for every portal |
| `deployments/local.json` | Contract address + ABI (generated) |
