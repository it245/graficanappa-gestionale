<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliche_anagrafica', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->unique();
            $table->string('descrizione_raw', 500);
            $table->integer('qta')->nullable();
            $table->integer('scatola')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliche_anagrafica');
    }
};
