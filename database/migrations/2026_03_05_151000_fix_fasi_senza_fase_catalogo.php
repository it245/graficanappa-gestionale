<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trova tutte le fasi con fase_catalogo_id NULL e assegna il corretto
        $fasiSenzaCatalogo = DB::table('ordine_fasi')
            ->whereNull('fase_catalogo_id')
            ->whereNotNull('fase')
            ->where('fase', '!=', '')
            ->select('id', 'fase')
            ->get();

        foreach ($fasiSenzaCatalogo as $fase) {
            $catalogo = DB::table('fasi_catalogo')
                ->where('nome', $fase->fase)
                ->first();

            if ($catalogo) {
                DB::table('ordine_fasi')
                    ->where('id', $fase->id)
                    ->update(['fase_catalogo_id' => $catalogo->id]);
            }
        }
    }

    public function down(): void
    {
        // Non reversibile
    }
};
