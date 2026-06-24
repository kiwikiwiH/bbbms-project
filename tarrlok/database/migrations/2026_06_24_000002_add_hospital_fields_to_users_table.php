<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('job_title')->nullable()->after('name');
            $table->enum('role', ['admin', 'hospital', 'lab', 'donor'])->default('hospital')->after('password');
            $table->enum('status', ['pending', 'active', 'suspended'])->default('active')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropColumn(['hospital_id', 'job_title', 'role', 'status']);
        });
    }
};
