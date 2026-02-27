<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ean_prodotti', function (Blueprint $table) {
            $table->id();
            $table->string('articolo')->index();
            $table->string('codice_ean')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ean_prodotti');
    }
};
