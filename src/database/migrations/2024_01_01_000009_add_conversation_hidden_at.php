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
            $table->timestamp('hidden_at')->nullable()->after('pin_order');
        });
    }

    public function down(): void
    {
        Schema::table(config('chatify.tables.participants', 'ch_conversation_participants'), function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });
    }
};
