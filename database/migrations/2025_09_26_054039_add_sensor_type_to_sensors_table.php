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
            $table->unsignedBigInteger('type_sensor_id')->nullable()->after('id');
            $table->foreign('type_sensor_id')->references('id')->on('sensors_type')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropForeign(['type_sensor_id']);
            $table->dropColumn('type_sensor_id');
        });
    }
};
