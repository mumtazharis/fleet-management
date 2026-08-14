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
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('service_type')->default('Rutin'); // Rutin, Perbaikan Berkala, Ganti Oli, dll.
            $table->text('description')->nullable(); // Rincian perbaikan / service
            $table->decimal('cost', 14, 2); // Biaya service
            $table->date('service_date'); // Tanggal service
            $table->date('next_service_date')->nullable(); // Jadwal service berikutnya
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
