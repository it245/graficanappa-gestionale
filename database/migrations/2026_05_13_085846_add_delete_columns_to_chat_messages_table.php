<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Eliminazione totale (per tutti): soft delete standard
            if (!Schema::hasColumn('chat_messages', 'deleted_at')) {
                $table->softDeletes();
            }
            // Lista operatori che hanno eliminato il messaggio "per se'"
            // (JSON array di operatore_id). Il messaggio resta in DB ma non
            // viene mostrato a questi utenti.
            if (!Schema::hasColumn('chat_messages', 'hidden_for')) {
                $table->json('hidden_for')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'hidden_for')) {
                $table->dropColumn('hidden_for');
            }
            if (Schema::hasColumn('chat_messages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
