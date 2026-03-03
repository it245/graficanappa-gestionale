<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Trova e pulisce ordini duplicati (stessa commessa + cod_art + descrizione).
     * Per ogni gruppo di duplicati: tiene l'ordine con fasi in stato più avanzato,
     * elimina le fasi duplicate degli ordini ridondanti.
     */
    public function up(): void
    {
        // Trova gruppi di ordini duplicati (stessa commessa + cod_art)
        $duplicati = DB::table('ordini')
            ->select('commessa', 'cod_art', DB::raw('COUNT(*) as cnt'))
            ->groupBy('commessa', 'cod_art')
            ->having('cnt', '>', 1)
            ->get();

        $totaleOrdiniFasi = 0;

        foreach ($duplicati as $dup) {
            // Prendi tutti gli ordini di questo gruppo
            $ordini = DB::table('ordini')
                ->where('commessa', $dup->commessa)
                ->where('cod_art', $dup->cod_art)
                ->orderBy('id')
                ->get();

            if ($ordini->count() <= 1) continue;

            // Trova l'ordine "principale" (quello con fasi in stato più alto)
            $bestOrdineId = null;
            $bestMaxStato = -1;
            foreach ($ordini as $ordine) {
                $maxStato = DB::table('ordine_fasi')
                    ->where('ordine_id', $ordine->id)
                    ->max('stato') ?? -1;

                if ($maxStato > $bestMaxStato || ($maxStato == $bestMaxStato && $bestOrdineId === null)) {
                    $bestMaxStato = $maxStato;
                    $bestOrdineId = $ordine->id;
                }
            }

            // Per ogni ordine ridondante, elimina le fasi duplicate
            foreach ($ordini as $ordine) {
                if ($ordine->id == $bestOrdineId) continue;

                $fasi = DB::table('ordine_fasi')
                    ->where('ordine_id', $ordine->id)
                    ->get();

                foreach ($fasi as $fase) {
                    // Controlla se l'ordine principale ha già questa fase
                    $existsOnBest = DB::table('ordine_fasi')
                        ->where('ordine_id', $bestOrdineId)
                        ->where('fase_catalogo_id', $fase->fase_catalogo_id)
                        ->exists();

                    if ($existsOnBest) {
                        // Fase duplicata: elimina (solo se stato <= 2 e l'ordine principale ha stato >= di questa)
                        $bestFaseStato = DB::table('ordine_fasi')
                            ->where('ordine_id', $bestOrdineId)
                            ->where('fase_catalogo_id', $fase->fase_catalogo_id)
                            ->max('stato');

                        if ($fase->stato <= $bestFaseStato) {
                            DB::table('fase_operatore')->where('fase_id', $fase->id)->delete();
                            DB::table('ordine_fasi')->where('id', $fase->id)->delete();
                            $totaleOrdiniFasi++;
                        }
                    }
                }
            }
        }

        if ($totaleOrdiniFasi > 0) {
            Log::info("Migration dedup ordini: eliminate {$totaleOrdiniFasi} fasi su ordini duplicati");
        }
    }

    public function down(): void
    {
        // Non reversibile
    }
};
