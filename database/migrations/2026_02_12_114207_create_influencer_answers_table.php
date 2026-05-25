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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('influencer_answers');
    }
};
