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
       Schema::create('operatore_reparto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('operatore_id')->constrained('operatori')->onDelete('cascade');
    $table->foreignId('reparto_id')->constrained()->onDelete('cascade');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operatore_reparto');
    }
};
