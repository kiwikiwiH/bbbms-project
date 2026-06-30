<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->enum('screening_status', ['pending', 'cleared', 'failed'])
                ->default('pending')
                ->after('status');
            $table->timestamp('screened_at')->nullable()->after('collected_at');
            $table->foreignId('screened_by')->nullable()->after('screened_at')->constrained('users')->nullOnDelete();
            $table->boolean('screening_hiv')->default(false)->after('screened_by');
            $table->boolean('screening_hep_b')->default(false)->after('screening_hiv');
            $table->boolean('screening_hep_c')->default(false)->after('screening_hep_b');
            $table->boolean('screening_syphilis')->default(false)->after('screening_hep_c');
            $table->text('screening_notes')->nullable()->after('screening_syphilis');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE blood_units MODIFY COLUMN status ENUM('quarantine','available','reserved','issued','discarded') NOT NULL DEFAULT 'quarantine'");
        }

        DB::table('blood_units')->where('status', 'available')->update([
            'screening_status' => 'cleared',
            'screening_hiv' => true,
            'screening_hep_b' => true,
            'screening_hep_c' => true,
            'screening_syphilis' => true,
            'screened_at' => DB::raw('collected_at'),
        ]);

        DB::table('blood_units')->where('status', 'issued')->update([
            'screening_status' => 'cleared',
            'screening_hiv' => true,
            'screening_hep_b' => true,
            'screening_hep_c' => true,
            'screening_syphilis' => true,
            'screened_at' => DB::raw('collected_at'),
        ]);
    }

    public function down(): void
    {
        DB::table('blood_units')->where('status', 'quarantine')->delete();
        DB::table('blood_units')->where('status', 'discarded')->delete();

        Schema::table('blood_units', function (Blueprint $table) {
            $table->dropForeign(['screened_by']);
            $table->dropColumn([
                'screening_status',
                'screened_at',
                'screened_by',
                'screening_hiv',
                'screening_hep_b',
                'screening_hep_c',
                'screening_syphilis',
                'screening_notes',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE blood_units MODIFY COLUMN status ENUM('available','reserved','issued') NOT NULL DEFAULT 'available'");
        }
    }
};
