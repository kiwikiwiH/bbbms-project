<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloodRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_is_blocked_when_stock_is_insufficient(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $request = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'O+', 5);

        $this->actingAs($supplier['user'])
            ->post(route('hospital.requests.approve', $request))
            ->assertSessionHasErrors('stock');

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_incoming_list_flags_insufficient_stock_and_hides_approve(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $this->makeRequest($requester['hospital'], $supplier['hospital'], 'O+', 3);

        $this->actingAs($supplier['user'])
            ->get(route('hospital.requests'))
            ->assertOk()
            ->assertSee('flagged', false)
            ->assertSee('Approve blocked', false)
            ->assertSee('0 free / 3', false);
    }

    public function test_approve_succeeds_when_cleared_stock_covers_quantity(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $this->seedClearedUnits($supplier['hospital'], $supplier['user'], 'O+', 2);
        $request = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'O+', 2);

        $this->actingAs($supplier['user'])
            ->post(route('hospital.requests.approve', $request))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertSame($supplier['user']->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);
    }

    public function test_approved_request_can_be_reversed_to_pending(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $this->seedClearedUnits($supplier['hospital'], $supplier['user'], 'A+', 1);
        $request = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'A+', 1);

        $this->actingAs($supplier['user'])
            ->post(route('hospital.requests.approve', $request))
            ->assertSessionHasNoErrors();

        $this->actingAs($supplier['user'])
            ->post(route('hospital.requests.reverse', $request), [
                'reverse_reason' => 'Need to re-check fridge stock',
            ])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertSame($supplier['user']->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);
        $this->assertSame($supplier['user']->id, $request->reversed_by);
        $this->assertSame('Need to re-check fridge stock', $request->reverse_reason);
    }

    public function test_second_approve_is_blocked_when_stock_is_reserved_by_another_approval(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $this->seedClearedUnits($supplier['hospital'], $supplier['user'], 'O+', 2);

        $first = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'O+', 2);
        $second = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'O+', 2);

        $this->actingAs($supplier['user'])
            ->post(route('hospital.requests.approve', $first))
            ->assertSessionHasNoErrors();

        $this->actingAs($supplier['user'])
            ->post(route('hospital.requests.approve', $second))
            ->assertSessionHasErrors('stock');

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
    }

    public function test_outgoing_cancel_works_without_rejection_reason_field(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $request = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'AB+', 1);

        $this->actingAs($requester['user'])
            ->post(route('hospital.requests.cancel', $request))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame('Cancelled by requesting hospital.', $request->rejection_reason);
        $this->assertSame($requester['user']->id, $request->rejected_by);
    }

    public function test_blood_group_filter_on_requests_and_inventory(): void
    {
        [$supplier, $requester] = $this->twoHospitals();
        $this->seedClearedUnits($supplier['hospital'], $supplier['user'], 'B+', 1);
        $this->seedClearedUnits($supplier['hospital'], $supplier['user'], 'O-', 1);
        $bPlus = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'B+', 1);
        $oMinus = $this->makeRequest($requester['hospital'], $supplier['hospital'], 'O-', 1);

        $this->actingAs($supplier['user'])
            ->get(route('hospital.requests', ['blood_group' => 'B+']))
            ->assertOk()
            ->assertSee($bPlus->request_code, false)
            ->assertDontSee($oMinus->request_code, false);

        $this->actingAs($supplier['user'])
            ->get(route('hospital.inventory', ['blood_group' => 'O-', 'screening' => 'cleared']))
            ->assertOk()
            ->assertSee('O-', false)
            ->assertSee('Filtered units', false);
    }

    public function test_dashboard_shows_simple_analytics(): void
    {
        [$supplier] = $this->twoHospitals();
        $this->seedClearedUnits($supplier['hospital'], $supplier['user'], 'AB+', 1);

        $this->actingAs($supplier['user'])
            ->get(route('hospital.dashboard'))
            ->assertOk()
            ->assertSee('Cleared stock by blood type', false)
            ->assertSee('Incoming request status', false)
            ->assertSee('Screening outcomes', false)
            ->assertSee('AB+', false);
    }

    /**
     * @return array{0: array{hospital: Hospital, user: User}, 1: array{hospital: Hospital, user: User}}
     */
    private function twoHospitals(): array
    {
        $supplierHospital = Hospital::create([
            'name' => 'Ridge Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-REQ-SUP-001',
            'phone' => '+233244111001',
            'email' => 'supply@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $requesterHospital = Hospital::create([
            'name' => 'Korle Bu Teaching Hospital',
            'type' => 'teaching',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-REQ-REQ-001',
            'phone' => '+233244111002',
            'email' => 'request@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $supplierUser = User::factory()->create([
            'hospital_id' => $supplierHospital->id,
            'role' => 'hospital',
            'status' => 'active',
            'job_title' => 'Blood Bank Manager',
        ]);

        $requesterUser = User::factory()->create([
            'hospital_id' => $requesterHospital->id,
            'role' => 'hospital',
            'status' => 'active',
            'job_title' => 'Blood Bank Manager',
        ]);

        return [
            ['hospital' => $supplierHospital, 'user' => $supplierUser],
            ['hospital' => $requesterHospital, 'user' => $requesterUser],
        ];
    }

    private function makeRequest(Hospital $from, Hospital $to, string $group, int $qty): BloodRequest
    {
        return BloodRequest::create([
            'requesting_hospital_id' => $from->id,
            'fulfilling_hospital_id' => $to->id,
            'blood_group' => $group,
            'quantity' => $qty,
            'urgency' => 'routine',
            'status' => 'pending',
        ]);
    }

    private function seedClearedUnits(Hospital $hospital, User $user, string $group, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            BloodUnit::create([
                'hospital_id' => $hospital->id,
                'unit_code' => sprintf('UNIT-%03d-%05d', $hospital->id, $i + random_int(10, 90)),
                'blood_group' => $group,
                'status' => 'available',
                'screening_status' => 'cleared',
                'recorded_by' => $user->id,
                'collected_at' => now()->subDays(2),
                'expires_at' => now()->addDays(30),
                'screened_at' => now()->subDay(),
                'screening_hiv' => true,
                'screening_hep_b' => true,
                'screening_hep_c' => true,
                'screening_syphilis' => true,
            ]);
        }
    }
}
