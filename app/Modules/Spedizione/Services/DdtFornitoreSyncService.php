<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Services;

use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Onda\Contracts\OndaErpInterface;
use Illuminate\Support\Facades\Log;

/**
 * Sync DDT a fornitore da Onda → avvia fasi esterne MES con dati DDT.
 *
 * Estratto da OndaSyncService::sincronizzaDDTFornitore (Strangler Fig).
 * Logica SQL invariata bit-for-bit per preservare comportamento produzione.
 */
final class DdtFornitoreSyncService
{
    public function __construct(
        private OndaErpInterface $onda,
    ) {}

    /**
     * Sincronizza DDT fornitore degli ultimi N giorni.
     * Avvia automaticamente le fasi esterne nel MES quando trova
     * una DDT con commessa.
     *
     * @return int Numero fasi esterne avviate.
     */
    public function sync(int $giorni = 30): int
    {
        $avviate = 0;

        $righeDDT = $this->onda->getDdtFornitoreUltimiGiorni($giorni);

        if (empty($righeDDT)) {
            return 0;
        }

        $repartoEsterno = Reparto::where('nome', 'esterno')->first();
        if (!$repartoEsterno) {
            Log::warning('DDT Fornitore sync: reparto "esterno" non trovato');
            return 0;
        }

        foreach ($righeDDT as $riga) {
            $descrizione = $riga->Descrizione ?? '';

            if (!preg_match('/Commessa n°\s*(\d+)/i', $descrizione, $m)) {
                continue;
            }

            $numGrezzo = $m[1];
            $fornitore = trim($riga->RagioneSociale ?? '');
            $idDoc = $riga->IdDoc;
            $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();

            $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
            $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            $fase = OrdineFase::whereHas('ordine', function ($q) use ($numCommessa) {
                    $q->where('commessa', $numCommessa);
                })
                ->whereHas('faseCatalogo', function ($q) use ($repartoEsterno) {
                    $q->where('reparto_id', $repartoEsterno->id);
                })
                ->whereIn('stato', [0, 1])
                ->whereNull('ddt_fornitore_id')
                ->orderBy('id')
                ->first();

            if (!$fase) {
                continue;
            }

            $fase->update([
                'esterno'          => 1,
                'stato'            => 5,
                'data_inizio'      => $dataDoc,
                'note'             => 'Inviato a: ' . $fornitore,
                'ddt_fornitore_id' => $idDoc,
            ]);

            $avviate++;

            Log::info("DDT Fornitore: avviata fase esterna #{$fase->id} per commessa {$numCommessa} (DDT {$idDoc}, fornitore: {$fornitore})");
        }

        return $avviate;
    }
}
