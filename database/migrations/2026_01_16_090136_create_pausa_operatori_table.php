<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausa_operatores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operatore_id')->constrained('operatori')->onDelete('cascade');
            $table->foreignId('ordine_id')->constrained('ordini')->onDelete('cascade');
            $table->string('fase'); // STAMPA, PIEGAINCOLLA ecc.
            $table->string('motivo'); // motivo della pausa
            $table->timestamp('data_ora')->useCurrent(); // quando inizia la pausa
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausa_operatores');
    }
};
