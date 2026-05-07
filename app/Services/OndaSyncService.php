<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Onda\Services\CommessaSyncService;
use App\Modules\Onda\Services\OrdineSyncService;
use App\Modules\Reparti\Services\RepartoService;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Strangler Fig completato per i metodi principali (sincronizza,
 *             sincronizzaSingolaCommessa, getMappaReparti, getTipoReparto).
 *
 *             Body trasferito in:
 *               - {@see \App\Modules\Onda\Services\OrdineSyncService::sync()}
 *                 (era ~870 righe di sincronizza())
 *               - {@see \App\Modules\Onda\Services\CommessaSyncService::sync()}
 *                 (era ~280 righe di sincronizzaSingolaCommessa())
 *               - {@see \App\Modules\Reparti\Services\RepartoService::mappaSlugToId()}
 *               - {@see \App\Modules\Reparti\Services\RepartoService::tipoFromCodice()}
 *
 *             Questa classe ora è un wrapper sottile (~200 righe) che mantiene
 *             la stabile API statica per i caller legacy:
 *               - cron `php artisan onda:sync`
 *               - DashboardOwnerController, DashboardSpedizioneController
 *               - ImportExcelTutto (Reflection su getMappaReparti)
 *
 *             I metodi sincronizzaDDT* (Fornitore / FornitureLavorazioni / Vendita)
 *             restano qui finché non saranno migrati nei rispettivi moduli
 *             (Spedizione::DdtSyncService gestisce gia DDT vendita).
 */
class OndaSyncService
{
    /**
     * Adapter Onda risolto dal container (lazy).
     */
    private static function onda(): OndaErpInterface
    {
        return app(OndaErpInterface::class);
    }

    /**
     * Sincronizza ordini e fasi dal gestionale Onda al MES.
     *
     * Orchestratore THIN: delega tutto a {@see OrdineSyncService::sync()}.
     *
     * @return array<string, mixed> Riepilogo: ordini creati/aggiornati, fasi create, log dettagliato.
     */
    public static function sincronizza(): array
    {
        $r = app(OrdineSyncService::class)->sync();

        return [
            'ordini_creati'         => $r['ordini_creati'] ?? 0,
            'ordini_aggiornati'     => $r['ordini_aggiornati'] ?? 0,
            'fasi_create'           => $r['fasi_create'] ?? 0,
            'duplicati_rimossi'     => $r['duplicati_rimossi'] ?? 0,
            'log_ordini_creati'     => $r['log_ordini_creati'] ?? [],
            'log_ordini_aggiornati' => $r['log_ordini_aggiornati'] ?? [],
            'log_fasi_create'       => $r['log_fasi_create'] ?? [],
        ];
    }

    /**
     * Sincronizza una singola commessa da Onda, senza filtro data.
     * Utile per commesse vecchie che il sync normale non prende.
     *
     * Orchestratore THIN: delega a {@see CommessaSyncService::sync()}.
     */
    public static function sincronizzaSingolaCommessa(string $codCommessa): array
    {
        return app(CommessaSyncService::class)->sync($codCommessa);
    }

