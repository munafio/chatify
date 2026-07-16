<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('attachment');
            $table->uuid('reply_to_message_id')->nullable()->after('edited_at');
            $table->uuid('forwarded_from_message_id')->nullable()->after('reply_to_message_id');

            $table->foreign('reply_to_message_id')->references('id')->on('ch_messages')->nullOnDelete();
            $table->foreign('forwarded_from_message_id')->references('id')->on('ch_messages')->nullOnDelete();
        });

        Schema::create('ch_message_user_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->foreign('message_id')->references('id')->on('ch_messages')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ch_message_user_states');

        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropForeign(['forwarded_from_message_id']);
            $table->dropColumn(['edited_at', 'reply_to_message_id', 'forwarded_from_message_id']);
        });
    }
};
