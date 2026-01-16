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
        Schema::create('operatori', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->string('cognome')->nullable();
        $table->string('codice_operatore')->unique(); // badge / codice login
        $table->string('ruolo')->default('operatore'); // operatore | superadmin
        $table->boolean('attivo')->default(true);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operatori');
    }
};
