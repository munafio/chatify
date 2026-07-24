<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conversations = config('chatify.tables.conversations', 'ch_conversations');
        $participants = config('chatify.tables.participants', 'ch_conversation_participants');
        $messages = config('chatify.tables.messages', 'ch_messages');
        $favorites = config('chatify.tables.favorites', 'ch_favorites');
        $blocks = config('chatify.tables.blocks', 'ch_user_blocks');
        $userSettings = config('chatify.tables.user_settings', 'ch_user_settings');
        $messageUserStates = 'ch_message_user_states';

        Schema::dropIfExists($messageUserStates);
        Schema::dropIfExists($favorites);
        Schema::dropIfExists($messages);
        Schema::dropIfExists($blocks);
        Schema::dropIfExists($participants);
        Schema::dropIfExists($conversations);
        Schema::dropIfExists($userSettings);

        Schema::create($conversations, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 20)->default('direct');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create($participants, function (Blueprint $table) use ($conversations) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 20)->default('member');
            $table->json('permissions')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('pin_order')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'conversation_id']);
            $table->foreign('conversation_id')->references('id')->on($conversations)->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create($messages, function (Blueprint $table) use ($conversations, $messages) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->string('kind', 20)->default('user');
            $table->text('body')->nullable();
            $table->json('attachment')->nullable();
            $table->json('system_event')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->uuid('reply_to_message_id')->nullable();
            $table->uuid('forwarded_from_message_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->foreign('conversation_id')->references('id')->on($conversations)->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reply_to_message_id')->references('id')->on($messages)->nullOnDelete();
            $table->foreign('forwarded_from_message_id')->references('id')->on($messages)->nullOnDelete();
        });

        Schema::create($userSettings, function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('avatar')->default('avatar.png');
            $table->boolean('dark_mode')->default(false);
            $table->string('messenger_color')->nullable();
            $table->json('theme_preferences')->nullable();
            $table->string('chat_background')->nullable();
            $table->boolean('active_status')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create($favorites, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('favorite_user_id');
            $table->timestamps();

            $table->unique(['user_id', 'favorite_user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('favorite_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create($messageUserStates, function (Blueprint $table) use ($messages) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->foreign('message_id')->references('id')->on($messages)->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create($blocks, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('blocker_id');
            $table->unsignedBigInteger('blocked_user_id');
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_user_id']);
            $table->index('blocked_user_id');
        });

        DB::table("{$participants} as p")
            ->join("{$conversations} as c", 'c.id', '=', 'p.conversation_id')
            ->whereColumn('p.user_id', 'c.created_by')
            ->where('c.type', 'group')
            ->update(['p.role' => 'owner']);
    }

    public function down(): void
    {
        $conversations = config('chatify.tables.conversations', 'ch_conversations');
        $participants = config('chatify.tables.participants', 'ch_conversation_participants');
        $messages = config('chatify.tables.messages', 'ch_messages');
        $favorites = config('chatify.tables.favorites', 'ch_favorites');
        $blocks = config('chatify.tables.blocks', 'ch_user_blocks');
        $userSettings = config('chatify.tables.user_settings', 'ch_user_settings');
        $messageUserStates = 'ch_message_user_states';

        Schema::dropIfExists($messageUserStates);
        Schema::dropIfExists($blocks);
        Schema::dropIfExists($favorites);
        Schema::dropIfExists($userSettings);
        Schema::dropIfExists($messages);
        Schema::dropIfExists($participants);
        Schema::dropIfExists($conversations);
    }
};
