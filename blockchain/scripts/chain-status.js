import { ethers } from "ethers";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rpc = process.env.BLOCKCHAIN_RPC_URL || "http://127.0.0.1:8545";
const privateKey = process.env.BLOCKCHAIN_PRIVATE_KEY;
const deploymentPath = path.join(__dirname, "..", "deployments", "local.json");
const RPC_TIMEOUT_MS = 5000;

async function rpcCall(method, params = []) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), RPC_TIMEOUT_MS);

  try {
    const response = await fetch(rpc, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ jsonrpc: "2.0", id: 1, method, params }),
      signal: controller.signal,
    });

    const payload = await response.json();

    if (payload.error) {
      throw new Error(payload.error.message || "RPC error");
    }

    return payload.result;
  } catch (error) {
    if (error.name === "AbortError") {
      throw new Error("RPC timeout — start the node with: cd blockchain && npm run node");
    }

    throw error;
  } finally {
    clearTimeout(timeout);
  }
}

async function main() {
  const result = {
    ok: false,
    rpcUrl: rpc,
    rpcReachable: false,
    blockNumber: null,
    chainId: null,
    contractDeployed: false,
    contractAddress: null,
    contractOwner: null,
    signerAddress: null,
    signerBalanceEth: null,
    deployedAt: null,
    errors: [],
  };

  try {
    const blockHex = await rpcCall("eth_blockNumber");
    const chainHex = await rpcCall("eth_chainId");

    result.rpcReachable = true;
    result.blockNumber = Number.parseInt(blockHex, 16);
    result.chainId = Number.parseInt(chainHex, 16);

    if (fs.existsSync(deploymentPath)) {
      const deployment = JSON.parse(fs.readFileSync(deploymentPath, "utf8"));
      result.contractDeployed = true;
      result.contractAddress = deployment.address;
      result.deployedAt = deployment.deployedAt ?? null;

      const iface = new ethers.Interface(deployment.abi);
      const data = iface.encodeFunctionData("owner", []);
      const ownerHex = await rpcCall("eth_call", [
        { to: deployment.address, data },
        "latest",
      ]);
      result.contractOwner = iface.decodeFunctionResult("owner", ownerHex)[0];
    } else {
      result.errors.push("Contract not deployed. Run: cd blockchain && npm run deploy");
    }

    if (privateKey) {
      const wallet = new ethers.Wallet(privateKey);
      result.signerAddress = wallet.address;
      const balanceHex = await rpcCall("eth_getBalance", [wallet.address, "latest"]);
      result.signerBalanceEth = ethers.formatEther(balanceHex);
    } else {
      result.errors.push("BLOCKCHAIN_PRIVATE_KEY is not set in .env");
    }

    result.ok =
      result.rpcReachable &&
      result.contractDeployed &&
      Boolean(privateKey) &&
      result.errors.length === 0;
  } catch (error) {
    result.errors.push(error.message || String(error));
  }

  console.log(JSON.stringify(result));
}

main().finally(() => process.exit(0));
