<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->foreignId('donor_id')->nullable()->after('hospital_id')->constrained()->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('collected_at');
        });
    }

    public function down(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->dropForeign(['donor_id']);
            $table->dropColumn(['donor_id', 'expires_at']);
        });
    }
};
