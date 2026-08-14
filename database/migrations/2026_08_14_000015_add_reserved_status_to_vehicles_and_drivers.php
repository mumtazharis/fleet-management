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
        // For SQLite and MySQL compatibility, update status column schema if necessary
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('status')->default('available')->change();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('status')->default('available')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
