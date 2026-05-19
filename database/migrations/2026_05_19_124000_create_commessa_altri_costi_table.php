<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commessa_altri_costi', function (Blueprint $table) {
            $table->id();
            $table->string('commessa', 20)->index();
            $table->enum('categoria', [
                'cliche', 'fustella', 'lavorazione_esterna', 'trasporto',
                'prove_colore', 'materiale_ausiliario', 'altro',
            ])->default('altro');
            $table->string('descrizione', 500)->nullable();
            $table->decimal('importo', 10, 2);
            $table->date('data');
            $table->string('autore', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commessa_altri_costi');
    }
};
