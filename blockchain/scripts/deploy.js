import hre from "hardhat";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

async function main() {
  const BloodBank = await hre.ethers.getContractFactory("BloodBank");
  const bloodBank = await BloodBank.deploy();
  await bloodBank.waitForDeployment();

  const address = await bloodBank.getAddress();
  const artifact = await hre.artifacts.readArtifact("BloodBank");
  const outDir = path.join(__dirname, "..", "deployments");

  fs.mkdirSync(outDir, { recursive: true });
  fs.writeFileSync(
    path.join(outDir, "local.json"),
    JSON.stringify(
      {
        address,
        abi: artifact.abi,
        network: hre.network.name,
        deployedAt: new Date().toISOString(),
      },
      null,
      2
    )
  );

  console.log(`BloodBank deployed to ${address}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
