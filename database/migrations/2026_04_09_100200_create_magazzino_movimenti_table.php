<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_movimenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articolo_id')->constrained('magazzino_articoli');
            $table->foreignId('ubicazione_id')->nullable()->constrained('magazzino_ubicazioni');
            $table->enum('tipo', ['carico', 'scarico', 'reso', 'rettifica']);
            $table->integer('quantita');
            $table->integer('giacenza_dopo');
            $table->string('lotto')->nullable();
            $table->string('fornitore')->nullable();
            $table->string('commessa')->nullable();
            $table->string('fase')->nullable();
            $table->foreignId('operatore_id')->nullable()->constrained('operatori');
            $table->string('note')->nullable();
            $table->string('foto_bolla')->nullable();
            $table->text('ocr_raw')->nullable();
            $table->timestamps();

            $table->index(['articolo_id', 'tipo']);
            $table->index('commessa');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_movimenti');
    }
};
