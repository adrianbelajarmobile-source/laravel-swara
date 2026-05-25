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
        // Change status to VARCHAR first to allow all values
        DB::statement('ALTER TABLE "event_participants" ALTER COLUMN "status" TYPE VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Can revert if needed
        DB::statement('ALTER TABLE "event_participants" ALTER COLUMN "status" TYPE VARCHAR(255)');
    }
};
