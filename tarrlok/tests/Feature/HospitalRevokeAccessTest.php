<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\Hospital;
use App\Models\User;
use App\Notifications\HospitalAccessRevoked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HospitalRevokeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revoke_an_approved_hospital(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'hospital_id' => null,
        ]);

        $hospital = Hospital::create([
            'name' => 'Ridge Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-REVOKE-001',
            'phone' => '+233244777001',
            'email' => 'bloodbank@ridge-revoke.gh',
            'status' => 'approved',
            'reviewed_at' => now()->subMonths(6),
        ]);

        $hospitalAdmin = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'hospital',
            'status' => 'active',
            'job_title' => 'Blood Bank Manager',
            'email' => 'manager@ridge-revoke.gh',
        ]);

        $lab = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab Technologist',
        ]);

        $partner = Hospital::create([
            'name' => 'Korle Bu Teaching Hospital',
            'type' => 'teaching',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-REVOKE-002',
            'phone' => '+233244777002',
            'email' => 'bloodbank@korle-revoke.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $openRequest = BloodRequest::create([
            'requesting_hospital_id' => $partner->id,
            'fulfilling_hospital_id' => $hospital->id,
            'blood_group' => 'O+',
            'quantity' => 2,
            'urgency' => 'routine',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.registrations.show', $hospital))
            ->assertOk()
            ->assertSee('Revoke network access', false)
            ->assertSee(route('admin.registrations.revoke', $hospital, false), false);

        $this->actingAs($admin)
            ->post(route('admin.registrations.revoke', $hospital), [
                'rejection_reason' => 'HeFRA licence expired and was not renewed for continued network participation.',
                'admin_message' => 'Please renew and re-apply.',
            ])
            ->assertRedirect(route('admin.registrations.show', $hospital));

        $hospital->refresh();
        $this->assertSame('rejected', $hospital->status);
        $this->assertSame($admin->id, $hospital->reviewed_by);
        $this->assertSame('suspended', $hospitalAdmin->fresh()->status);
        $this->assertSame('suspended', $lab->fresh()->status);
        $this->assertSame('rejected', $openRequest->fresh()->status);

        Notification::assertSentTo($hospitalAdmin, HospitalAccessRevoked::class);

        $this->post('/logout');

        $this->post('/login', [
            'email' => $hospitalAdmin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_revoke_is_blocked_for_pending_hospitals(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'hospital_id' => null,
        ]);

        $hospital = Hospital::create([
            'name' => 'Pending Clinic',
            'type' => 'private',
            'region' => 'ashanti',
            'city' => 'Kumasi',
            'license_id' => 'HFRA-REVOKE-003',
            'phone' => '+233244777003',
            'email' => 'pending@clinic.gh',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.registrations.revoke', $hospital), [
                'rejection_reason' => 'Trying to revoke a pending facility incorrectly.',
            ])
            ->assertRedirect();

        $this->assertSame('pending', $hospital->fresh()->status);
    }
}
