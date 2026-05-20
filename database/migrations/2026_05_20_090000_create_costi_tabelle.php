<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogo macchine
        Schema::create('macchine_costi', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nome', 200);
            $table->string('tipo', 30)->default('produzione'); // stampa_offset, stampa_digitale, fustella, piega_incolla, stampa_caldo, finestratura, plastificazione, legatoria
            $table->string('formato_max', 30)->nullable();
            $table->string('formato_min', 30)->nullable();
            $table->decimal('velocita_max', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Costi avviamento per configurazione
        Schema::create('costi_avviamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->string('configurazione', 100);
            $table->integer('gruppi_usati')->nullable();
            $table->decimal('costo_avviamento', 10, 2);
            $table->integer('tempo_avviamento_min')->nullable();
            $table->integer('fogli_avviamento')->nullable();
            $table->decimal('costo_cliche', 10, 2)->nullable(); // per Brausse + altri
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Costi per fascia tiratura — generica
        // udm = foglio, colpo, click, pz, mq, ora
        // variante = 4/0, 5/0, +UV, drip-off, standard, sfridatura, lineare, crash-lock, 4-6 punti, caldo, rilievo, ecc
        Schema::create('costi_fasce_tiratura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->integer('da_qta');
            $table->integer('a_qta')->nullable();
            $table->string('variante', 60);
            $table->string('udm', 20)->default('foglio');
            $table->decimal('costo', 10, 4);
            $table->string('formato', 30)->nullable(); // per Konica: SRA3, A3, A4, 330x700
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['macchina_id', 'da_qta', 'a_qta']);
        });

        // Costi aggiuntivi (cambio lastra, pantone, vernice, setup, dato variabile, ecc)
        Schema::create('costi_aggiuntivi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->string('voce', 200);
            $table->string('udm', 30)->nullable();
            $table->decimal('costo', 10, 4);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Dati grezzi Excel — backup completo di ogni sheet (per accesso ad-hoc)
        Schema::create('costi_raw_excel', function (Blueprint $table) {
            $table->id();
            $table->string('file', 100);            // GN_Materie_Prime.xlsx o GN_Database_Costi_Completo.xlsx
            $table->string('sheet', 100);
            $table->integer('riga');
            $table->json('dati');                    // array celle riga (preserva tutto)
            $table->timestamps();
            $table->index(['file', 'sheet']);
        });

        // Materie prime — carte
        Schema::create('materie_prime_carte', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_cartone', 60)->nullable();
            $table->string('nome', 200);
            $table->string('grammatura', 50);
            $table->string('formato', 30)->nullable();
            $table->decimal('eur_ton_alto', 10, 2)->nullable();
            $table->decimal('eur_ton_medio', 10, 2)->nullable();
            $table->decimal('eur_ton_basso', 10, 2)->nullable();
            $table->decimal('eur_kg', 10, 4)->nullable();
            $table->decimal('eur_foglio', 10, 4)->nullable();
            $table->string('fornitore', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costi_aggiuntivi');
        Schema::dropIfExists('costi_fasce_tiratura');
        Schema::dropIfExists('costi_avviamento');
        Schema::dropIfExists('macchine_costi');
        Schema::dropIfExists('materie_prime_carte');
        Schema::dropIfExists('costi_raw_excel');
    }
};
