<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Deduplica fasi digitale/finitura digitale: 1 sola per commessa + cod_art + descrizione + fase_catalogo.
     * Solo se i campi dell'ordine sono identici (stessa descrizione, stesso articolo).
     */
    public function up(): void
    {
        $repartiDigitali = DB::table('reparti')
            ->whereIn('nome', ['digitale', 'finitura digitale'])
            ->pluck('id');

        if ($repartiDigitali->isEmpty()) return;

        $fasiDigitali = DB::table('fasi_catalogo')
            ->whereIn('reparto_id', $repartiDigitali)
            ->pluck('id');

        if ($fasiDigitali->isEmpty()) return;

        // Trova duplicati per commessa + cod_art + descrizione + fase_catalogo_id
        $duplicati = DB::table('ordine_fasi')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->whereIn('ordine_fasi.fase_catalogo_id', $fasiDigitali)
            ->select(
                'ordini.commessa',
                'ordini.cod_art',
                'ordini.descrizione',
                'ordine_fasi.fase_catalogo_id',
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('ordini.commessa', 'ordini.cod_art', 'ordini.descrizione', 'ordine_fasi.fase_catalogo_id')
            ->having('cnt', '>', 1)
            ->get();

        $totaleEliminati = 0;

        foreach ($duplicati as $dup) {
            // Tieni la fase con stato più avanzato (o id più basso)
            $keepId = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('ordini.commessa', $dup->commessa)
                ->where('ordini.cod_art', $dup->cod_art)
                ->where('ordini.descrizione', $dup->descrizione)
                ->where('ordine_fasi.fase_catalogo_id', $dup->fase_catalogo_id)
                ->orderByDesc('ordine_fasi.stato')
                ->orderBy('ordine_fasi.id')
                ->value('ordine_fasi.id');

            // Elimina le altre (solo se stato <= 1, non toccare fasi avviate/terminate)
            $idsToDelete = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('ordini.commessa', $dup->commessa)
                ->where('ordini.cod_art', $dup->cod_art)
                ->where('ordini.descrizione', $dup->descrizione)
                ->where('ordine_fasi.fase_catalogo_id', $dup->fase_catalogo_id)
                ->where('ordine_fasi.id', '!=', $keepId)
                ->where('ordine_fasi.stato', '<=', 1)
                ->pluck('ordine_fasi.id');

            if ($idsToDelete->isNotEmpty()) {
                DB::table('fase_operatore')->whereIn('fase_id', $idsToDelete)->delete();
                $deleted = DB::table('ordine_fasi')->whereIn('id', $idsToDelete)->delete();
                $totaleEliminati += $deleted;
            }
        }

        if ($totaleEliminati > 0) {
            \Illuminate\Support\Facades\Log::info("Migration dedup digitale: eliminati {$totaleEliminati} duplicati");
        }
    }

    public function down(): void
    {
        // Non reversibile
    }
};
