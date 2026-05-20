<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('macchine_costi', function (Blueprint $table) {
            $table->enum('categoria', ['macchina_interna', 'fornitore_esterno', 'ctp', 'materia_prima'])
                ->default('macchina_interna')->after('tipo');
        });

        // Re-classifica esistenti
        DB::table('macchine_costi')->whereIn('slug', [
            'xl106', 'konica14000', 'bobst_novacut', 'heidelberg_5272',
            'visionfold110', 'brausse105', 'finestratrice',
        ])->update(['categoria' => 'macchina_interna']);

        DB::table('macchine_costi')->whereIn('slug', [
            'kresia', 'legraf', 'legokart', 'sae_spotimage',
        ])->update(['categoria' => 'fornitore_esterno']);

        DB::table('macchine_costi')->where('slug', 'ctp_agfa')->update(['categoria' => 'ctp']);
    }

    public function down(): void
    {
        Schema::table('macchine_costi', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
