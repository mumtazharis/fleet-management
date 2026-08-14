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
        if (!Schema::hasColumn('roles', 'level')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedInteger('level')->default(0)->after('name'); // 1 = Level 1 (SPV), 2 = Level 2 (Manager), 0 = Admin/Staff
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('roles', 'level')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('level');
            });
        }
    }
};
