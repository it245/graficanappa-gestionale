<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazzino_articoli', function (Blueprint $table) {
            // Ubicazione preferita dove va sempre stoccato questo articolo.
            // Usata da SuggerisciUbicazioneService per dare risposta immediata
            // al bot Telegram (e UI carico): "metti in Zona A1".
            // Null = nessuna preferenza (fallback a categoria su ubicazione).
            $table->foreignId('ubicazione_preferita_id')
                ->nullable()
                ->after('fornitore')
                ->constrained('magazzino_ubicazioni')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_articoli', function (Blueprint $table) {
            $table->dropForeign(['ubicazione_preferita_id']);
            $table->dropColumn('ubicazione_preferita_id');
        });
    }
};
