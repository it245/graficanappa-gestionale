<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazzino_ubicazioni', function (Blueprint $table) {
            // Categoria materiale che la zona può contenere (carta, foil, scatoloni, inchiostro, vernici)
            // Null = zona generica (può accettare qualsiasi categoria)
            $table->string('categoria', 50)->nullable()->after('codice')->index();

            // Capacità massima (es. n. pallet, kg, litri) — soft limit per warning UI
            $table->decimal('capacita_max', 10, 2)->nullable()->after('piano');

            // Priorità riempimento (più alto = preferita per nuovo carico)
            $table->integer('priorita', false, true)->default(0)->after('capacita_max');

            // Zona attiva (false = nascondi da suggerimenti)
            $table->boolean('attiva')->default(true)->after('priorita');

            // Zona/Area (es. "A", "B", "C") per raggruppamento mappa magazzino
            $table->string('zona', 10)->nullable()->after('attiva')->index();
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_ubicazioni', function (Blueprint $table) {
            $table->dropColumn(['categoria', 'capacita_max', 'priorita', 'attiva', 'zona']);
        });
    }
};
