<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
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

    public function test_hospital_lab_and_admin_can_open_the_shared_ledger(): void
    {
        [$hospitalUser, $labUser, $admin] = $this->networkUsers();

        $this->actingAs($hospitalUser)
            ->get('/hospital/blockchain')
            ->assertOk()
            ->assertSee('Network activity')
            ->assertSee('Integrity alerts')
            ->assertSee('Blocked attempts');

        $this->actingAs($labUser)
            ->get('/lab/blockchain')
            ->assertOk()
            ->assertSee('Network activity');

        $this->actingAs($admin)
            ->get('/admin/blockchain')
            ->assertOk()
            ->assertSee('Blockchain audit trail')
            ->assertSee('Network activity');
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
     * @return array{0: User, 1: User, 2: User}
     */
    private function networkUsers(): array
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

        return [$hospitalUser, $labUser, $admin];
    }
}
