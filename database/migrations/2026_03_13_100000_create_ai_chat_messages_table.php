<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operatore_id');
            $table->text('user_message');
            $table->text('ai_response');
            $table->timestamps();

            $table->foreign('operatore_id')->references('id')->on('operatori')->onDelete('cascade');
            $table->index('operatore_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
