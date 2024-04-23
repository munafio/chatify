<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatifyChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ch_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
	        $table->string('name')->nullable();
	        $table->bigInteger('owner_id')->nullable();
            $table->string('avatar')->default(config('chatify.channel_avatar.default'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ch_channels');
    }
}
