<?php

namespace Tests\Feature;

use App\Models\AuthActivityLog;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_and_logout_are_recorded(): void
    {
        $user = $this->hospitalUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'hospital',
            'event' => 'login',
        ]);

        $this->post('/logout')->assertRedirect('/');

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => $user->id,
            'event' => 'logout',
        ]);
    }

    public function test_failed_login_is_recorded(): void
    {
        $user = $this->hospitalUser();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('auth_activity_logs', [
            'email' => strtolower($user->email),
            'event' => 'login_failed',
            'user_id' => null,
        ]);
    }

    public function test_only_admin_can_view_sign_in_log(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'hospital_id' => null,
        ]);

        $hospitalUser = $this->hospitalUser();

        $this->get('/admin/auth-log')->assertRedirect('/login');

        $this->actingAs($hospitalUser)
            ->get('/admin/auth-log')
            ->assertRedirect(route('hospital.dashboard'));

        AuthActivityLog::record('login', request(), $hospitalUser);

        $this->actingAs($admin)
            ->get('/admin/auth-log')
            ->assertOk()
            ->assertSee('Activity log', false)
            ->assertSee($hospitalUser->name, false)
            ->assertSee('Login', false);
    }

    public function test_lab_register_unit_is_recorded_as_action(): void
    {
        $hospital = Hospital::create([
            'name' => 'Action Log Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-ACTLOG-001',
            'phone' => '+233244777200',
            'email' => 'actlog@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $lab = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'lab',
            'status' => 'active',
            'job_title' => 'Lab scientist',
        ]);

        $this->actingAs($lab)
            ->post(route('lab.units.store'), [
                'blood_group' => 'O+',
                'component_type' => 'red_blood_cells',
                'collected_at' => now()->toDateString(),
                'donor_phone' => '0244111333',
                'donor_name' => 'Activity Donor',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => $lab->id,
            'event' => 'action',
        ]);

        $row = AuthActivityLog::query()->where('event', 'action')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertStringContainsString('Registered unit', (string) $row->summary);
        $this->assertStringContainsString('Red Blood Cells', (string) $row->summary);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'hospital_id' => null,
        ]);

        $this->actingAs($admin)
            ->get('/admin/auth-log?event=action')
            ->assertOk()
            ->assertSee('Registered unit', false)
            ->assertSee($lab->name, false);
    }

    private function hospitalUser(): User
    {
        $hospital = Hospital::create([
            'name' => 'Auth Log Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-AUTHLOG-001',
            'phone' => '+233244777100',
            'email' => 'authlog@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        return User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => 'hospital',
            'status' => 'active',
            'job_title' => 'Manager',
        ]);
    }
}
