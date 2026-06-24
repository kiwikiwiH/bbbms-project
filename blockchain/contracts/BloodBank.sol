// SPDX-License-Identifier: UNLICENSED
pragma solidity ^0.8.28;

contract BloodBank {
    enum BloodStatus {
        Collected,
        Tested,
        Stored,
        Reserved,
        Issued,
        Expired
    }

    struct BloodUnit {
        string unitId;
        string bloodGroup;
        BloodStatus status;
        address recordedBy;
        uint256 timestamp;
    }

    mapping(string => BloodUnit) private bloodUnits;
    string[] private unitIds;

    event DonationRecorded(
        string indexed unitId,
        string bloodGroup,
        address indexed recordedBy,
        uint256 timestamp
    );

    event StatusUpdated(
        string indexed unitId,
        BloodStatus status,
        address indexed updatedBy,
        uint256 timestamp
    );

    function recordDonation(
        string calldata unitId,
        string calldata bloodGroup
    ) external {
        require(bytes(unitId).length > 0, "unitId required");
        require(bytes(bloodGroup).length > 0, "bloodGroup required");
        require(bytes(bloodUnits[unitId].unitId).length == 0, "unit exists");

        bloodUnits[unitId] = BloodUnit({
            unitId: unitId,
            bloodGroup: bloodGroup,
            status: BloodStatus.Collected,
            recordedBy: msg.sender,
            timestamp: block.timestamp
        });

        unitIds.push(unitId);

        emit DonationRecorded(unitId, bloodGroup, msg.sender, block.timestamp);
    }

    function updateStatus(
        string calldata unitId,
        BloodStatus newStatus
    ) external {
        require(bytes(bloodUnits[unitId].unitId).length > 0, "unit not found");

        bloodUnits[unitId].status = newStatus;
        bloodUnits[unitId].timestamp = block.timestamp;

        emit StatusUpdated(unitId, newStatus, msg.sender, block.timestamp);
    }

    function getBloodUnit(
        string calldata unitId
    )
        external
        view
        returns (
            string memory id,
            string memory bloodGroup,
            BloodStatus status,
            address recordedBy,
            uint256 timestamp
        )
    {
        BloodUnit memory unit = bloodUnits[unitId];
        require(bytes(unit.unitId).length > 0, "unit not found");

        return (
            unit.unitId,
            unit.bloodGroup,
            unit.status,
            unit.recordedBy,
            unit.timestamp
        );
    }

    function getUnitCount() external view returns (uint256) {
        return unitIds.length;
    }
}
