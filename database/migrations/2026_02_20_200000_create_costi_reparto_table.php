<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costi_reparto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reparto_id')->constrained('reparti')->onDelete('cascade');
            $table->decimal('costo_orario', 8, 2)->comment('Euro/ora per il reparto');
            $table->date('valido_dal');
            $table->date('valido_al')->nullable()->comment('NULL = tariffa corrente');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['reparto_id', 'valido_dal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costi_reparto');
    }
};
