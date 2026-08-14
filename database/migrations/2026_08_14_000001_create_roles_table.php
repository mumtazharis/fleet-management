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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. admin, supervisor, manager, employee
            $table->unsignedInteger('level')->default(0); // 1 = Level 1 (SPV), 2 = Level 2 (Manager), 0 = Admin/Staff
            $table->string('label'); // e.g. Administrator Pool, Supervisor (Atasan L1), Manager (Atasan L2), Pegawai
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
