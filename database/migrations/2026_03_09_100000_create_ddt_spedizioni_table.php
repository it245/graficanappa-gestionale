<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ddt_spedizioni', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onda_id_doc');
            $table->string('numero_ddt', 20);
            $table->date('data_ddt')->nullable();
            $table->string('vettore', 100)->nullable();
            $table->string('cliente_nome', 150)->nullable();
            $table->string('commessa', 20);
            $table->unsignedBigInteger('ordine_id')->nullable();
            $table->decimal('qta', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['onda_id_doc', 'commessa']);
            $table->index('numero_ddt');
            $table->index('vettore');
            $table->foreign('ordine_id')->references('id')->on('ordini')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ddt_spedizioni');
    }
};
