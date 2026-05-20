<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commessa_totali_cache', function (Blueprint $t) {
            $t->string('commessa', 50)->primary();
            $t->string('anno_mese', 7)->nullable()->index();
            $t->decimal('totale', 12, 2)->default(0);
            $t->integer('n_voci')->default(0);
            $t->json('per_categoria')->nullable();
            $t->timestamp('calcolato_at')->useCurrent();
            $t->index('calcolato_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commessa_totali_cache');
    }
};
