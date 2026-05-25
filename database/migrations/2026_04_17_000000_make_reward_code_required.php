<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update NULL values menjadi unique code sebelum set NOT NULL
        $nullRewards = DB::table('rewards')->whereNull('code')->get();
        foreach ($nullRewards as $reward) {
            DB::table('rewards')
                ->where('id', $reward->id)
                ->update(['code' => 'REWARD_' . $reward->id . '_' . Str::random(10)]);
        }

        Schema::table('rewards', function (Blueprint $table) {
            $table->text('code')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->text('code')->nullable(true)->change();
        });
    }
};
