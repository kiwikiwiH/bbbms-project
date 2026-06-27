import { ethers } from "ethers";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const payload = JSON.parse(process.argv[2] || "{}");
const rpc = process.env.BLOCKCHAIN_RPC_URL || "http://127.0.0.1:8545";
const privateKey = process.env.BLOCKCHAIN_PRIVATE_KEY;
const deploymentPath = path.join(__dirname, "..", "deployments", "local.json");

if (!privateKey) {
  console.log(JSON.stringify({ ok: false, error: "BLOCKCHAIN_PRIVATE_KEY is not set" }));
  process.exit(0);
}

if (!fs.existsSync(deploymentPath)) {
  console.log(JSON.stringify({ ok: false, error: "Contract not deployed. Run: npm run deploy:local" }));
  process.exit(0);
}

const deployment = JSON.parse(fs.readFileSync(deploymentPath, "utf8"));
const provider = new ethers.JsonRpcProvider(rpc);
const wallet = new ethers.Wallet(privateKey, provider);
const contract = new ethers.Contract(deployment.address, deployment.abi, wallet);

async function main() {
  let tx;

  switch (payload.action) {
    case "registerUnit":
      tx = await contract.registerUnit(payload.unitCode, payload.hospitalId, payload.bloodGroup);
      break;
    case "recordScreening":
      tx = await contract.recordScreening(payload.unitCode, payload.status);
      break;
    case "recordIssue":
      tx = await contract.recordIssue(
        payload.unitCode,
        payload.fromHospitalId,
        payload.toHospitalId,
        payload.requestCode
      );
      break;
    default:
      throw new Error(`Unknown action: ${payload.action}`);
  }

  const receipt = await tx.wait();
  console.log(JSON.stringify({ ok: true, txHash: receipt.hash }));
}

main().catch((error) => {
  console.log(JSON.stringify({ ok: false, error: error.message }));
  process.exit(1);
});
