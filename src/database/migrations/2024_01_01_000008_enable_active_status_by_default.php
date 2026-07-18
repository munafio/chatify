<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('chatify.tables.user_settings', 'ch_user_settings');

        DB::table($table)
            ->where('active_status', false)
            ->update(['active_status' => true]);
    }

    public function down(): void
    {
        // Cannot reliably restore prior per-user preferences.
    }
};
