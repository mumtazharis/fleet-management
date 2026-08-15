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
        Schema::table('service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('service_logs', 'status')) {
                $table->string('status')->default('in_progress')->after('cost'); // 'in_progress', 'completed'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('service_logs', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
