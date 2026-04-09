<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazzino_ubicazioni', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('corridoio');
            $table->string('scaffale');
            $table->string('piano')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazzino_ubicazioni');
    }
};
