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
        if (!Schema::hasTable('influencer_applications')) {
            Schema::create('influencer_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('nik');
                $table->string('screenshot_path');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('admin_note')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('influencer_questions')) {
            Schema::create('influencer_questions', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('influencer_answers')) {
            Schema::create('influencer_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')
                    ->constrained('influencer_applications')
                    ->cascadeOnDelete();
                $table->foreignId('question_id')
                    ->constrained('influencer_questions')
                    ->cascadeOnDelete();
                $table->text('answer');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty to avoid destructive rollback on recovered tables.
    }
};
