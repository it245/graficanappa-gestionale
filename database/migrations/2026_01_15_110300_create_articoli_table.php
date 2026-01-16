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
          Schema::create('articoli', function (Blueprint $table) {
    $table->id();

    // Relazione con ordine
    $table->foreignId('ordine_id')
          ->constrained('ordini')
          ->onDelete('cascade');

    // Dati articolo (da OndaIQ)
    $table->string('cod_art')->nullable();
    $table->text('descrizione');
    $table->integer('qta_richiesta');
    $table->integer('qta_prodotta')->default(0);
    $table->string('um', 10)->default('FG');

    // Carta / materiale principale
    $table->string('cod_carta')->nullable();
    $table->string('carta')->nullable();
    $table->integer('qta_carta')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articoli');
    }
};
