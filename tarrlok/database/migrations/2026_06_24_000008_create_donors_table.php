<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('donor_code')->unique();
            $table->string('name');
            $table->string('phone', 30)->unique();
            $table->string('email')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->foreignId('registered_at_hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_donation_at')->nullable();
            $table->boolean('tracking_consent')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
