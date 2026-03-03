<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Deduplica fasi fustella piana/cilindrica: 1 sola per commessa.
     * Mantiene la fase con stato più avanzato (o id più basso a parità).
     */
    public function up(): void
    {
        $repartiFustella = DB::table('reparti')
            ->whereIn('nome', ['fustella piana', 'fustella cilindrica'])
            ->pluck('id');

        if ($repartiFustella->isEmpty()) return;

        $fasiFustella = DB::table('fasi_catalogo')
            ->whereIn('reparto_id', $repartiFustella)
            ->pluck('id');

        if ($fasiFustella->isEmpty()) return;

        // Trova tutte le fasi fustella raggruppate per commessa + fase_catalogo_id
        $duplicati = DB::table('ordine_fasi')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->whereIn('ordine_fasi.fase_catalogo_id', $fasiFustella)
            ->select('ordini.commessa', 'ordine_fasi.fase_catalogo_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('ordini.commessa', 'ordine_fasi.fase_catalogo_id')
            ->having('cnt', '>', 1)
            ->get();

        $totaleEliminati = 0;

        foreach ($duplicati as $dup) {
            // Tieni la fase con stato più avanzato (o id più basso)
            $keepId = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('ordini.commessa', $dup->commessa)
                ->where('ordine_fasi.fase_catalogo_id', $dup->fase_catalogo_id)
                ->orderByDesc('ordine_fasi.stato')
                ->orderBy('ordine_fasi.id')
                ->value('ordine_fasi.id');

            // Elimina le altre (solo se stato <= 1, non toccare fasi avviate/terminate)
            $idsToDelete = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('ordini.commessa', $dup->commessa)
                ->where('ordine_fasi.fase_catalogo_id', $dup->fase_catalogo_id)
                ->where('ordine_fasi.id', '!=', $keepId)
                ->where('ordine_fasi.stato', '<=', 1)
                ->pluck('ordine_fasi.id');

            if ($idsToDelete->isNotEmpty()) {
                $deleted = DB::table('ordine_fasi')->whereIn('id', $idsToDelete)->delete();
                $totaleEliminati += $deleted;
            }
        }

        if ($totaleEliminati > 0) {
            \Illuminate\Support\Facades\Log::info("Migration dedup fustella: eliminati {$totaleEliminati} duplicati");
        }
    }

    public function down(): void
    {
        // Non reversibile
    }
};
