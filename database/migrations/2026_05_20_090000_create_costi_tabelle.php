<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogo macchine costi
        Schema::create('macchine_costi', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique(); // xl106, konica14000, bobst_novacut, ecc
            $table->string('nome', 200);
            $table->string('tipo', 30)->default('produzione'); // stampa_offset, stampa_digitale, fustella, ecc
            $table->string('formato_max', 30)->nullable();
            $table->string('formato_min', 30)->nullable();
            $table->decimal('velocita_max', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Costi avviamento per configurazione (es 4/0 = €115, 5/0 + UV = €140)
        Schema::create('costi_avviamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->string('configurazione', 100);
            $table->integer('gruppi_usati')->nullable();
            $table->decimal('costo_avviamento', 10, 2);
            $table->integer('tempo_avviamento_min')->nullable();
            $table->integer('fogli_avviamento')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Costi stampa per fascia tiratura (XL106)
        Schema::create('costi_stampa_fasce', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->integer('da_fogli');
            $table->integer('a_fogli')->nullable(); // null = infinito
            $table->decimal('costo_4_0', 8, 4)->nullable();
            $table->decimal('costo_5_0', 8, 4)->nullable();
            $table->decimal('costo_uv', 8, 4)->nullable();
            $table->decimal('costo_dripoff', 8, 4)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Costi click digitale (Konica 14000)
        Schema::create('costi_click_digitale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->string('tipo_click', 60); // colore_cmyk, bn, bianco
            $table->string('formato', 30);    // 330x700, SRA3, A3, A4
            $table->decimal('costo_click', 8, 4);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Costi aggiuntivi (cambio lastra, pantone, vernice, ecc)
        Schema::create('costi_aggiuntivi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macchina_id')->constrained('macchine_costi')->cascadeOnDelete();
            $table->string('voce', 200);
            $table->string('udm', 30)->nullable(); // cad, €/kg, €/foglio, ecc
            $table->decimal('costo', 10, 4);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Materie prime — listino carte
        Schema::create('materie_prime_carte', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_cartone', 60)->nullable();        // CKB, GC1, GC2, SBS, ecc
            $table->string('nome', 200);                            // CKB Stora Enso, Patinata lucida, ecc
            $table->string('grammatura', 50);                       // 195 g/m², 220-415 g/m²
            $table->string('formato', 30)->nullable();              // 64×88, 70×100, ecc
            $table->decimal('eur_ton_alto', 10, 2)->nullable();    // ≥5.000 kg
            $table->decimal('eur_ton_medio', 10, 2)->nullable();   // 2501-4999 kg
            $table->decimal('eur_ton_basso', 10, 2)->nullable();   // 500-2500 kg
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
        Schema::dropIfExists('costi_click_digitale');
        Schema::dropIfExists('costi_stampa_fasce');
        Schema::dropIfExists('costi_avviamento');
        Schema::dropIfExists('macchine_costi');
        Schema::dropIfExists('materie_prime_carte');
    }
};
