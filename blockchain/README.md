# Blockchain (Tarrlok)

Local Ethereum **audit log** for blood unit lifecycle events. The Laravel app calls this layer after saving data to MySQL.

## What gets anchored

| Laravel action | Contract method | On-chain event |
|----------------|-----------------|----------------|
| Lab registers unit | `registerUnit()` | `UnitRegistered` |
| Screening cleared/failed | `recordScreening()` | `UnitScreened` |
| Partner issue | `recordIssue()` | `UnitIssued` |

The contract does **not** replace MySQL — it stores an **immutable event log**. Tx hashes are saved on `blood_units` and shown on the trace page.

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

## Laravel configuration

In `tarrlok/.env`:

```env
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d3255bf239959da31d71ebff6b2c5c3f809b40
```

Use the **first Hardhat account** private key (printed when `hardhat node` starts).

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
tx hash → saved on blood_units → shown on Trace page
```

## Verify it works

1. Register and screen a blood unit in the lab portal
2. Open **Trace Unit** and search the unit code
3. Confirm **Blockchain audit trail** shows `0x…` transaction hashes
4. Watch the Hardhat node terminal for mined transactions

## Troubleshooting

| Issue | Fix |
|-------|-----|
| “Contract not deployed” | Run `npm run deploy` while node is running |
| No tx hashes in app | Check `BLOCKCHAIN_ENABLED=true` and node is on port 8545 |
| Connection refused | Start `npm run node` first |
| Wrong owner / revert | Redeploy after restarting node; use matching private key |

## Files

| File | Purpose |
|------|---------|
| `contracts/BloodBank.sol` | Smart contract (event emissions) |
| `scripts/deploy.js` | Deploy to local chain |
| `scripts/anchor-event.js` | Called by Laravel to send transactions |
| `deployments/local.json` | Contract address + ABI (generated) |
