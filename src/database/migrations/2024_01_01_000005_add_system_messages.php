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
            $table->string('kind', 20)->default('user')->after('user_id');
            $table->json('system_event')->nullable()->after('attachment');
        });
    }

    public function down(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropColumn(['kind', 'system_event']);
        });
    }
};
