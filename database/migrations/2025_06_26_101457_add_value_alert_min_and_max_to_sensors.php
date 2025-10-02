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
        Schema::table('sensors', function (Blueprint $table) {
            $table->integer('alert_notification_interval')->default(1)->after('location_id');
            $table->integer('alert_max_value')->nullable()->after('location_id');
            $table->integer('alert_min_value')->nullable()->after('location_id');
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn(['alert_min_value', 'alert_max_value', 'alert_notification_interval']);
        });
    }
};
