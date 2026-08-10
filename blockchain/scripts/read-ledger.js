import { ethers } from "ethers";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const payload = JSON.parse(process.argv[2] || "{}");
const rpc = process.env.BLOCKCHAIN_RPC_URL || "http://127.0.0.1:8545";
const deploymentPath = path.join(__dirname, "..", "deployments", "local.json");
const screeningLabels = ["none", "pending", "cleared", "failed"];

function fail(error) {
  console.log(JSON.stringify({ ok: false, events: [], units: {}, error }));
  process.exit(0);
}

if (!fs.existsSync(deploymentPath)) {
  fail("Contract not deployed. Run: cd blockchain && npm run deploy");
}

const deployment = JSON.parse(fs.readFileSync(deploymentPath, "utf8"));
const provider = new ethers.JsonRpcProvider(rpc, undefined, { staticNetwork: true });
const contract = new ethers.Contract(deployment.address, deployment.abi, provider);

function toNumber(value) {
  if (value === null || value === undefined) {
    return null;
  }

  return typeof value === "bigint" ? Number(value) : Number(value);
}

function mapEvent(log) {
  const name = log.eventName;
  const args = log.args;
  const base = {
    name,
    unitCode: args.unitCode,
    actorId: toNumber(args.actorId),
    actorName: args.actorName,
    txHash: log.transactionHash,
    blockNumber: toNumber(log.blockNumber),
    timestamp: toNumber(args.timestamp),
  };

  if (name === "UnitRegistered") {
    return {
      ...base,
      hospitalId: toNumber(args.hospitalId),
      bloodGroup: args.bloodGroup,
      expiresAt: toNumber(args.expiresAt),
      label: "Registered unit",
    };
  }

  if (name === "UnitScreened") {
    return {
      ...base,
      status: args.status,
      label: `Screening ${args.status}`,
    };
  }

  if (name === "UnitIssued") {
    return {
      ...base,
      fromHospitalId: toNumber(args.fromHospitalId),
      toHospitalId: toNumber(args.toHospitalId),
      requestCode: args.requestCode,
      label: "Issued to partner",
    };
  }

  return { ...base, label: name };
}

async function readUnit(unitCode) {
  const result = await contract.getUnit(unitCode);

  return {
    exists: Boolean(result.exists),
    hospitalId: toNumber(result.hospitalId),
    bloodGroup: result.bloodGroup,
    expiresAt: toNumber(result.expiresAt),
    screening: toNumber(result.screening),
    screeningLabel: screeningLabels[toNumber(result.screening)] ?? "none",
  };
}

async function main() {
  if (payload.action === "getUnit") {
    if (!payload.unitCode) {
      fail("unitCode is required");
    }

    const unit = await readUnit(payload.unitCode);
    console.log(JSON.stringify({ ok: true, unit, events: [], units: { [payload.unitCode]: unit } }));
    return;
  }

  const [registered, screened, issued] = await Promise.all([
    contract.queryFilter(contract.filters.UnitRegistered()),
    contract.queryFilter(contract.filters.UnitScreened()),
    contract.queryFilter(contract.filters.UnitIssued()),
  ]);

  const events = [...registered, ...screened, ...issued]
    .sort((a, b) => {
      if (a.blockNumber !== b.blockNumber) {
        return a.blockNumber - b.blockNumber;
      }

      return (a.index ?? 0) - (b.index ?? 0);
    })
    .map(mapEvent)
    .reverse();

  const units = {};
  const unitCodes = Array.isArray(payload.unitCodes) ? payload.unitCodes : [];

  for (const unitCode of unitCodes) {
    if (!unitCode || units[unitCode]) {
      continue;
    }

    units[unitCode] = await readUnit(unitCode);
  }

  console.log(JSON.stringify({ ok: true, events, units, error: null }));
}

main().catch((error) => {
  fail(error.message || String(error));
});
