<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite' || ! Schema::hasColumn('donors', 'user_id')) {
            return;
        }

        Schema::table('donors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || Schema::hasColumn('donors', 'user_id')) {
            return;
        }

        Schema::table('donors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });
    }
};
