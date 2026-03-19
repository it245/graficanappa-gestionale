<?php
/**
 * Legge DDT emesse a fornitore da Onda, interpreta le lavorazioni dalla descrizione
 * e marca le fasi corrispondenti come esterne nel MES.
 *
 * Uso: php sync_ddt_esterne.php [--dry-run]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Models\FasiCatalogo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$dryRun = in_array('--dry-run', $argv ?? []);
if ($dryRun) echo "*** DRY RUN — nessuna modifica ***\n\n";

// Mapping parole chiave → codici fase nel MES
$mappingLavorazioni = [
    // UV Spot
    ['pattern' => '/uv\s*spot\s*spessorat/i', 'fasi' => ['UVSPOTSPESSEST', 'UVSPOTEST', 'UVSPOT.MGI.30M', 'UVSPOT.MGI.9M']],
    ['pattern' => '/uv\s*spot/i', 'fasi' => ['UVSPOTEST', 'UVSPOT.MGI.30M', 'UVSPOT.MGI.9M', 'UVSPOTSPESSEST']],
    ['pattern' => '/verniciare\s*uv/i', 'fasi' => ['UVSPOTEST', 'UVSPOTSPESSEST', 'UVSPOT.MGI.30M']],
    ['pattern' => '/uv\s*serigrafic/i', 'fasi' => ['UVSERIGRAFICOEST']],

    // Plastificazione
    ['pattern' => '/plastificare\s*opac/i', 'fasi' => ['PLAOPA1LATO', 'PLAOPABV']],
    ['pattern' => '/plastificare\s*lucid/i', 'fasi' => ['PLALUX1LATO', 'PLALUXBV']],
    ['pattern' => '/plastificar/i', 'fasi' => ['PLAOPA1LATO', 'PLAOPABV', 'PLALUX1LATO', 'PLALUXBV', 'PLASOFTTOUCH1']],

    // Stampa a caldo
    ['pattern' => '/stamp\w*\s*a\s*caldo/i', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH', 'stampalaminaoro', 'STAMPALAMINAORO']],
    ['pattern' => '/oro\s*a\s*caldo/i', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH', 'stampalaminaoro']],
    ['pattern' => '/clich[eè]\s.*foil/i', 'fasi' => ['STAMPACALDOJOHEST', 'STAMPACALDOJOH']],

    // Fustellatura
    ['pattern' => '/fustellatura|fustellare|da\s*fustellare/i', 'fasi' => ['FUSTBOBST75X106', 'FUSTBIML75X106', 'FUSTSTELG33.44', 'FUSTSTELP25.35', 'FUST.STARPACK.74X104']],

    // Legatoria / Brossura
    ['pattern' => '/brossura\s*filo\s*refe/i', 'fasi' => ['BROSSFILOREFE/A4EST', 'BROSSFILOREFE/A5EST', 'BROSSCOPEST']],
    ['pattern' => '/brossura\s*fresat/i', 'fasi' => ['BROSSFRESATA/A5EST', 'BROSSFRESATA/A4EST']],
    ['pattern' => '/punt[io]\s*metallic/i', 'fasi' => ['PUNTOMETALLICOEST', 'PUNTOMETALLICO']],
    ['pattern' => '/cartonato/i', 'fasi' => ['CARTONATO.GEN', 'EXTCARTONATO.GEN', 'EXTCARTONATO']],

    // Piegaincolla / Incollaggio
    ['pattern' => '/incollare|incollaggio|piega\s*incolla/i', 'fasi' => ['PI01', 'PI02', 'PI03']],

    // Accoppiatura
    ['pattern' => '/accoppiar/i', 'fasi' => ['ACCOPPIATURA.FOGLI', 'ACCOPPIATURA.FOG.33.48INT']],

    // Allestimento
    ['pattern' => '/allestimento|allestire/i', 'fasi' => ['Allest.Manuale', 'ALLEST.SHOPPER', 'ALLESTIMENTO.ESPOSITORI']],

    // Stampa esterna
    ['pattern' => '/stampa\s*digital\w*\s*bianc/i', 'fasi' => ['STAMPAINDIGOBIANCO', 'STAMPAINDIGO']],
    ['pattern' => '/stampar\w*.*\d+\s*color/i', 'fasi' => ['STAMPA.OFFSET11.EST', 'STAMPABUSTE.EST']],

    // Piega
    ['pattern' => '/piega\s*a\s*\d+\s*ante|piega\s*fisarmonic/i', 'fasi' => ['PIEGA2ANTESINGOLO', 'PIEGA3ANTESINGOLO', 'PIEGA6ANTESINGOLO', 'PIEGA8ANTESINGOLO']],
];

// Query DDT a fornitore
$righeDDT = DB::connection('onda')->select("
    SELECT t.IdDoc, t.DataDocumento, t.DataRegistrazione, t.IdAnagrafica,
           a.RagioneSociale, r.Descrizione, r.Qta, r.CodUnMis
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.TipoDocumento = 7
      AND t.DataRegistrazione >= DATEADD(day, -60, GETDATE())
    ORDER BY t.DataRegistrazione DESC
");

echo "DDT fornitore trovate: " . count($righeDDT) . " righe\n\n";

$repartoEsterno = Reparto::firstOrCreate(['nome' => 'esterno']);
$aggiornate = 0;
$giaEsterne = 0;
$nonTrovate = 0;

foreach ($righeDDT as $riga) {
    $descrizione = $riga->Descrizione ?? '';
    $fornitore = trim($riga->RagioneSociale ?? '');
    $idDoc = $riga->IdDoc;
    $dataDoc = $riga->DataDocumento ? date('Y-m-d H:i:s', strtotime($riga->DataDocumento)) : now();

    // Estrai numero commessa — formati: "Commessa n° 66018", "Commessa n°66018", "Commessa 66018"
    // Il ° è UTF-8 (C2 B0), serve flag /u
    if (!preg_match('/Commessa\s*n?[°º.]?\s*(\d{5,7})/iu', $descrizione, $m)) {
        // Debug: mostra le prime 5 righe senza commessa
        static $noCommCount = 0;
        if ($dryRun && $noCommCount < 5) {
            echo "[NO COMM] DDT #{$idDoc} | Desc: " . substr($descrizione, 0, 80) . "\n";
            // Mostra hex dei primi 30 char per trovare caratteri speciali
            echo "  HEX: ";
            for ($h = 0; $h < min(50, strlen($descrizione)); $h++) echo sprintf('%02X ', ord($descrizione[$h]));
            echo "\n";
            $noCommCount++;
        }
        continue;
    }

    $numGrezzo = $m[1];
    $anno = $riga->DataDocumento ? date('y', strtotime($riga->DataDocumento)) : date('y');
    $numCommessa = str_pad($numGrezzo, 7, '0', STR_PAD_LEFT) . '-' . $anno;

    // Trova quali lavorazioni sono nella descrizione
    $lavorazioniTrovate = [];
    foreach ($mappingLavorazioni as $map) {
        if (preg_match($map['pattern'], $descrizione)) {
            $lavorazioniTrovate[] = $map;
        }
    }

    if ($dryRun) {
        $commessaEsiste = Ordine::where('commessa', $numCommessa)->exists();
        $nFasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $numCommessa))->count();
        $lavStr = empty($lavorazioniTrovate) ? 'NESSUNA' : implode(', ', array_map(fn($l) => $l['pattern'], $lavorazioniTrovate));
        echo "DDT #{$idDoc} | {$numCommessa} | Forn: {$fornitore} | MES: " . ($commessaEsiste ? "SI ({$nFasi} fasi)" : "NO") . " | Lav: {$lavStr}\n";
        echo "  Desc: " . substr($descrizione, 0, 100) . "\n";
    }

    if (empty($lavorazioniTrovate)) continue;

    // Per ogni lavorazione trovata, cerca la fase corrispondente nella commessa
    foreach ($lavorazioniTrovate as $lav) {
        // Cerca la prima fase matchante non ancora esterna
        $fase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $numCommessa))
            ->whereIn('fase', $lav['fasi'])
            ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
            ->whereNull('ddt_fornitore_id')
            ->orderBy('id')
            ->first();

        // Se non trovata con nome esatto, cerca con LIKE
        if (!$fase) {
            foreach ($lav['fasi'] as $nomeFase) {
                $fase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $numCommessa))
                    ->where('fase', 'LIKE', $nomeFase . '%')
                    ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
                    ->whereNull('ddt_fornitore_id')
                    ->orderBy('id')
                    ->first();
                if ($fase) break;
            }
        }

        // Cerca anche fasi EXT* già nel reparto esterno ma non ancora avviate
        if (!$fase) {
            foreach ($lav['fasi'] as $nomeFase) {
                $fase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $numCommessa))
                    ->where(fn($q) => $q->where('fase', 'LIKE', 'EXT' . $nomeFase . '%')->orWhere('fase', $nomeFase))
                    ->whereIn('stato', [0, 1])
                    ->whereNull('ddt_fornitore_id')
                    ->orderBy('id')
                    ->first();
            }
        }

        if (!$fase) {
            $nonTrovate++;
            if ($dryRun) {
                echo "  [MISS] $numCommessa | Pattern: {$lav['pattern']} | Fasi cercate: " . implode(', ', $lav['fasi']) . "\n";
            }
            continue;
        }

        // Controlla se già esterna
        if ($fase->esterno && $fase->ddt_fornitore_id) {
            $giaEsterne++;
            continue;
        }

        echo "  [MATCH] $numCommessa | Fase: {$fase->fase} (ID:{$fase->id}) | Fornitore: $fornitore\n";
        echo "         DDT: $idDoc | Desc: " . substr($descrizione, 0, 80) . "\n";

        if (!$dryRun) {
            // Cambia reparto a esterno
            $faseCatEsterno = FasiCatalogo::firstOrCreate(
                ['nome' => 'EXT' . $fase->fase, 'reparto_id' => $repartoEsterno->id],
                ['nome_display' => $fase->fase . ' (Esterno)']
            );

            $fase->update([
                'esterno' => 1,
                'stato' => 2,
                'data_inizio' => $dataDoc,
                'note' => 'Inviato a: ' . $fornitore,
                'ddt_fornitore_id' => $idDoc,
                'fase_catalogo_id_originale' => $fase->fase_catalogo_id_originale ?: $fase->fase_catalogo_id,
                'fase_catalogo_id' => $faseCatEsterno->id,
            ]);
        }

        $aggiornate++;
    }
}

echo "\n=== RIEPILOGO ===\n";
echo "Fasi marcate esterne: $aggiornate\n";
echo "Già esterne (skip): $giaEsterne\n";
echo "Fase non trovata nel MES: $nonTrovate\n";
if ($dryRun) echo "\n*** Nessuna modifica applicata (dry-run) ***\n";
