<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique(); // e.g. BOOK-20260814-001
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Admin / Pemesan
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('start_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('start_address')->nullable(); // Titik mulai spesifik (string/teks)
            $table->string('destination_address')->nullable(); // Titik tujuan spesifik (string/teks)
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->text('purpose')->nullable(); // Keperluan pemakaian
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_bookings');
    }
};
