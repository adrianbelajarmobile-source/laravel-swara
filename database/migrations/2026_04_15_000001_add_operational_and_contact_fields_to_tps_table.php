<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tps', function (Blueprint $table) {
            $table->json('accepted_waste_types')->nullable()->after('longitude');
            $table->time('open_time')->nullable()->after('accepted_waste_types');
            $table->time('close_time')->nullable()->after('open_time');
            $table->string('contact_phone')->nullable()->after('close_time');
            $table->string('contact_social_media')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('tps', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_waste_types',
                'open_time',
                'close_time',
                'contact_phone',
                'contact_social_media',
            ]);
        });
    }
};
