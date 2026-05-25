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
        // Backfill komunitas dengan location kosong/NULL
        // Format: "Lokasi, Kota, Provinsi, Negara"
        DB::table('communities')
            ->whereNull('location')
            ->orWhere('location', '')
            ->update([
                'location' => 'Unknown Location, Unknown City, Jawa Timur, Indonesia'
            ]);

        // Ensure location column is NOT NULL dengan default value
        Schema::table('communities', function (Blueprint $table) {
            $table->string('location')
                ->default('Unknown Location, Unknown City, Jawa Timur, Indonesia')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->string('location')->nullable()->change();
        });
    }
};
