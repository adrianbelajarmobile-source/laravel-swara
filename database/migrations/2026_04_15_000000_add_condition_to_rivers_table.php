<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kondisi sungai:
     * 1 = normal
     * 2 = warning
     * 3 = urgent
     */
    public function up(): void
    {
        Schema::table('rivers', function (Blueprint $table) {
            $table->tinyInteger('condition')
                ->default(1)
                ->comment('1=normal, 2=warning, 3=urgent');
        });
    }

    public function down(): void
    {
        Schema::table('rivers', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
