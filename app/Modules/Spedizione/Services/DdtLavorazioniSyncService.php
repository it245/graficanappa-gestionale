<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Onda\Contracts\OndaErpInterface;
use Illuminate\Support\Facades\Log;

/**
 * Sync DDT lavorazioni esterne (terzisti): parsing keyword multi-fase
 * dalle descrizioni DDT per marcare fasi MES come esterne.
 *
 * Estratto da OndaSyncService::sincronizzaDDTFornitureLavorazioni (Strangler Fig).
 * Logica SQL/regex invariata bit-for-bit per preservare comportamento produzione.
 */
final class DdtLavorazioniSyncService
{
    /**
     * @var array<int, array{pattern: string, fasi: array<int, string>}>
     */
    private const MAPPING_LAVORAZIONI = [
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

    public function __construct(
        private OndaErpInterface $onda,
    ) {}

    /**
     * @return int Numero fasi marcate esterne.
     */
    public function sync(int $giorni = 30): int
    {
        $righeDDT = $this->onda->getDdtFornitoreLavorazioniUltimiGiorni($giorni);

        if (empty($righeDDT)) {
            return 0;
        }

        $repartoEsterno = Reparto::firstOrCreate(['nome' => 'esterno']);
        $aggiornate = 0;

        foreach ($righeDDT as $riga) {
            $descrizione = $riga->Descrizione ?? '';
            if (!preg_match('/Commessa\s*n?[°º.]?\s*(\d{5,7})/iu', $descrizione, $m)) {
                continue;
            }

            $numGrezzo = $m[1];
            $fornitore = trim($riga->RagioneSociale ?? '');
            $idDoc = $riga->IdDoc;
            $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();
            $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
            $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            $lavorazioniTrovate = [];
            foreach (self::MAPPING_LAVORAZIONI as $map) {
                if (preg_match($map['pattern'], $descrizione)) {
                    $lavorazioniTrovate[] = $map;
                }
            }
            if (empty($lavorazioniTrovate)) {
                continue;
            }

            // Estrai keyword "tipo articolo" dalla descrizione DDT per filtrare ordini target.
            $keywordTipo = null;
            $codArtPattern = null;
            if (preg_match('/allesti(to|mento|ti|te)/iu', $descrizione)) {
                $keywordTipo = 'allestito';
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

            $ordiniTargetIds = Ordine::where('commessa', $numCommessa)
                ->when($codArtPattern, function ($q) use ($codArtPattern) {
                    $q->where(function ($q2) use ($codArtPattern) {
                        $q2->where('cod_art', 'LIKE', $codArtPattern)
                           ->orWhere('descrizione', 'LIKE', $codArtPattern);
                    });
                })
                ->pluck('id')
                ->toArray();

            if (empty($ordiniTargetIds)) {
                $ordiniTargetIds = Ordine::where('commessa', $numCommessa)->pluck('id')->toArray();
            }
            if (empty($ordiniTargetIds)) {
                continue;
            }

            $fasiGiaMatchate = [];
            foreach ($lavorazioniTrovate as $lav) {
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
                        if ($fasiCandidate->isNotEmpty()) {
                            break;
                        }
                    }
                }

                foreach ($fasiCandidate as $fase) {
                    if (in_array($fase->id, $fasiGiaMatchate, true)) {
                        continue;
                    }
                    if ($fase->esterno && $fase->ddt_fornitore_id) {
                        continue;
                    }
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

                    if ($keywordTipo !== 'allestito') {
                        break;
                    }
                }
            }
        }

        return $aggiornate;
    }
}
