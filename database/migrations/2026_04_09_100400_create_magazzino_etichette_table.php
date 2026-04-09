<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_etichette', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code')->unique();
            $table->foreignId('articolo_id')->constrained('magazzino_articoli');
            $table->foreignId('ubicazione_id')->nullable()->constrained('magazzino_ubicazioni');
            $table->foreignId('giacenza_id')->nullable()->constrained('magazzino_giacenze');
            $table->string('lotto')->nullable();
            $table->integer('quantita_iniziale');
            $table->boolean('attiva')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_etichette');
    }
};
