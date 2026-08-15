<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('service_logs', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('cost');
            }
            if (!Schema::hasColumn('service_logs', 'end_date')) {
                $table->dateTime('end_date')->nullable()->after('start_date');
            }
        });

        // Backfill existing records from service_date if available
        if (Schema::hasColumn('service_logs', 'service_date')) {
            DB::statement("UPDATE service_logs SET start_date = CONCAT(service_date, ' 08:00:00'), end_date = CONCAT(service_date, ' 17:00:00') WHERE start_date IS NULL AND service_date IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('service_logs', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (Schema::hasColumn('service_logs', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};