    /**
     * Sincronizza DDT emesse a fornitore da Onda: avvia automaticamente
     * le fasi esterne nel MES quando trova una DDT con commessa.
     */
    public static function sincronizzaDDTFornitore(): int
    {
        $avviate = 0;

        // Query DDT a fornitore degli ultimi 30 giorni (via Adapter)
        $righeDDT = self::onda()->getDdtFornitoreUltimiGiorni(30);

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

            // Estrai numero commessa dalla descrizione
            if (!preg_match('/Commessa n°\s*(\d+)/i', $descrizione, $m)) {
                continue;
            }

            $numGrezzo = $m[1];
            $fornitore = trim($riga->RagioneSociale ?? '');
            $idDoc = $riga->IdDoc;
            $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();

            // Formato MES: 00XXXXX-YY (7 cifre zero-padded + trattino + anno 2 cifre)
            $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
            $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            // Cerca la prima fase esterna non ancora avviata per questa commessa
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

    /**
     * Interpreta le descrizioni DDT fornitore per mandare fasi all'esterno.
     * Legge parole chiave (plastificare, fustellare, stampa a caldo, ecc.)
     * e marca le fasi corrispondenti come esterne.
     */
    public static function sincronizzaDDTFornitureLavorazioni(): int
    {
        $mappingLavorazioni = [
            ['pattern' => '/uv\s*spot\s*spessorat/iu', 'fasi' => ['UVSPOTSPESSEST', 'EXTUVSPOTSPESS', 'UVSPOTEST', 'EXTUVSPOTEST', 'UVSPOT.MGI.30M', 'UVSPOT.MGI.9M']],
            ['pattern' => '/uv\s*spot/iu', 'fasi' => ['UVSPOTEST', 'EXTUVSPOTEST', 'UVSPOT.MGI.30M', 'UVSPOT.MGI.9M', 'UVSPOTSPESSEST', 'EXTUVSPOTSPESS']],
            ['pattern' => '/verniciare\s*uv/iu', 'fasi' => ['UVSPOTEST', 'EXTUVSPOTEST', 'UVSPOTSPESSEST', 'EXTUVSPOTSPESS', 'UVSPOT.MGI.30M']],
            ['pattern' => '/plastificare\s*opac/iu', 'fasi' => ['PLAOPA1LATO', 'PLAOPABV', 'EXTPLAOPA']],
            ['pattern' => '/plastificare\s*lucid/iu', 'fasi' => ['PLALUX1LATO', 'PLALUXBV', 'EXTPLALUX']],
            ['pattern' => '/plastificar/iu', 'fasi' => ['PLAOPA1LATO', 'PLAOPABV', 'PLALUX1LATO', 'PLALUXBV', 'PLASOFTTOUCH1', 'EXTPLAOPA', 'EXTPLALUX']],
            ['pattern' => '/stamp\w*\s*a\s*caldo/iu', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH', 'stampalaminaoro', 'STAMPALAMINAORO']],
            ['pattern' => '/oro\s*a\s*caldo/iu', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH', 'stampalaminaoro']],
            ['pattern' => '/foil\s*oro|foil\s+\w+/iu', 'fasi' => ['FOIL.MGI.30M', 'FOIL.MGI.9M', 'FOILMGI', 'FOIL.MGI']],
            ['pattern' => '/\bfoil\b/iu', 'fasi' => ['FOIL.MGI.30M', 'FOIL.MGI.9M', 'FOILMGI', 'FOIL.MGI']],
            ['pattern' => '/fustellatura|fustellare|da\s*fustellare/iu', 'fasi' => ['FUSTBOBST75X106', 'FUSTBIML75X106', 'FUSTSTELG33.44', 'FUSTSTELP25.35', 'FUST.STARPACK.74X104']],
            ['pattern' => '/brossura\s*filo\s*refe/iu', 'fasi' => ['BROSSFILOREFE/A4EST', 'BROSSFILOREFE/A5EST', 'BROSSCOPEST']],
            ['pattern' => '/brossura\s*fresat/iu', 'fasi' => ['BROSSFRESATA/A5EST', 'BROSSFRESATA/A4EST']],
            ['pattern' => '/punt[io]\s*metallic/iu', 'fasi' => ['PUNTOMETALLICOEST', 'EXTPUNTOMETALLICOEST', 'PUNTOMETALLICO']],
            ['pattern' => '/arrotonda|angoli\s*arrotond|4\s*angoli/iu', 'fasi' => ['ARROT4ANGOLI', 'ARROTONDAMENTO', 'EXTARROT4ANGOLI']],
            ['pattern' => '/incollare|incollaggio|piega\s*incolla/iu', 'fasi' => ['PI01', 'PI02', 'PI03']],
            ['pattern' => '/accoppiar/iu', 'fasi' => ['accopp+fust', 'ACCOPPIATURA.FOGLI', 'ACCOPPIATURA.FOG.33.48INT']],
            ['pattern' => '/allestimento|allestire|allestiti?o?/iu', 'fasi' => ['Allest.Manuale', 'ALLEST.SHOPPER', 'ALLESTIMENTO.ESPOSITORI', 'PUNTOMETALLICOEST', 'EXTPUNTOMETALLICOEST', 'ARROT4ANGOLI', 'EXTARROT4ANGOLI']],
        ];

        // DDT fornitore (versione lavorazioni per parsing keyword multi-fase) — via Adapter
        $righeDDT = self::onda()->getDdtFornitoreLavorazioniUltimiGiorni(30);

        if (empty($righeDDT)) return 0;

        $repartoEsterno = Reparto::firstOrCreate(['nome' => 'esterno']);
        $aggiornate = 0;

        foreach ($righeDDT as $riga) {
            $descrizione = $riga->Descrizione ?? '';
            if (!preg_match('/Commessa\s*n?[°º.]?\s*(\d{5,7})/iu', $descrizione, $m)) continue;

            $numGrezzo = $m[1];
            $fornitore = trim($riga->RagioneSociale ?? '');
            $idDoc = $riga->IdDoc;
            $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();
            $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
            $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            $lavorazioniTrovate = [];
            foreach ($mappingLavorazioni as $map) {
                if (preg_match($map['pattern'], $descrizione)) {
                    $lavorazioniTrovate[] = $map;
                }
            }
            if (empty($lavorazioniTrovate)) continue;

            // Estrai keyword "tipo articolo" dalla descrizione DDT per filtrare ordini target.
            // Match priorita:
            // 1. ALLESTITO/ALLESTIMENTO -> match TUTTI ordini commessa (allestimento finale)
            // 2. COPERTINA -> ordine I.copertina
            // 3. OPUSCOL/CATALOGO senza ALLESTI -> ordine I.Opuscol* (interno)
            // 4. ASTUCC -> ordine I.Astucci o cod_art con ASTUCC
            // 5. SCHED/CARTELLIN/etc -> match generico cod_art
            $keywordTipo = null;
            $codArtPattern = null;
            if (preg_match('/allesti(to|mento|ti|te)/iu', $descrizione)) {
                $keywordTipo = 'allestito';   // tutti gli ordini
            } elseif (preg_match('/coperti?/iu', $descrizione)) {
                $keywordTipo = 'copertina';
                $codArtPattern = '%coper%';
            } elseif (preg_match('/(opuscol|catalogo)/iu', $descrizione)) {
                $keywordTipo = 'opuscolo';
                $codArtPattern = '%opuscol%';
            } elseif (preg_match('/astucc/iu', $descrizione)) {
                $keywordTipo = 'astuccio';
                $codArtPattern = '%astucci%';
            } elseif (preg_match('/(scheda|schede)/iu', $descrizione)) {
                $keywordTipo = 'scheda';
                $codArtPattern = '%schede%';
            }

            // Trova ordini target: se keyword specifica esiste, filtra; altrimenti tutti.
            $ordiniTargetIds = \App\Models\Ordine::where('commessa', $numCommessa)
                ->when($codArtPattern, function ($q) use ($codArtPattern) {
                    $q->where(function ($q2) use ($codArtPattern) {
                        $q2->where('cod_art', 'LIKE', $codArtPattern)
                           ->orWhere('descrizione', 'LIKE', $codArtPattern);
                    });
                })
                ->pluck('id')
                ->toArray();

            // Fallback: se filtro non trova niente, usa tutti gli ordini commessa
            if (empty($ordiniTargetIds)) {
                $ordiniTargetIds = \App\Models\Ordine::where('commessa', $numCommessa)->pluck('id')->toArray();
            }
            if (empty($ordiniTargetIds)) continue;

            $fasiGiaMatchate = [];
            foreach ($lavorazioniTrovate as $lav) {
                // Cerca TUTTE le fasi candidate (non solo prima) per matchare ogni ordine target
                $fasiCandidate = OrdineFase::whereIn('ordine_id', $ordiniTargetIds)
                    ->whereIn('fase', $lav['fasi'])
                    ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
                    ->whereNull('ddt_fornitore_id')
                    ->whereRaw("stato REGEXP '^[0-9]+$' AND stato < 3")
                    ->orderBy('id')
                    ->get();

                if ($fasiCandidate->isEmpty()) {
                    foreach ($lav['fasi'] as $nomeFase) {
                        $fasiCandidate = OrdineFase::whereIn('ordine_id', $ordiniTargetIds)
                            ->where('fase', 'LIKE', $nomeFase . '%')
                            ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
                            ->whereNull('ddt_fornitore_id')
                            ->whereRaw("stato REGEXP '^[0-9]+$' AND stato < 3")
                            ->orderBy('id')
                            ->get();
                        if ($fasiCandidate->isNotEmpty()) break;
                    }
                }

                foreach ($fasiCandidate as $fase) {
                    if (in_array($fase->id, $fasiGiaMatchate)) continue;
                    if ($fase->esterno && $fase->ddt_fornitore_id) continue;
                    $fasiGiaMatchate[] = $fase->id;

                    $fase->update([
                        'esterno' => 1,
                        'stato' => 5,
                        'data_inizio' => $dataDoc,
                        'note' => 'Inviato a: ' . $fornitore,
                        'ddt_fornitore_id' => $idDoc,
                    ]);

                    $aggiornate++;
                    Log::info("DDT Fornitore lavorazione: fase {$fase->fase} (#{$fase->id}) ord={$fase->ordine_id} → esterno per commessa {$numCommessa} (DDT {$idDoc}, fornitore: {$fornitore}, keyword: " . ($keywordTipo ?? 'tutti') . ")");

                    // Per keyword specifica (copertina/opuscolo/ecc.) prendiamo solo 1 fase per pattern.
                    // Per "allestito" (ordini multipli) prendiamo tutte le fasi candidate.
                    if ($keywordTipo !== 'allestito') break;
                }
            }
        }

        return $aggiornate;
    }

    /**
     * @deprecated Wrapper di compatibilità. Usa {@see \App\Modules\Spedizione\Services\DdtSyncService::syncFromOnda()}.
     *
     * Mantenuto per non rompere i caller esistenti (cron `php artisan onda:sync`,
     * DashboardOwnerController, DashboardSpedizioneController).
     */
    public static function sincronizzaDDTVendita(): int
    {
        $risultato = app(\App\Modules\Spedizione\Services\DdtSyncService::class)->syncFromOnda(7);

        return ($risultato['inseriti'] ?? 0) + ($risultato['aggiornati'] ?? 0);
    }

    /**
     * Mappa codice fase Onda → reparto MES.
     *
     * @deprecated Migrato in {@see RepartoService::mappaSlugToId()}.
     *             Wrapper mantenuto per backward-compat con ImportExcelTutto
     *             (Reflection) e script standalone (import_commessa.php,
     *             import_commessa_onda.php, confronta_tutte.php).
     *
     * @return array<string, string> Chiave = codice fase Onda, valore = nome reparto.
     */
    public static function getMappaReparti(): array
    {
        return app(RepartoService::class)->mappaSlugToId();
    }

    /**
     * Mappa codice fase Onda → tipo (multifase/monofase/max 2 fasi).
     *
     * @deprecated Migrato in {@see RepartoService::tipoFromCodice()}.
     *
     * @return array<string, string> Chiave = codice fase Onda, valore = tipo reparto.
     */
    public static function getTipoReparto(): array
    {
        return app(RepartoService::class)->tipoFromCodice();
    }
}
