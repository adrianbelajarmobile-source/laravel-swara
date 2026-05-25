<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_members')) {
            return;
        }

        Schema::table('community_members', function (Blueprint $table) {
            if (!Schema::hasColumn('community_members', 'status')) {
                $table->string('status', 20)->default('approved')->after('role');
                $table->index(['community_id', 'status']);
            }

            if (!Schema::hasColumn('community_members', 'invited_by')) {
                $table->unsignedBigInteger('invited_by')->nullable()->after('status');
                $table->index('invited_by');
            }

            if (!Schema::hasColumn('community_members', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('invited_by');
                $table->index('approved_by');
            }

            if (!Schema::hasColumn('community_members', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Backfill status for existing data.
        DB::table('community_members')
            ->whereNull('status')
            ->update(['status' => 'approved']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $checks = DB::select("\n                SELECT c.conname\n                FROM pg_constraint c\n                JOIN pg_class t ON c.conrelid = t.oid\n                WHERE t.relname = 'community_members'\n                  AND c.contype = 'c'\n                  AND pg_get_constraintdef(c.oid) ILIKE '%role%'\n            ");

            foreach ($checks as $check) {
                DB::statement('ALTER TABLE community_members DROP CONSTRAINT IF EXISTS "' . $check->conname . '"');
            }

            DB::statement("ALTER TABLE community_members ALTER COLUMN role TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE community_members ALTER COLUMN role SET DEFAULT 'pegiat'");
            DB::statement("ALTER TABLE community_members ADD CONSTRAINT community_members_role_check CHECK (role IN ('influencer', 'admin', 'pegiat'))");
            DB::statement("ALTER TABLE community_members ADD CONSTRAINT community_members_status_check CHECK (status IN ('pending', 'invited', 'approved'))");
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE community_members MODIFY role ENUM('influencer', 'admin', 'pegiat') NOT NULL DEFAULT 'pegiat'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_members')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE community_members DROP CONSTRAINT IF EXISTS community_members_status_check");
            DB::statement("ALTER TABLE community_members DROP CONSTRAINT IF EXISTS community_members_role_check");
            DB::statement("ALTER TABLE community_members ADD CONSTRAINT community_members_role_check CHECK (role IN ('influencer', 'pegiat'))");
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE community_members MODIFY role ENUM('influencer', 'pegiat') NOT NULL DEFAULT 'pegiat'");
        }

        Schema::table('community_members', function (Blueprint $table) {
            $drop = [];

            foreach (['status', 'invited_by', 'approved_by', 'approved_at'] as $col) {
                if (Schema::hasColumn('community_members', $col)) {
                    $drop[] = $col;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
