<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Aggiungi macchina_slug a fasi_catalogo per mapping fase MES → macchina costi
        if (!Schema::hasColumn('fasi_catalogo', 'macchina_slug')) {
            Schema::table('fasi_catalogo', function (Blueprint $table) {
                $table->string('macchina_slug', 60)->nullable()->after('reparto_id');
            });
        }

        // Auto-mapping iniziale
        $mappings = [
            'xl106'           => ['STAMPAXL106%', 'STAMPA XL%', 'STAMPA'],
            'konica14000'     => ['STAMPAINDIGO%', 'STAMPAINDIGOBN%'],
            'bobst_novacut'   => ['FUSTBOBST%'],
            'heidelberg_5272' => ['FUSTCIL%'],
            'brausse105'      => ['STAMPACALDOJOH%', 'STAMPACALDO%', 'RILIEVOASECCO%'],
            'visionfold110'   => ['PI0%', 'PI1%', 'PI2%', 'PI3%'],
            'finestratrice'   => ['FIN0%'],
            'kresia'          => ['PLAS%', 'PLASTSOFT%', 'PLASTLUC%', 'PLASTOPACA%', 'VERNUV%'],
            'legraf'          => ['NUM%', 'PERF%', 'BROSS%', 'PUNTOMETA%', 'CORDONATURA%'],
            'legokart'        => ['EXTPIEGA%'],
            'sae_spotimage'   => ['UVSPOT%', 'SAE%'],
        ];

        foreach ($mappings as $slug => $patterns) {
            foreach ($patterns as $pattern) {
                DB::table('fasi_catalogo')
                    ->where('nome', 'LIKE', $pattern)
                    ->whereNull('macchina_slug')
                    ->update(['macchina_slug' => $slug]);
            }
        }

        // 2. Tabella voci costo per commessa (1 riga per voce, calcolata o override)
        Schema::create('commessa_costi_voci', function (Blueprint $table) {
            $table->id();
            $table->string('commessa', 20)->index();
            $table->string('categoria', 60); // stampa_offset, carta, fustella, finestratura, piegaincolla, stampa_caldo, esterno, altro
            $table->string('voce_chiave', 100); // es "xl106.avviamento", "xl106.fogli", "carta.totale"
            $table->string('descrizione', 200);
            $table->decimal('qta', 12, 4)->nullable();
            $table->string('udm', 20)->nullable();
            $table->decimal('prezzo_unit', 12, 4)->nullable();
            $table->decimal('importo', 12, 2);
            $table->boolean('override_manuale')->default(false);
            $table->string('autore_override', 100)->nullable();
            $table->timestamps();
            $table->unique(['commessa', 'voce_chiave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commessa_costi_voci');
        if (Schema::hasColumn('fasi_catalogo', 'macchina_slug')) {
            Schema::table('fasi_catalogo', function (Blueprint $table) {
                $table->dropColumn('macchina_slug');
            });
        }
    }
};
