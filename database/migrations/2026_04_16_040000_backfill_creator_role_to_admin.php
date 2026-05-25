<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill existing creator memberships to admin role
        DB::statement("
            UPDATE community_members cm
            SET role = 'admin'
            WHERE cm.user_id = (
                SELECT c.created_by
                FROM communities c
                WHERE c.id = cm.community_id
            )
            AND cm.role = 'influencer'
        ");
    }

    public function down(): void
    {
        // Rollback: convert admin back to influencer for creators only
        // Note: This is lossy - we can't distinguish between admin promoted by creator vs creator's original role
        // So we don't rollback
    }
};
