import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { ethers } from "ethers";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const artifactPath = path.join(
  __dirname,
  "..",
  "artifacts",
  "contracts",
  "BloodBank.sol",
  "BloodBank.json"
);

const RPC_URL = process.env.BLOCKCHAIN_RPC_URL || "http://127.0.0.1:7545";
const PRIVATE_KEY =
  process.env.BLOCKCHAIN_PRIVATE_KEY ||
  "0x4f3edf983ac636a65a842ce7c0d64b5cfe4c77a2b95447116187a3a0f24b7783";

async function main() {
  const artifact = JSON.parse(fs.readFileSync(artifactPath, "utf8"));
  const provider = new ethers.JsonRpcProvider(RPC_URL);
  const wallet = new ethers.Wallet(PRIVATE_KEY, provider);

  console.log("Deploying BloodBank with account:", wallet.address);

  const factory = new ethers.ContractFactory(
    artifact.abi,
    artifact.bytecode,
    wallet
  );

  const contract = await factory.deploy();
  await contract.waitForDeployment();
  const address = await contract.getAddress();

  console.log("\nBloodBank deployed to:", address);
  console.log("\nAdd this to backend/.env:");
  console.log(`BLOOD_BANK_CONTRACT_ADDRESS=${address}`);
  console.log(`BLOCKCHAIN_PRIVATE_KEY=${PRIVATE_KEY}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
