<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_message_letture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('operatore_id');
            $table->timestamp('letto_at');
            $table->timestamps();

            $table->unique(['chat_message_id', 'operatore_id']);
            $table->index('operatore_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_letture');
    }
};
