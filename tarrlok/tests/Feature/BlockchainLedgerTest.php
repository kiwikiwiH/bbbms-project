<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
use App\Models\Donor;
use App\Models\Hospital;
use App\Models\User;
use App\Services\BlockchainIntegrityService;
use App\Services\BlockchainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BlockchainLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_ledger_routes(): void
    {
        $this->get('/hospital/blockchain')->assertRedirect('/login');
        $this->get('/lab/blockchain')->assertRedirect('/login');
        $this->get('/admin/blockchain')->assertRedirect('/login');
    }

    public function test_hospital_lab_and_admin_can_open_role_scoped_ledgers(): void
    {
        [$hospitalUser, $labUser, $admin] = $this->networkUsers();

        $this->actingAs($hospitalUser)
            ->get('/hospital/blockchain')
            ->assertOk()
            ->assertSee('Unit audit trail')
            ->assertSee('Your unit activity')
            ->assertSee('Integrity alerts')
            ->assertSee('Blocked attempts');

        $this->actingAs($labUser)
            ->get('/lab/blockchain')
            ->assertOk()
            ->assertSee('Facility ledger')
            ->assertSee('Facility activity');

        $this->actingAs($admin)
            ->get('/admin/blockchain')
            ->assertOk()
            ->assertSee('Blockchain audit trail')
            ->assertSee('Unit history search')
            ->assertSee('Network activity');
    }

    public function test_hospital_ledger_hides_other_hospital_chain_events(): void
    {
        [$hospitalUser, , $admin, $otherHospital] = $this->networkUsers(withSecondHospital: true);

        config([
            'blockchain.enabled' => true,
            'blockchain.private_key' => '0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80',
        ]);

        Process::fake([
            '*' => Process::result(
                output: json_encode([
                    'ok' => true,
                    'events' => [
                        [
                            'name' => 'UnitRegistered',
                            'label' => 'Registered unit',
                            'unitCode' => 'UNIT-001-00001',
                            'hospitalId' => $hospitalUser->hospital_id,
                            'actorName' => 'Local Lab',
                            'txHash' => '0x'.str_repeat('1', 64),
                            'blockNumber' => 12,
                            'timestamp' => now()->subHour()->getTimestamp(),
                        ],
                        [
                            'name' => 'UnitRegistered',
                            'label' => 'Registered unit',
                            'unitCode' => 'UNIT-999-00001',
                            'hospitalId' => $otherHospital->id,
                            'actorName' => 'Other Lab',
                            'txHash' => '0x'.str_repeat('2', 64),
                            'blockNumber' => 13,
                            'timestamp' => now()->getTimestamp(),
                        ],
                    ],
                    'units' => [],
                    'error' => null,
                ]),
            ),
        ]);

        BloodUnit::create([
            'hospital_id' => $hospitalUser->hospital_id,
            'unit_code' => 'UNIT-001-00001',
            'blood_group' => 'O+',
            'status' => 'available',
            'screening_status' => 'cleared',
            'recorded_by' => $hospitalUser->id,
            'collected_at' => now()->subDays(2),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($hospitalUser)
            ->get('/hospital/blockchain')
            ->assertOk()
            ->assertSee('UNIT-001-00001')
            ->assertSee('Block 12')
            ->assertDontSee('UNIT-999-00001')
            ->assertDontSee('Other Lab');

        $this->actingAs($admin)
            ->get('/admin/blockchain')
            ->assertOk()
            ->assertSee('UNIT-001-00001')
            ->assertSee('UNIT-999-00001')
            ->assertSee('Block 13');
    }

    public function test_admin_unit_history_search_shows_block_and_tx_trail(): void
    {
        [, , $admin] = $this->networkUsers();

        config([
            'blockchain.enabled' => true,
            'blockchain.private_key' => '0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80',
        ]);

        Process::fake(function () {
            static $call = 0;
            $call++;

            if ($call === 1) {
                return Process::result(
                    output: json_encode([
                        'ok' => true,
                        'rpcUrl' => 'http://127.0.0.1:8545',
                        'rpcReachable' => true,
                        'blockNumber' => 42,
                        'chainId' => 31337,
                        'contractDeployed' => true,
                        'contractAddress' => '0x'.str_repeat('0', 40),
                        'contractOwner' => '0x'.str_repeat('1', 40),
                        'signerAddress' => '0x'.str_repeat('2', 40),
                        'signerBalanceEth' => '10000',
                        'deployedAt' => null,
                        'errors' => [],
                    ]),
                );
            }

            return Process::result(
                output: json_encode([
                    'ok' => true,
                    'events' => [
                        [
                            'name' => 'UnitRegistered',
                            'label' => 'Registered unit',
                            'unitCode' => 'UNIT-001-00007',
                            'hospitalId' => 1,
                            'actorName' => 'Lab',
                            'txHash' => '0x'.str_repeat('ab', 32),
                            'blockNumber' => 42,
                            'timestamp' => now()->getTimestamp(),
                        ],
                    ],
                    'units' => [
                        'UNIT-001-00007' => [
                            'exists' => true,
                            'bloodGroup' => 'A+',
                            'screeningLabel' => 'none',
                        ],
                    ],
                    'error' => null,
                ]),
            );
        });

        $this->actingAs($admin)
            ->get('/admin/blockchain?unit=UNIT-001-00007')
            ->assertOk()
            ->assertSee('Unit history search')
            ->assertSee('Registered unit')
            ->assertSee('Block 42')
            ->assertSee('0xabababababababab');
    }

    public function test_donor_track_hides_raw_transaction_hashes(): void
    {
        [$hospitalUser] = $this->networkUsers();

        $donor = Donor::create([
            'donor_code' => 'DNR-TEST-001',
            'name' => 'Track Donor',
            'phone' => '+233244111222',
            'blood_group' => 'O+',
            'registered_at_hospital_id' => $hospitalUser->hospital_id,
            'tracking_consent' => true,
        ]);

        $unit = BloodUnit::create([
            'hospital_id' => $hospitalUser->hospital_id,
            'donor_id' => $donor->id,
            'unit_code' => 'UNIT-001-00088',
            'blood_group' => 'O+',
            'status' => 'available',
            'screening_status' => 'cleared',
            'recorded_by' => $hospitalUser->id,
            'collected_at' => now()->subDays(2),
            'expires_at' => now()->addDays(30),
            'screened_at' => now()->subDay(),
            'blockchain_register_tx' => '0x'.str_repeat('c', 64),
            'blockchain_screening_tx' => '0x'.str_repeat('d', 64),
        ]);

        $this->get('/track/'.$unit->unit_code)
            ->assertOk()
            ->assertSee('Donation status')
            ->assertSee('Network verification')
            ->assertDontSee($unit->blockchain_register_tx)
            ->assertDontSee('Blockchain verification');
    }

    public function test_failed_anchor_inserts_a_tamper_attempt(): void
    {
        [$hospitalUser, $labUser] = $this->networkUsers();

        config([
            'blockchain.enabled' => true,
            'blockchain.private_key' => '0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80',
        ]);

        Process::fake([
            '*' => Process::result(
                output: json_encode(['ok' => false, 'error' => 'BloodBank: screening already set']),
                exitCode: 1,
            ),
        ]);

        $this->actingAs($labUser);

        $hash = app(BlockchainService::class)->recordScreening(
            'UNIT-001-00001',
            'cleared',
            $labUser->id,
            $labUser->name
        );

        $this->assertNull($hash);
        $this->assertDatabaseHas('blockchain_tamper_attempts', [
            'user_id' => $labUser->id,
            'actor_name' => $labUser->name,
            'role' => 'lab',
            'action' => 'recordScreening',
            'unit_code' => 'UNIT-001-00001',
            'reason' => 'BloodBank: screening already set',
        ]);

        $this->actingAs($hospitalUser)
            ->get('/hospital/blockchain')
            ->assertOk()
            ->assertSee($labUser->name)
            ->assertSee('BloodBank: screening already set');
    }

    public function test_integrity_compare_flags_blood_group_mismatch(): void
    {
        [$hospitalUser] = $this->networkUsers();

        $unit = BloodUnit::create([
            'hospital_id' => $hospitalUser->hospital_id,
            'unit_code' => 'UNIT-001-00009',
            'blood_group' => 'A+',
            'status' => 'available',
            'screening_status' => 'cleared',
            'recorded_by' => $hospitalUser->id,
            'collected_at' => now()->subDays(2),
            'expires_at' => now()->addDays(30),
            'blockchain_register_tx' => '0x'.str_repeat('a', 64),
        ]);
        $unit->load(['recorder', 'screener', 'hospital']);

        $result = app(BlockchainIntegrityService::class)->compare($unit, [
            'exists' => true,
            'hospitalId' => $hospitalUser->hospital_id,
            'bloodGroup' => 'O+',
            'expiresAt' => $unit->expires_at->getTimestamp(),
            'screening' => 2,
            'screeningLabel' => 'cleared',
        ], true);

        $this->assertSame('tampered', $result['status']);
        $this->assertNotEmpty($result['mismatches']);
        $this->assertSame($hospitalUser->name, $result['lastEditor']);
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3?: Hospital}
     */
    private function networkUsers(bool $withSecondHospital = false): array
    {
        $hospital = Hospital::create([
            'name' => 'Ledger Test Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-LEDGER-001',
            'phone' => '+233244000099',
            'email' => 'ledger@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $hospitalUser = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'hospital',
            'status' => 'active',
            'job_title' => 'Manager',
        ]);

        $labUser = User::factory()->create([
            'name' => 'Jane Lab',
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab scientist',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'hospital_id' => null,
        ]);

        if (! $withSecondHospital) {
            return [$hospitalUser, $labUser, $admin];
        }

        $other = Hospital::create([
            'name' => 'Other Ledger Hospital',
            'type' => 'district',
            'region' => 'ashanti',
            'city' => 'Kumasi',
            'license_id' => 'HFRA-LEDGER-999',
            'phone' => '+233244000088',
            'email' => 'other@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        return [$hospitalUser, $labUser, $admin, $other];
    }
}
