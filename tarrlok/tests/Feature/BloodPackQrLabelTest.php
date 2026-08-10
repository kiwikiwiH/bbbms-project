<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloodPackQrLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_can_open_bag_label_and_donor_slip_with_qr(): void
    {
        [$lab, $unit] = $this->labWithUnit();

        $this->actingAs($lab)
            ->get(route('lab.units.bag-label', $unit))
            ->assertOk()
            ->assertSee($unit->unit_code, false)
            ->assertSee('Scan to verify / track', false)
            ->assertSee('data:image/png;base64,', false)
            ->assertDontSee('Donor:', false);

        $this->actingAs($lab)
            ->get(route('lab.units.slip', $unit))
            ->assertOk()
            ->assertSee('Scan to track your donation', false)
            ->assertSee('data:image/png;base64,', false);
        $this->actingAs($lab)
            ->get(route('lab.units.screening.show', $unit))
            ->assertOk()
            ->assertSee('On-screen QR for this unit', false)
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('Open public track', false);
    }

    public function test_public_track_page_shows_qr_when_consent_allows(): void
    {
        [, $unit] = $this->labWithUnit(withConsentingDonor: true);

        $this->get(route('track.show', $unit))
            ->assertOk()
            ->assertSee('Scan this QR anytime', false)
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_lab_cannot_print_another_hospitals_bag_label(): void
    {
        [$lab] = $this->labWithUnit();

        $otherHospital = Hospital::create([
            'name' => 'Other QR Hospital',
            'type' => 'district',
            'region' => 'ashanti',
            'city' => 'Kumasi',
            'license_id' => 'HFRA-QR-OTHER',
            'phone' => '+233244999001',
            'email' => 'other-qr@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'hospital_id' => $otherHospital->id,
            'role' => 'lab',
            'status' => 'active',
        ]);

        $foreignUnit = BloodUnit::create([
            'hospital_id' => $otherHospital->id,
            'unit_code' => 'UNIT-999-00001',
            'blood_group' => 'O+',
            'component_type' => 'whole_blood',
            'status' => 'quarantine',
            'screening_status' => 'pending',
            'recorded_by' => $otherUser->id,
            'collected_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($lab)
            ->get(route('lab.units.bag-label', $foreignUnit))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: BloodUnit}
     */
    private function labWithUnit(bool $withConsentingDonor = false): array
    {
        $hospital = Hospital::create([
            'name' => 'QR Lab Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-QR-001',
            'phone' => '+233244888001',
            'email' => 'qr@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $lab = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab scientist',
        ]);

        $donorId = null;
        if ($withConsentingDonor) {
            $donor = \App\Models\Donor::create([
                'donor_code' => 'DNR-QR-001',
                'name' => 'QR Donor',
                'phone' => '+233244888999',
                'blood_group' => 'A+',
                'registered_at_hospital_id' => $hospital->id,
                'tracking_consent' => true,
            ]);
            $donorId = $donor->id;
        }

        $unit = BloodUnit::create([
            'hospital_id' => $hospital->id,
            'donor_id' => $donorId,
            'unit_code' => 'UNIT-001-00055',
            'blood_group' => 'A+',
            'component_type' => 'red_blood_cells',
            'status' => 'quarantine',
            'screening_status' => 'pending',
            'recorded_by' => $lab->id,
            'collected_at' => now()->subDay(),
            'expires_at' => now()->addDays(34),
        ]);

        return [$lab, $unit];
    }
}
