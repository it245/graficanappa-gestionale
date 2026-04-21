<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            // Verifica prima se gli indici esistono già
            $existing = collect(DB::select('SHOW INDEX FROM ordine_fasi'))->pluck('Key_name')->unique()->toArray();

            if (!in_array('idx_of_ordine_fase', $existing)) {
                $table->index(['ordine_id', 'fase'], 'idx_of_ordine_fase');
            }
            if (!in_array('idx_of_stato', $existing)) {
                $table->index('stato', 'idx_of_stato');
            }
            if (!in_array('idx_of_fase_catalogo', $existing)) {
                $table->index('fase_catalogo_id', 'idx_of_fase_catalogo');
            }
            if (!in_array('idx_of_priorita', $existing)) {
                $table->index('priorita', 'idx_of_priorita');
            }
            if (!in_array('idx_of_ordine_stato', $existing)) {
                $table->index(['ordine_id', 'stato'], 'idx_of_ordine_stato');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropIndex('idx_of_ordine_fase');
            $table->dropIndex('idx_of_stato');
            $table->dropIndex('idx_of_fase_catalogo');
            $table->dropIndex('idx_of_priorita');
            $table->dropIndex('idx_of_ordine_stato');
        });
    }
};
