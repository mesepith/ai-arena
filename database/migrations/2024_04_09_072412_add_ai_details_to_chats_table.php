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
            $table->string('ai_model')->after('ai_response')->nullable();
            $table->string('service_by')->after('ai_model')->default('openai');
            $table->unsignedInteger('prompt_tokens')->after('service_by')->nullable();
            $table->unsignedInteger('completion_tokens')->after('prompt_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->after('completion_tokens')->nullable();
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
            $table->dropColumn(['ai_model', 'service_by', 'prompt_tokens', 'completion_tokens', 'total_tokens']);
        });
    }
};
