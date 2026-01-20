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
        Schema::create('fasi_catalogo', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->foreignId('reparto_id')->constrained('reparti');
        $table->decimal('pronto_consegna', 8, 2)->nullable();
        $table->decimal('avviamento', 8, 2)->nullable();
        $table->integer('copie_ora')->nullable();
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fasi_catalogo');
    }
};
