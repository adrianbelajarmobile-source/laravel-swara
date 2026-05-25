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
            // For PostgreSQL, we need to handle the enum type carefully
            try {
                // Drop type if exists (CASCADE to remove columns using it)
                DB::statement("DROP TYPE IF EXISTS urgency_enum CASCADE");
            } catch (\Exception $e) {
                // Type might not exist, continue
            }

            // Drop existing constraints first
            DB::statement("ALTER TABLE river_reports DROP CONSTRAINT IF EXISTS river_reports_urgency_check");

            // Drop the column completely and recreate as VARCHAR
            DB::statement("ALTER TABLE river_reports DROP COLUMN IF EXISTS urgency");
            
            // Add new VARCHAR column with safe default
            DB::statement("ALTER TABLE river_reports ADD COLUMN urgency VARCHAR(20) NOT NULL DEFAULT 'normal'");

            // Add constraint
            DB::statement("
                ALTER TABLE river_reports 
                ADD CONSTRAINT river_reports_urgency_check 
                CHECK (urgency IN ('normal', 'warning', 'urgent'))
            ");
        }

        if ($driver === 'mysql') {
            // For MySQL, ensure ENUM type
            DB::statement("
                ALTER TABLE river_reports 
                MODIFY urgency ENUM('normal', 'warning', 'urgent') NOT NULL DEFAULT 'normal'
            ");
        }
    }

    public function down(): void
    {
        // Not implemented for safety
    }
};

