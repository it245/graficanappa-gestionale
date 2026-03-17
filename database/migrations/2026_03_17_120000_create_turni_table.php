<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turni', function (Blueprint $table) {
            $table->id();
            $table->string('cognome_nome', 100);  // es. "MENALE BENITO"
            $table->date('data');
            $table->string('turno', 5);            // T, 1, 2, 3, F, R
            $table->time('ora_inizio')->nullable(); // override orario (es. Crisanti 09:00)
            $table->time('ora_fine')->nullable();
            $table->timestamps();

            $table->unique(['cognome_nome', 'data']);
            $table->index('data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turni');
    }
};
