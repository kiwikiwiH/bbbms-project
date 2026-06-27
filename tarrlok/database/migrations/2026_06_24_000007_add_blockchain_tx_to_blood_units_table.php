<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->string('blockchain_register_tx', 66)->nullable()->after('screening_notes');
            $table->string('blockchain_screening_tx', 66)->nullable()->after('blockchain_register_tx');
            $table->string('blockchain_issue_tx', 66)->nullable()->after('blockchain_screening_tx');
        });
    }

    public function down(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->dropColumn([
                'blockchain_register_tx',
                'blockchain_screening_tx',
                'blockchain_issue_tx',
            ]);
        });
    }
};
