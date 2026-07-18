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
        Schema::table('ch_conversations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('description');
        });

        Schema::table('ch_conversation_participants', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->after('user_id');
            $table->json('permissions')->nullable()->after('role');
        });

        DB::table('ch_conversation_participants as p')
            ->join('ch_conversations as c', 'c.id', '=', 'p.conversation_id')
            ->whereColumn('p.user_id', 'c.created_by')
            ->where('c.type', 'group')
            ->update(['p.role' => 'owner']);
    }

    public function down(): void
    {
        Schema::table('ch_conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });

        Schema::table('ch_conversations', function (Blueprint $table) {
            $table->dropColumn(['description', 'avatar']);
        });
    }
};
