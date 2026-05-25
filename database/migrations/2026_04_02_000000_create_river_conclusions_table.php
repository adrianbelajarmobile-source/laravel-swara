<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('river_conclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('river_id')->unique()->constrained('rivers')->cascadeOnDelete();
            $table->string('river_path')->nullable();
            $table->enum('status', ['normal', 'warning', 'urgent'])->default('normal');
            $table->string('pollution_type')->default('unknown');
            $table->decimal('average_urgency', 5, 2)->nullable();
            $table->json('reporter_user_ids')->nullable();
            $table->json('reporters')->nullable();
            $table->unsignedInteger('reporter_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('river_conclusions');
    }
};