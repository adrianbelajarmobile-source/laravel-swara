<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('communities')) {
            return;
        }

        Schema::table('communities', function (Blueprint $table) {
            if (!Schema::hasColumn('communities', 'capacity')) {
                $table->integer('capacity')->default(0)->after('description');
            }

            if (!Schema::hasColumn('communities', 'location')) {
                $table->string('location')->nullable()->after('capacity');
            }

            if (!Schema::hasColumn('communities', 'privacy')) {
                $table->string('privacy')->default('Publik')->after('location');
            }

            if (!Schema::hasColumn('communities', 'permission')) {
                $table->string('permission')->default('Bebas')->after('privacy');
            }

            if (!Schema::hasColumn('communities', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('permission');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('communities')) {
            return;
        }

        Schema::table('communities', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['capacity', 'location', 'privacy', 'permission', 'cover_image'] as $column) {
                if (Schema::hasColumn('communities', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
