<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chatify.tables.blocks', 'ch_user_blocks'), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('blocker_id');
            $table->unsignedBigInteger('blocked_user_id');
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_user_id']);
            $table->index('blocked_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chatify.tables.blocks', 'ch_user_blocks'));
    }
};
