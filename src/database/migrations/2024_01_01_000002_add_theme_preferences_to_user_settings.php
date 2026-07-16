<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ch_user_settings', function (Blueprint $table) {
            $table->json('theme_preferences')->nullable()->after('messenger_color');
            $table->string('chat_background')->nullable()->after('theme_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('ch_user_settings', function (Blueprint $table) {
            $table->dropColumn(['theme_preferences', 'chat_background']);
        });
    }
};
