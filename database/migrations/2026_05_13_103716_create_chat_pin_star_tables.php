<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pin con scadenza (banner alto chat). 1 pin attivo per canale alla volta.
        Schema::create('chat_message_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->string('canale', 50);
            $table->unsignedBigInteger('pinned_by'); // operatore_id che ha fissato
            $table->timestamp('scade_at')->nullable(); // null = illimitato
            $table->timestamps();
            $table->index(['canale', 'scade_at']);
        });

        // Stelline personali (importanti per utente)
        Schema::create('chat_message_stars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('operatore_id');
            $table->timestamps();
            $table->unique(['chat_message_id', 'operatore_id']);
            $table->index('operatore_id');
        });

        // Rimuovi is_pinned dal chat_messages (sostituito da pins separati)
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_stars');
        Schema::dropIfExists('chat_message_pins');
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false);
            }
        });
    }
};
