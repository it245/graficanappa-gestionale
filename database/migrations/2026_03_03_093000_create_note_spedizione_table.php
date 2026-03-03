<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_spedizione', function (Blueprint $table) {
            $table->id();
            $table->date('data')->unique();
            $table->text('contenuto_am')->nullable();
            $table->text('contenuto_pm')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_spedizione');
    }
};
