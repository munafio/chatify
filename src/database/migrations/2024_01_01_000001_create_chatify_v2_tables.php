<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ch_favorites');
        Schema::dropIfExists('ch_messages');
        Schema::dropIfExists('ch_conversation_participants');
        Schema::dropIfExists('ch_conversations');
        Schema::dropIfExists('ch_user_settings');

        Schema::create('ch_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 20)->default('direct');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('ch_conversation_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'conversation_id']);
            $table->foreign('conversation_id')->references('id')->on('ch_conversations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('ch_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body')->nullable();
            $table->json('attachment')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->foreign('conversation_id')->references('id')->on('ch_conversations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('ch_user_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('avatar')->default('avatar.png');
            $table->boolean('dark_mode')->default(false);
            $table->string('messenger_color')->nullable();
            $table->boolean('active_status')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('ch_favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('favorite_user_id');
            $table->timestamps();

            $table->unique(['user_id', 'favorite_user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('favorite_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ch_favorites');
        Schema::dropIfExists('ch_user_settings');
        Schema::dropIfExists('ch_messages');
        Schema::dropIfExists('ch_conversation_participants');
        Schema::dropIfExists('ch_conversations');
    }
};
