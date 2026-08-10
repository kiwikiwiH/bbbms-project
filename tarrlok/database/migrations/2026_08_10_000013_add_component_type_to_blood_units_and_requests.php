<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->string('component_type', 32)->default('whole_blood')->after('blood_group');
            $table->index(['blood_group', 'component_type']);
        });

        Schema::table('blood_requests', function (Blueprint $table) {
            $table->string('component_type', 32)->default('whole_blood')->after('blood_group');
            $table->index(['blood_group', 'component_type']);
        });
    }

    public function down(): void
    {
        Schema::table('blood_units', function (Blueprint $table) {
            $table->dropIndex(['blood_group', 'component_type']);
            $table->dropColumn('component_type');
        });

        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropIndex(['blood_group', 'component_type']);
            $table->dropColumn('component_type');
        });
    }
};
