<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('ordini', function (Blueprint $table) {
    $table->id();

    // Dati OndaIQ
    $table->string('commessa')->index();
    $table->string('cliente_nome')->index();
    $table->string('cod_art')->nullable();
    $table->text('descrizione')->nullable();

    // Quantità
    $table->integer('qta_richiesta');
    $table->integer('qta_prodotta')->default(0);
    $table->string('um', 10)->default('FG');

    // Stato generale
    $table->tinyInteger('stato')->default(0); // 0=non iniziato, 1=in lavorazione, 2=terminato
    $table->integer('priorita')->default(0);

    // Date
    $table->date('data_registrazione');
    $table->date('data_prevista_consegna')->nullable();

    // Consegna
    $table->boolean('pronto_consegna')->default(false);

    //note
     // Consegna
    $table->text('note')->nullable();

    // Tempo
    $table->decimal('ore_lavorate', 6, 2)->nullable();
    $table->decimal('timeout_macchina', 6, 2)->nullable();

    $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordini');
    }
};
