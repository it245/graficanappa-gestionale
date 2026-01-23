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
    Schema::create('fase_operatore', function (Blueprint $table) {
        $table->id();
        $table->foreignId('fase_id')->constrained('ordine_fasi')->onDelete('cascade');
        $table->foreignId('operatore_id')->constrained('operatori')->onDelete('cascade');
        $table->timestamp('data_inizio')->nullable();
        $table->timestamps();

        $table->unique(['fase_id', 'operatore_id']); // evita duplicati
    });
}
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fase_operatore');
    }
};
