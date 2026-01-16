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
       Schema::create('assegnazioni', function (Blueprint $table) {
    $table->id(); // id auto incrementale della pivot
    $table->foreignId('operatore_id')->constrained('operatori')->onDelete('cascade');
        // collega alla tabella operatori, se l'operatore viene cancellato la pivot viene cancellata
    $table->foreignId('ordine_id')->constrained('ordini')->onDelete('cascade');
        // collega alla tabella ordini
    $table->timestamps(); // created_at e updated_at automatici
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assegnazioni');
    }
};
