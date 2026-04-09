<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_articoli', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('descrizione');
            $table->string('tipo_carta')->nullable();
            $table->string('formato')->nullable();
            $table->integer('grammatura')->nullable();
            $table->decimal('spessore', 5, 3)->nullable();
            $table->string('um', 10)->default('fg');
            $table->integer('soglia_minima')->default(0);
            $table->string('fornitore')->nullable();
            $table->string('certificazioni')->nullable();
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_articoli');
    }
};
