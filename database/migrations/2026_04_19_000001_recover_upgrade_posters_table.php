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
        if (!Schema::hasTable('upgrade_posters')) {
            Schema::create('upgrade_posters', function (Blueprint $table) {
                $table->id();
                $table->string('image');
                $table->integer('order');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak drop table agar recovery migration tetap aman
    }
};
