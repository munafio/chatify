<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('chatify.tables.participants', 'ch_conversation_participants'), function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('last_read_at');
            $table->unsignedInteger('pin_order')->nullable()->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table(config('chatify.tables.participants', 'ch_conversation_participants'), function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'pin_order']);
        });
    }
};
