<?php
/**
 * Aggiunge le fasi mancanti alle commesse senza toccare quelle esistenti.
 * Confronta MES vs Onda e crea solo le fasi che mancano.
 *
 * Uso: php fix_fasi_mancanti.php [--dry-run]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

$dryRun = in_array('--dry-run', $argv);
if ($dryRun) echo "=== DRY RUN (nessuna modifica) ===\n\n";

// Mappa fasi Onda → reparto MES (stessa del sync)
$mappaReparti = [
    'STAMPAXL106' => 'stampa offset',
    'STAMPALAMINAORO' => 'generico',
    'STAMPACALDOJOH' => 'stampa a caldo',
    'STAMPACALDOBR' => 'stampa a caldo',
    'STAMPACALDO04' => 'stampa a caldo',
    'FUSTBOBST75X106' => 'fustella piana',
    'FUSTBOBSTRILIEVI' => 'fustella piana',
    'FUSTIML75X106' => 'fustella piana',
    'FUSTSTELG33.44' => 'fustella cilindrica',
    'FUSTSTELP25.35' => 'fustella cilindrica',
    'FIN01' => 'finestratura',
    'FIN03' => 'finestratura',
    'FIN04' => 'finestratura',
    'FINESTRATURA.INT' => 'finestratura',
    'FINESTRATURA.MANUALE' => 'finestratura',
    'PI01' => 'piegaincolla',
    'PI02' => 'piegaincolla',
    'PI03' => 'piegaincolla',
    'BRT1' => 'spedizione',
    'BRT' => 'spedizione',
    'TAGLIACARTE' => 'legatoria',
    'TAGLIOINDIGO' => 'digitale',
    'PLASOFTTOUCH1' => 'plastificazione',
    'PLALUX1LATO' => 'plastificazione',
    'PLAOPA1LATO' => 'plastificazione',
    'PLAOPABV' => 'plastificazione',
    'PLALUXBV' => 'plastificazione',
    'PLASOFTBV' => 'plastificazione',
    'PLAPOLIESARG1LATO' => 'plastificazione',
    'INCOLLAGGIO.PATTINA' => 'legatoria',
    'FOILMGI' => 'finitura digitale',
    'FOIL.MGI.30M' => 'finitura digitale',
    'UVSPOT.MGI.9M' => 'finitura digitale',
    'UVSPOT.MGI.30M' => 'finitura digitale',
    'PUNTOMETALLICO' => 'legatoria',
    'FASCETTATURA' => 'legatoria',
    'SFUST' => 'fustella piana',
    'RILIEVOASECCOJOH' => 'stampa a caldo',
    'ZUND' => 'digitale',
    'DEKIA-semplice' => 'finitura digitale',
    'NUM.PROGR.' => 'legatoria',
    'NUM33.44' => 'legatoria',
    'PERF.BUC' => 'legatoria',
    'BROSSPUR' => 'legatoria',
    'PIEGA2ANTECORDONE' => 'legatoria',
    'APPL.BIADESIVO30' => 'legatoria',
    'STAMPA.ESTERNA' => 'generico',
    'accopp+fust' => 'fustella piana',
    'SFUST.IML.FUSTELLATO' => 'fustella piana',
];

// Fasi da ignorare (generiche Onda coperte da fasi specifiche MES)
$fasiIgnorate = ['STAMPA'];

// Tutte le commesse attive nel MES
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 4)
    ->distinct()
    ->pluck('ordini.commessa')
    ->sort()
    ->values();

$mappaPriorita = config('fasi_priorita');
$totaleCreate = 0;

foreach ($commesse as $commessa) {
    // Fasi nel MES (non soft-deleted), incluse quelle con stato qualsiasi
    $fasiMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->leftJoin('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
        ->where('ordini.commessa', $commessa)
        ->whereNull('ordine_fasi.deleted_at')
        ->select('fasi_catalogo.nome as fase_nome')
        ->pluck('fase_nome')
        ->filter()
        ->unique()
        ->values();

    // Fasi in Onda
    $righeOnda = DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase, f.CodMacchina, f.QtaDaLavorare
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY f.CodFase
    ", [$commessa]);

    if (empty($righeOnda)) continue;

    // Trova ordine principale per questa commessa (il primo, o quello SEMILAVSTAMPA)
    $ordine = Ordine::where('commessa', $commessa)->orderBy('id')->first();
    if (!$ordine) continue;

    // Per fasi che richiedono ordine SEMILAVSTAMPA_FUSTELLA
    $ordineSemilav = Ordine::where('commessa', $commessa)
        ->where('cod_art', 'SEMILAVSTAMPA_FUSTELLA')
        ->first();

    $fasiCreate = [];

    foreach ($righeOnda as $riga) {
        $faseNome = trim($riga->CodFase);

        // Ignora fasi generiche
        if (in_array($faseNome, $fasiIgnorate)) continue;

        // Ignora fasi EXT (esterne) — gestite separatamente
        if (str_starts_with($faseNome, 'EXT')) continue;

        // Già presente nel MES?
        if ($fasiMes->contains($faseNome)) continue;

        // Già aggiunta in questo ciclo?
        if (in_array($faseNome, $fasiCreate)) continue;

        // Determina reparto
        $repartoNome = $mappaReparti[$faseNome] ?? null;

        // Prova match con prefisso (STAMPAXL106.1, STAMPACALDOJOH0,1, ecc.)
        if (!$repartoNome) {
            foreach ($mappaReparti as $key => $rep) {
                if (str_starts_with($faseNome, $key)) {
                    $repartoNome = $rep;
                    break;
                }
            }
        }

        if (!$repartoNome) {
            echo "  SKIP: {$commessa} → {$faseNome} (reparto sconosciuto)\n";
            continue;
        }

        // Scegli ordine: fasi stampa/fustella vanno su SEMILAVSTAMPA se esiste
        $targetOrdine = $ordine;
        $fasiSemilav = ['STAMPAXL106', 'STAMPACALDOJOH', 'STAMPACALDOBR', 'STAMPACALDO',
                        'FUSTBOBST75X106', 'FUSTBOBSTRILIEVI', 'FUSTIML75X106', 'STAMPALAMINAORO'];
        if ($ordineSemilav) {
            foreach ($fasiSemilav as $prefix) {
                if (str_starts_with($faseNome, $prefix)) {
                    $targetOrdine = $ordineSemilav;
                    break;
                }
            }
        }

        $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
        $faseCatalogo = FasiCatalogo::firstOrCreate(
            ['nome' => $faseNome],
            ['reparto_id' => $reparto->id]
        );

        // Calcola priorità
        $priorita = $mappaPriorita[$faseNome] ?? $mappaPriorita[$repartoNome] ?? 0;

        $qta = (int)($riga->QtaDaLavorare ?? 0);

        echo "  ADD: {$commessa} → {$faseNome} (rep: {$repartoNome}, ordine #{$targetOrdine->id}, qta: {$qta})\n";

        if (!$dryRun) {
            OrdineFase::create([
                'ordine_id' => $targetOrdine->id,
                'fase_catalogo_id' => $faseCatalogo->id,
                'fase' => $faseNome,
                'stato' => 0,
                'priorita' => $priorita,
                'qta_fase' => $qta > 0 ? $qta : null,
                'manuale' => false,
            ]);
        }

        $fasiCreate[] = $faseNome;
        $totaleCreate++;
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Totale fasi create: {$totaleCreate}" . ($dryRun ? " (dry-run)" : "") . "\n";

if (!$dryRun && $totaleCreate > 0) {
    echo "\nRicalcolo stati...\n";
    \App\Services\FaseStatoService::ricalcolaTutti();
    echo "Fatto.\n";
}
