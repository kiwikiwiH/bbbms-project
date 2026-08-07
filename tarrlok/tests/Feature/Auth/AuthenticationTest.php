<?php

namespace Tests\Feature\Auth;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $hospital = Hospital::create([
            'name' => 'Test Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-TEST-001',
            'phone' => '+233244000001',
            'email' => 'test@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'hospital',
            'status' => 'active',
            'job_title' => 'Manager',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('hospital.dashboard', absolute: false));
    }

    public function test_lab_staff_login_ignores_a_hospital_intended_url(): void
    {
        $hospital = Hospital::create([
            'name' => 'Ridge Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-TEST-LAB-001',
            'phone' => '+233244000002',
            'email' => 'labtest@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $labUser = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab Technologist',
        ]);

        $this->get('/hospital')->assertRedirect(route('login'));

        $response = $this->post('/login', [
            'email' => $labUser->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('lab.dashboard', absolute: false));
    }

    public function test_lab_staff_visiting_hospital_routes_are_sent_to_the_lab_portal(): void
    {
        $hospital = Hospital::create([
            'name' => 'Korle Bu Teaching Hospital',
            'type' => 'teaching',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-TEST-LAB-002',
            'phone' => '+233244000003',
            'email' => 'labtest2@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $labUser = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab Technologist',
        ]);

        $this->actingAs($labUser)
            ->get('/hospital')
            ->assertRedirect(route('lab.dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
