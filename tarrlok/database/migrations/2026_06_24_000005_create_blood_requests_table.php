<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('requesting_hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('fulfilling_hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('blood_group', 5);
            $table->unsignedSmallInteger('quantity');
            $table->enum('urgency', ['emergency', 'routine'])->default('routine');
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blood_request_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blood_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blood_unit_id')->constrained()->cascadeOnDelete();
            $table->unique('blood_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_request_unit');
        Schema::dropIfExists('blood_requests');
    }
};
