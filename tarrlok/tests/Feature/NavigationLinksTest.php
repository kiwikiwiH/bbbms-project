<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_and_guest_pages_expose_real_destinations(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('track.index', absolute: false), false)
            ->assertSee(route('login', absolute: false), false)
            ->assertSee(route('register', absolute: false), false)
            ->assertSee('#portals', false)
            ->assertSee('#how', false)
            ->assertSee('#impact', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee(route('home', absolute: false), false)
            ->assertSee(route('register', absolute: false), false)
            ->assertSee(route('track.index', absolute: false), false)
            ->assertSee(route('password.request', absolute: false), false);

        $this->get('/track')
            ->assertOk()
            ->assertSee(route('home', absolute: false), false)
            ->assertSee(route('login', absolute: false), false);

        $this->get('/register')
            ->assertOk()
            ->assertSee(route('home', absolute: false), false)
            ->assertSee(route('login', absolute: false), false);
    }

    public function test_portal_dashboards_link_to_their_working_pages(): void
    {
        [$hospitalUser, $labUser, $admin] = $this->networkUsers();

        $this->actingAs($hospitalUser)
            ->get('/hospital')
            ->assertOk()
            ->assertSee(route('hospital.inventory', absolute: false), false)
            ->assertSee(route('hospital.requests', absolute: false), false)
            ->assertSee(route('hospital.lab-staff.index', absolute: false), false)
            ->assertSee(route('hospital.facility', absolute: false), false)
            ->assertSee(route('hospital.blockchain', absolute: false), false)
            ->assertSee('Cleared stock by blood type', false);

        $this->actingAs($hospitalUser)
            ->get('/hospital/requests')
            ->assertOk()
            ->assertSee('Incoming', false)
            ->assertSee('Blood type', false);

        $this->actingAs($hospitalUser)
            ->get('/hospital/inventory')
            ->assertOk()
            ->assertSee('Blood type', false);

        $this->actingAs($labUser)
            ->get('/lab')
            ->assertOk()
            ->assertSee(route('lab.units.create', absolute: false), false)
            ->assertSee(route('lab.units.index', absolute: false), false)
            ->assertSee(route('lab.trace', absolute: false), false)
            ->assertSee(route('lab.blockchain', absolute: false), false);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee(route('admin.blockchain', absolute: false), false)
            ->assertSee(route('admin.registrations.index', ['status' => 'pending'], false), false)
            ->assertSee(route('admin.registrations.index', ['status' => 'approved'], false), false)
            ->assertSee(route('admin.registrations.index', ['status' => 'rejected'], false), false)
            ->assertSee(route('profile.edit', absolute: false), false);
    }

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    private function networkUsers(): array
    {
        $hospital = Hospital::create([
            'name' => 'Nav Test Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-NAV-001',
            'phone' => '+233244000088',
            'email' => 'nav@hospital.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        return [
            User::factory()->create([
                'hospital_id' => $hospital->id,
                'role' => 'hospital',
                'status' => 'active',
                'job_title' => 'Manager',
            ]),
            User::factory()->create([
                'hospital_id' => $hospital->id,
                'role' => 'lab',
                'status' => 'active',
                'job_title' => 'Lab scientist',
            ]),
            User::factory()->create([
                'role' => 'admin',
                'status' => 'active',
                'hospital_id' => null,
            ]),
        ];
    }
}
