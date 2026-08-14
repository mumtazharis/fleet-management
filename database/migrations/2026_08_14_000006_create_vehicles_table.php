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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama / Model kendaraan, misal: Toyota Hilux 4x4, Dump Truck Hino 500
            $table->string('license_plate')->unique(); // Plat nomor
            $table->enum('type', ['passenger', 'cargo'])->default('passenger'); // Jenis: angkutan orang / angkutan barang
            $table->enum('ownership', ['company', 'rented'])->default('company'); // Milik perusahaan / sewa
            $table->foreignId('rental_company_id')->nullable()->constrained('rental_companies')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('status', ['available', 'reserved', 'in_use', 'service'])->default('available');
            $table->string('fuel_type')->default('Solar'); // Jenis BBM: Solar, Dexlite, Pertalite, dll.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
