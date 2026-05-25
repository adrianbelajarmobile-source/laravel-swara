<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('communities')) {
            Schema::create('communities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->index('created_by');
            });
        }

        if (!Schema::hasTable('community_members')) {
            Schema::create('community_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('community_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('role', ['influencer', 'pegiat'])->default('pegiat');
                $table->timestamps();

                $table->foreign('community_id')->references('id')->on('communities')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                $table->unique(['community_id', 'user_id']);
                $table->index('community_id');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('community_members');
    }
};
