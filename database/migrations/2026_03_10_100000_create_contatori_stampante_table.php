<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatori_stampante', function (Blueprint $table) {
            $table->id();
            $table->string('stampante', 50)->default('Canon iPR V900');
            $table->string('ip', 45);
            $table->unsignedInteger('totale_1')->default(0);
            $table->unsignedInteger('nero_grande')->default(0);
            $table->unsignedInteger('nero_piccolo')->default(0);
            $table->unsignedInteger('colore_grande')->default(0);
            $table->unsignedInteger('colore_piccolo')->default(0);
            $table->unsignedInteger('scansioni')->default(0);
            $table->unsignedInteger('foglio_lungo')->default(0);
            $table->timestamp('rilevato_at');
            $table->timestamps();

            $table->index('rilevato_at');
            $table->index(['stampante', 'rilevato_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatori_stampante');
    }
};
