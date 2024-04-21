<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats_title', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->char('chat_session_id', 36);
            $table->string('title');
            $table->timestamps();
            $table->tinyInteger('status')->default(1);
            $table->foreign('chat_id')->references('id')->on('chats');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats_title');
    }
};
