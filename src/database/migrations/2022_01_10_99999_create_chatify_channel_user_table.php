<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ch_channel_user', function (Blueprint $table) {
	        $table->string('channel_id');
	        $table->unsignedBigInteger('user_id');
	        
	        $table->foreign('channel_id')->references('id')->on('ch_channels');
	        $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ch_channel_user');
    }
};
