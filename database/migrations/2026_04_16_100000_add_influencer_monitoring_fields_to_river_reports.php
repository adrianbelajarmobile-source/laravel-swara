<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('river_reports', function (Blueprint $table) {
            // Check if columns already exist before adding
            if (!Schema::hasColumn('river_reports', 'video_path')) {
                $table->string('video_path')->nullable()->after('photo_path');
            }
            
            if (!Schema::hasColumn('river_reports', 'monitoring_date')) {
                $table->date('monitoring_date')->nullable()->after('video_path');
            }
            
            if (!Schema::hasColumn('river_reports', 'reported_by_type')) {
                $table->enum('reported_by_type', ['community', 'influencer'])->default('community')->after('monitoring_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('river_reports', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'monitoring_date', 'reported_by_type']);
        });
    }
};
