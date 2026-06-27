// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title BloodBank
 * @notice Immutable audit log for Tarrlok blood unit lifecycle events.
 */
contract BloodBank {
    address public owner;

    event UnitRegistered(
        bytes32 indexed unitHash,
        string unitCode,
        uint256 hospitalId,
        string bloodGroup,
        uint256 timestamp
    );

    event UnitScreened(
        bytes32 indexed unitHash,
        string unitCode,
        string status,
        uint256 timestamp
    );

    event UnitIssued(
        bytes32 indexed unitHash,
        string unitCode,
        uint256 fromHospitalId,
        uint256 toHospitalId,
        string requestCode,
        uint256 timestamp
    );

    modifier onlyOwner() {
        require(msg.sender == owner, "BloodBank: not owner");
        _;
    }

    constructor() {
        owner = msg.sender;
    }

    function registerUnit(
        string calldata unitCode,
        uint256 hospitalId,
        string calldata bloodGroup
    ) external onlyOwner {
        emit UnitRegistered(
            keccak256(bytes(unitCode)),
            unitCode,
            hospitalId,
            bloodGroup,
            block.timestamp
        );
    }

    function recordScreening(string calldata unitCode, string calldata status) external onlyOwner {
        emit UnitScreened(keccak256(bytes(unitCode)), unitCode, status, block.timestamp);
    }

    function recordIssue(
        string calldata unitCode,
        uint256 fromHospitalId,
        uint256 toHospitalId,
        string calldata requestCode
    ) external onlyOwner {
        emit UnitIssued(
            keccak256(bytes(unitCode)),
            unitCode,
            fromHospitalId,
            toHospitalId,
            requestCode,
            block.timestamp
        );
    }
}
