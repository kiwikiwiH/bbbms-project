<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloodComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_shelf_life_depends_on_component_type(): void
    {
        $collected = now()->subDays(2)->startOfDay();

        $rbcExpiry = BloodUnit::calculateExpiresAt($collected, 'red_blood_cells');
        $plateletExpiry = BloodUnit::calculateExpiresAt($collected, 'platelets');
        $ffpExpiry = BloodUnit::calculateExpiresAt($collected, 'fresh_frozen_plasma');

        $this->assertSame(35, BloodUnit::shelfLifeDays('red_blood_cells'));
        $this->assertSame(5, BloodUnit::shelfLifeDays('platelets'));
        $this->assertSame(365, BloodUnit::shelfLifeDays('fresh_frozen_plasma'));

        $this->assertTrue($rbcExpiry->equalTo($collected->copy()->addDays(35)));
        $this->assertTrue($plateletExpiry->equalTo($collected->copy()->addDays(5)));
        $this->assertTrue($ffpExpiry->equalTo($collected->copy()->addDays(365)));
    }

    public function test_lab_register_persists_component_and_component_expiry(): void
    {
        $hospital = Hospital::create([
            'name' => 'Component Lab Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-COMP-001',
            'phone' => '+233244777001',
            'email' => 'comp@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $lab = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab scientist',
        ]);

        $collected = now()->toDateString();

        $this->actingAs($lab)
            ->post(route('lab.units.store'), [
                'blood_group' => 'O+',
                'component_type' => 'platelets',
                'collected_at' => $collected,
                'donor_phone' => '0244111222',
                'donor_name' => 'Component Donor',
            ])
            ->assertRedirect();

        $unit = BloodUnit::query()->first();
        $this->assertNotNull($unit);
        $this->assertSame('platelets', $unit->component_type);
        $this->assertSame('Platelets', $unit->componentLabel());
        $this->assertTrue(
            $unit->expires_at->equalTo(BloodUnit::calculateExpiresAt($collected, 'platelets'))
        );
    }

    public function test_existing_rows_default_to_whole_blood_after_migrate(): void
    {
        $hospital = Hospital::create([
            'name' => 'Default Component Hospital',
            'type' => 'district',
            'region' => 'ashanti',
            'city' => 'Kumasi',
            'license_id' => 'HFRA-COMP-002',
            'phone' => '+233244777002',
            'email' => 'default@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'hospital',
            'status' => 'active',
        ]);

        $unit = BloodUnit::create([
            'hospital_id' => $hospital->id,
            'unit_code' => 'UNIT-001-00099',
            'blood_group' => 'A+',
            'status' => 'available',
            'screening_status' => 'cleared',
            'recorded_by' => $user->id,
            'collected_at' => now()->subDays(2),
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertSame('whole_blood', $unit->fresh()->component_type);
    }
}
