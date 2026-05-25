<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE river_reports ALTER COLUMN urgency TYPE VARCHAR(20) USING urgency::text");
            DB::statement("UPDATE river_reports SET urgency = CASE
                WHEN urgency IN ('normal', 'warning', 'urgent') THEN urgency
                WHEN urgency IN ('1', 'low', 'rendah') THEN 'normal'
                WHEN urgency IN ('2', '3', 'medium', 'sedang') THEN 'warning'
                WHEN urgency IN ('4', '5', 'high', 'tinggi') THEN 'urgent'
                ELSE 'normal'
            END");
            DB::statement("ALTER TABLE river_reports ALTER COLUMN urgency SET NOT NULL");
            DB::statement("ALTER TABLE river_reports ALTER COLUMN urgency SET DEFAULT 'normal'");
            DB::statement("ALTER TABLE river_reports DROP CONSTRAINT IF EXISTS river_reports_urgency_check");
            DB::statement("ALTER TABLE river_reports ADD CONSTRAINT river_reports_urgency_check CHECK (urgency IN ('normal', 'warning', 'urgent'))");

            return;
        }

        DB::statement("ALTER TABLE river_reports MODIFY urgency ENUM('normal', 'warning', 'urgent') NOT NULL DEFAULT 'normal'");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE river_reports DROP CONSTRAINT IF EXISTS river_reports_urgency_check");
            DB::statement("ALTER TABLE river_reports ALTER COLUMN urgency TYPE VARCHAR(255) USING urgency::text");
            DB::statement("ALTER TABLE river_reports ALTER COLUMN urgency SET NOT NULL");
            DB::statement("ALTER TABLE river_reports ALTER COLUMN urgency SET DEFAULT 'normal'");

            return;
        }

        DB::statement("ALTER TABLE river_reports MODIFY urgency VARCHAR(255) NOT NULL DEFAULT 'normal'");
    }
};