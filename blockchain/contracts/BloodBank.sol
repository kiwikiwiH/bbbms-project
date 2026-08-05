// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title BloodBank
 * @notice Immutable audit log + lifecycle guards for Tarrlok blood units.
 * @dev Laravel remains the operational database. This contract:
 *      - records who performed each anchored action
 *      - rejects invalid status transitions
 *      - rejects issuing expired units
 */
contract BloodBank {
    address public owner;

    enum ScreeningStatus {
        None,
        Pending,
        Cleared,
        Failed
    }

    struct UnitRecord {
        bool exists;
        uint256 hospitalId;
        string bloodGroup;
        uint256 expiresAt;
        ScreeningStatus screening;
    }

    mapping(bytes32 => UnitRecord) public units;

    event UnitRegistered(
        bytes32 indexed unitHash,
        string unitCode,
        uint256 hospitalId,
        string bloodGroup,
        uint256 expiresAt,
        uint256 actorId,
        string actorName,
        uint256 timestamp
    );

    event UnitScreened(
        bytes32 indexed unitHash,
        string unitCode,
        string status,
        uint256 actorId,
        string actorName,
        uint256 timestamp
    );

    event UnitIssued(
        bytes32 indexed unitHash,
        string unitCode,
        uint256 fromHospitalId,
        uint256 toHospitalId,
        string requestCode,
        uint256 actorId,
        string actorName,
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
        string calldata bloodGroup,
        uint256 expiresAt,
        uint256 actorId,
        string calldata actorName
    ) external onlyOwner {
        bytes32 unitHash = keccak256(bytes(unitCode));
        require(!units[unitHash].exists, "BloodBank: already registered");
        require(hospitalId > 0, "BloodBank: invalid hospital");
        require(bytes(bloodGroup).length > 0, "BloodBank: blood group required");
        require(expiresAt > block.timestamp, "BloodBank: already expired");
        require(bytes(actorName).length > 0, "BloodBank: actor required");

        units[unitHash] = UnitRecord({
            exists: true,
            hospitalId: hospitalId,
            bloodGroup: bloodGroup,
            expiresAt: expiresAt,
            screening: ScreeningStatus.Pending
        });

        emit UnitRegistered(
            unitHash,
            unitCode,
            hospitalId,
            bloodGroup,
            expiresAt,
            actorId,
            actorName,
            block.timestamp
        );
    }

    function recordScreening(
        string calldata unitCode,
        string calldata status,
        uint256 actorId,
        string calldata actorName
    ) external onlyOwner {
        bytes32 unitHash = keccak256(bytes(unitCode));
        UnitRecord storage unit = units[unitHash];

        require(unit.exists, "BloodBank: unknown unit");
        require(unit.screening == ScreeningStatus.Pending, "BloodBank: screening already set");
        require(bytes(actorName).length > 0, "BloodBank: actor required");

        ScreeningStatus nextStatus;
        if (keccak256(bytes(status)) == keccak256(bytes("cleared"))) {
            nextStatus = ScreeningStatus.Cleared;
        } else if (keccak256(bytes(status)) == keccak256(bytes("failed"))) {
            nextStatus = ScreeningStatus.Failed;
        } else {
            revert("BloodBank: invalid screening status");
        }

        unit.screening = nextStatus;

        emit UnitScreened(unitHash, unitCode, status, actorId, actorName, block.timestamp);
    }

    function recordIssue(
        string calldata unitCode,
        uint256 fromHospitalId,
        uint256 toHospitalId,
        string calldata requestCode,
        uint256 actorId,
        string calldata actorName
    ) external onlyOwner {
        bytes32 unitHash = keccak256(bytes(unitCode));
        UnitRecord storage unit = units[unitHash];

        require(unit.exists, "BloodBank: unknown unit");
        require(unit.screening == ScreeningStatus.Cleared, "BloodBank: not cleared");
        require(block.timestamp <= unit.expiresAt, "BloodBank: unit expired");
        require(fromHospitalId == unit.hospitalId, "BloodBank: wrong owner hospital");
        require(toHospitalId > 0 && toHospitalId != fromHospitalId, "BloodBank: invalid transfer");
        require(bytes(requestCode).length > 0, "BloodBank: request required");
        require(bytes(actorName).length > 0, "BloodBank: actor required");

        unit.hospitalId = toHospitalId;

        emit UnitIssued(
            unitHash,
            unitCode,
            fromHospitalId,
            toHospitalId,
            requestCode,
            actorId,
            actorName,
            block.timestamp
        );
    }

    function getUnit(string calldata unitCode)
        external
        view
        returns (
            bool exists,
            uint256 hospitalId,
            string memory bloodGroup,
            uint256 expiresAt,
            ScreeningStatus screening
        )
    {
        UnitRecord storage unit = units[keccak256(bytes(unitCode))];
        return (unit.exists, unit.hospitalId, unit.bloodGroup, unit.expiresAt, unit.screening);
    }
}
