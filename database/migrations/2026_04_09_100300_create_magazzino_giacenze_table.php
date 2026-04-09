<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_giacenze', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articolo_id')->constrained('magazzino_articoli');
            $table->foreignId('ubicazione_id')->nullable()->constrained('magazzino_ubicazioni');
            $table->integer('quantita')->default(0);
            $table->string('lotto')->nullable();
            $table->date('data_ultimo_carico')->nullable();
            $table->date('data_ultimo_scarico')->nullable();
            $table->unique(['articolo_id', 'ubicazione_id', 'lotto']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_giacenze');
    }
};
