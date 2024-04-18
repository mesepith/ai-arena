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
    Schema::table('chats', function (Blueprint $table) {
        $table->char('chat_session_id', 36)->change();
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    Schema::table('chats', function (Blueprint $table) {
        $table->bigInteger('chat_session_id')->unsigned()->change();
    });
}

};
