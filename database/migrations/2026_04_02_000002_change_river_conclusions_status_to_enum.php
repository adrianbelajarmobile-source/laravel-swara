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
            DB::statement("ALTER TABLE river_conclusions ALTER COLUMN status TYPE VARCHAR(20) USING status::text");
            DB::statement("UPDATE river_conclusions SET status = CASE
                WHEN status IN ('normal', 'warning', 'urgent') THEN status
                WHEN status IN ('unknown') THEN 'normal'
                WHEN status IN ('critical') THEN 'urgent'
                ELSE 'normal'
            END");
            DB::statement("ALTER TABLE river_conclusions ALTER COLUMN status SET NOT NULL");
            DB::statement("ALTER TABLE river_conclusions ALTER COLUMN status SET DEFAULT 'normal'");
            DB::statement("ALTER TABLE river_conclusions DROP CONSTRAINT IF EXISTS river_conclusions_status_check");
            DB::statement("ALTER TABLE river_conclusions ADD CONSTRAINT river_conclusions_status_check CHECK (status IN ('normal', 'warning', 'urgent'))");

            return;
        }

        DB::statement("ALTER TABLE river_conclusions MODIFY status ENUM('normal', 'warning', 'urgent') NOT NULL DEFAULT 'normal'");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE river_conclusions DROP CONSTRAINT IF EXISTS river_conclusions_status_check");
            DB::statement("ALTER TABLE river_conclusions ALTER COLUMN status TYPE VARCHAR(255) USING status::text");
            DB::statement("ALTER TABLE river_conclusions ALTER COLUMN status SET NOT NULL");
            DB::statement("ALTER TABLE river_conclusions ALTER COLUMN status SET DEFAULT 'normal'");

            return;
        }

        DB::statement("ALTER TABLE river_conclusions MODIFY status VARCHAR(255) NOT NULL DEFAULT 'normal'");
    }
};