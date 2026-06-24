import { defineConfig } from "hardhat/config";

export default defineConfig({
  solidity: {
    version: "0.8.28",
  },
  networks: {
    ganache: {
      type: "http",
      chainType: "l1",
      url: "http://127.0.0.1:7545",
    },
  },
});
