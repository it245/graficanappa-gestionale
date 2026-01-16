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
       Schema::create('ordine_fasi', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ordine_id')->constrained('ordini')->onDelete('cascade'); // collega l'ordine
     $table->string('fase'); //nome fase 
    $table->foreignId('operatore_id')->nullable()->constrained('operatori');    // chi lavora
    $table->tinyInteger('stato')->default(0);                                    // 0=non iniziata,1=in lavorazione,2=terminata
    $table->integer('qta_prod')->default(0);
    $table->timestamp('data_inizio')->nullable();
    $table->timestamp('data_fine')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordine_fasi'); // <-- nome corretto della tabella
    }
};
