<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commessa_dati_costi', function (Blueprint $table) {
            $table->id();
            $table->string('commessa', 20)->unique();
            $table->integer('fogli_utilizzati')->nullable();
            $table->decimal('tiri_cm_foil', 12, 2)->nullable();
            $table->decimal('inchiostro_g', 12, 2)->nullable();
            $table->integer('scarti_fogli')->nullable();
            $table->string('autore', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commessa_dati_costi');
    }
};
