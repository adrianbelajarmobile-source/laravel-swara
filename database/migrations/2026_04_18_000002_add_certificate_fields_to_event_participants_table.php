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
        Schema::table('event_participants', function (Blueprint $table) {
            $table->string('certificate_status')->default('not_generated')->after('checked_out_at');
            $table->string('certificate_path')->nullable()->after('certificate_status');
            $table->timestamp('certificate_generated_at')->nullable()->after('certificate_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn(['certificate_status', 'certificate_path', 'certificate_generated_at']);
        });
    }
};