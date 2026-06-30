<?php

namespace Database\Seeders;

use App\Models\BloodUnit;
use App\Models\Donor;
use App\Models\Hospital;
use App\Models\User;
use App\Services\BlockchainService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Hospital::where('license_id', 'HFRA-GAR-2024')->exists()) {
            $this->command?->warn('Demo hospitals already exist — skipping DemoSeeder.');

            return;
        }

        $korleBu = Hospital::create([
            'name' => 'Korle Bu Teaching Hospital',
            'type' => 'teaching',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-GAR-2024',
            'phone' => '+233244123456',
            'email' => 'bloodbank@korlebu.gov.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $ridge = Hospital::create([
            'name' => 'Ridge Hospital',
            'type' => 'regional',
            'region' => 'greater_accra',
            'city' => 'Accra',
            'license_id' => 'HFRA-GAR-2025',
            'phone' => '+233244987654',
            'email' => 'bloodbank@ridge.gov.gh',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        User::create([
            'hospital_id' => $korleBu->id,
            'name' => 'Dr. Kwame Mensah',
            'job_title' => 'Blood Bank Manager',
            'email' => 'kwame.mensah@korlebu.gov.gh',
            'password' => Hash::make('KorleBu2024!'),
            'role' => 'hospital',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::create([
            'hospital_id' => $korleBu->id,
            'name' => 'Ama Osei',
            'job_title' => 'Senior Lab Technologist',
            'email' => 'ama.osei@korlebu.gov.gh',
            'password' => Hash::make('KorleBuLab2024!'),
            'role' => 'lab',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::create([
            'hospital_id' => $ridge->id,
            'name' => 'Dr. Efua Adjei',
            'job_title' => 'Blood Bank Manager',
            'email' => 'efua.adjei@ridge.gov.gh',
            'password' => Hash::make('Ridge2024!'),
            'role' => 'hospital',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $ridgeLab = User::create([
            'hospital_id' => $ridge->id,
            'name' => 'Kofi Boateng',
            'job_title' => 'Lab Technologist',
            'email' => 'kofi.boateng@ridge.gov.gh',
            'password' => Hash::make('RidgeLab2024!'),
            'role' => 'lab',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $demoDonor = Donor::create([
            'donor_code' => Donor::generateDonorCode(),
            'name' => 'Akosua Donkor',
            'phone' => '+233244555123',
            'email' => 'akosua.donor@example.com',
            'blood_group' => 'O+',
            'registered_at_hospital_id' => $ridge->id,
            'tracking_consent' => true,
            'last_donation_at' => now()->subDays(5),
        ]);

        $groups = ['O+', 'O+', 'A+', 'B+', 'AB+'];
        $collectedOffsets = [30, 5, 4, 3, 2];

        foreach ($groups as $index => $group) {
            $collectedAt = now()->subDays($collectedOffsets[$index]);

            $unit = BloodUnit::create([
                'hospital_id' => $ridge->id,
                'donor_id' => $demoDonor->id,
                'unit_code' => sprintf('UNIT-%03d-%05d', $ridge->id, $index + 1),
                'blood_group' => $group,
                'status' => 'available',
                'screening_status' => 'cleared',
                'recorded_by' => $ridgeLab->id,
                'screened_by' => $ridgeLab->id,
                'collected_at' => $collectedAt,
                'expires_at' => BloodUnit::calculateExpiresAt($collectedAt),
                'screened_at' => $collectedAt->copy()->addDay(),
                'screening_hiv' => true,
                'screening_hep_b' => true,
                'screening_hep_c' => true,
                'screening_syphilis' => true,
            ]);

            $this->seedBlockchainHashes($unit, $ridge);
        }

        Artisan::call('blood:mark-expired');

        $this->command?->info('Demo data seeded.');
        $this->command?->table(
            ['Role', 'Email', 'Password'],
            [
                ['Korle Bu admin', 'kwame.mensah@korlebu.gov.gh', 'KorleBu2024!'],
                ['Korle Bu lab', 'ama.osei@korlebu.gov.gh', 'KorleBuLab2024!'],
                ['Ridge admin', 'efua.adjei@ridge.gov.gh', 'Ridge2024!'],
                ['Ridge lab', 'kofi.boateng@ridge.gov.gh', 'RidgeLab2024!'],
            ]
        );
        $this->command?->info('Donor tracking: /track → UNIT-002-00001 (demo blockchain hashes included).');
    }

    private function seedBlockchainHashes(BloodUnit $unit, Hospital $hospital): void
    {
        $blockchain = app(BlockchainService::class);

        if ($blockchain->isEnabled()) {
            $registerTx = $blockchain->registerUnit($unit->unit_code, $hospital->id, $unit->blood_group);
            $screenTx = $blockchain->recordScreening($unit->unit_code, 'cleared');

            $unit->update(array_filter([
                'blockchain_register_tx' => $registerTx,
                'blockchain_screening_tx' => $screenTx,
            ]));

            return;
        }

        $unit->update([
            'blockchain_register_tx' => $this->demoTxHash($unit->unit_code, 'register'),
            'blockchain_screening_tx' => $this->demoTxHash($unit->unit_code, 'screen'),
        ]);
    }

    private function demoTxHash(string $unitCode, string $event): string
    {
        return '0x'.substr(hash('sha256', "tarrlok-demo:{$unitCode}:{$event}"), 0, 64);
    }
}
