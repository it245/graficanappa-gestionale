<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensori_ydigital', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50);
            $table->string('sensor_name', 50);
            $table->string('macchina', 20)->nullable(); // codice macchina scheduler (XL106/BOBST/...)
            $table->string('descrizione')->nullable();
            $table->boolean('attivo')->default(true);
            $table->decimal('ultimo_value', 12, 2)->nullable();
            $table->timestamp('ultimo_ts')->nullable();
            $table->decimal('ultimo_delta', 12, 2)->nullable(); // incremento ultimo poll
            $table->timestamps();
            $table->unique(['device_id', 'sensor_name']);
        });

        // Tabella letture (audit: ogni delta registrato)
        Schema::create('sensori_letture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensore_id')->constrained('sensori_ydigital')->cascadeOnDelete();
            $table->decimal('value', 12, 2);
            $table->decimal('delta', 12, 2);
            $table->timestamp('letto_at');
            $table->unsignedBigInteger('ordine_fase_id')->nullable(); // a quale fase ha contribuito
            $table->timestamps();
            $table->index('letto_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensori_letture');
        Schema::dropIfExists('sensori_ydigital');
    }
};
