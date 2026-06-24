import json
from typing import Any

from web3 import Web3
from web3.exceptions import ContractLogicError, Web3Exception

from config import Config, CONTRACT_ARTIFACT


class BlockchainService:
    def __init__(self):
        self.rpc_url = Config.BLOCKCHAIN_RPC_URL
        self.private_key = Config.BLOCKCHAIN_PRIVATE_KEY
        self.contract_address = Config.BLOOD_BANK_CONTRACT_ADDRESS
        self.web3 = Web3(Web3.HTTPProvider(self.rpc_url))
        self.contract = None

        if self.contract_address and CONTRACT_ARTIFACT.exists():
            with CONTRACT_ARTIFACT.open("r", encoding="utf-8") as artifact_file:
                artifact = json.load(artifact_file)
            self.contract = self.web3.eth.contract(
                address=Web3.to_checksum_address(self.contract_address),
                abi=artifact["abi"],
            )

    def status(self) -> dict[str, Any]:
        try:
            connected = self.web3.is_connected()
            block_number = self.web3.eth.block_number if connected else None
            return {
                "connected": connected,
                "rpc_url": self.rpc_url,
                "block_number": block_number,
                "contract_configured": bool(self.contract),
                "contract_address": self.contract_address or None,
            }
        except Web3Exception as exc:
            return {
                "connected": False,
                "rpc_url": self.rpc_url,
                "error": str(exc),
                "contract_configured": bool(self.contract),
            }

    def record_donation(self, unit_id: str, blood_group: str) -> dict[str, Any]:
        if not self.contract:
            raise RuntimeError(
                "BloodBank contract is not configured. Set BLOOD_BANK_CONTRACT_ADDRESS in .env"
            )
        if not self.private_key:
            raise RuntimeError(
                "BLOCKCHAIN_PRIVATE_KEY is missing. Add a Ganache account private key to .env"
            )

        account = self.web3.eth.account.from_key(self.private_key)
        nonce = self.web3.eth.get_transaction_count(account.address)

        transaction = self.contract.functions.recordDonation(
            unit_id, blood_group
        ).build_transaction(
            {
                "from": account.address,
                "nonce": nonce,
                "gas": 500000,
                "gasPrice": self.web3.eth.gas_price,
                "chainId": self.web3.eth.chain_id,
            }
        )

        signed = self.web3.eth.account.sign_transaction(
            transaction, private_key=self.private_key
        )
        tx_hash = self.web3.eth.send_raw_transaction(signed.raw_transaction)
        receipt = self.web3.eth.wait_for_transaction_receipt(tx_hash)

        return {
            "transaction_hash": receipt.transactionHash.hex(),
            "block_number": receipt.blockNumber,
            "unit_id": unit_id,
            "blood_group": blood_group,
        }

    def get_blood_unit(self, unit_id: str) -> dict[str, Any]:
        if not self.contract:
            raise RuntimeError("BloodBank contract is not configured")

        try:
            unit = self.contract.functions.getBloodUnit(unit_id).call()
            status_names = [
                "collected",
                "tested",
                "stored",
                "reserved",
                "issued",
                "expired",
            ]
            return {
                "unit_id": unit[0],
                "blood_group": unit[1],
                "status": status_names[unit[2]],
                "recorded_by": unit[3],
                "timestamp": unit[4],
            }
        except ContractLogicError as exc:
            raise RuntimeError(str(exc)) from exc
