<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operatore_id')->constrained('operatori')->onDelete('cascade');
            $table->text('nota');
            $table->string('destinazione')->default('tutti'); // "tutti", nome reparto, o "operatore:ID"
            $table->boolean('letta')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_turno');
    }
};
