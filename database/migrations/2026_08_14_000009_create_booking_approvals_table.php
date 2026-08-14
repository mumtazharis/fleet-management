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
        Schema::create('booking_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_booking_id')->constrained('vehicle_bookings')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete(); // User yang berhak menyetujui
            $table->unsignedInteger('approval_level')->default(1); // 1 = Level 1 (misal SPV), 2 = Level 2 (misal Manager)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note')->nullable(); // Catatan persetujuan / penolakan
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_approvals');
    }
};
