<?php
/**
 * Simulazione collegamento Excel cliché → MES (fasi stato 0-1).
 *
 * NON scrive nulla nel DB. Mostra:
 *  - righe Excel senza match
 *  - fasi MES senza match (stato 0-1)
 *  - match esatti trovati
 *
 * Uso:
 *   php simula_collegamento_cliche.php "C:\path\al\file.xlsx"
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:\\Users\\Giovanni\\Downloads\\Numerazione_Clice_2000_2300 1 (1) (1).xlsx';
if (!file_exists($path)) {
    die("File non trovato: $path\n");
}

error_reporting(E_ALL & ~E_DEPRECATED);

/**
 * Normalizza una descrizione per confronto:
 *  - uppercase
 *  - trim + collassa spazi multipli
 *  - rimuove apostrofi "'" e "'" e accenti comuni
 *  - NON rimuove parole (es. "LES" resta) → match stretto
 */
function normDesc(?string $s): string {
    $s = (string) $s;
    $s = mb_strtoupper($s, 'UTF-8');
    // normalizza apostrofi/virgolette tipografiche
    $s = str_replace(["'", "'", '"', '"', '`', '´'], "'", $s);
    // rimuove accenti (mappa base italiana)
    $s = strtr($s, [
        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
        'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
        'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
        'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
    ]);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

// ==================== STEP 1: leggi Excel ====================
echo "=== STEP 1: lettura Excel ===\n";
$ss = IOFactory::load($path);
$rows = $ss->getActiveSheet()->toArray(null, false, false, true);

$cliche = []; // [descNormalizzata => ['cliche'=>N, 'raw'=>descOriginale, 'qta'=>, 'note'=>]]
$duplicati = [];
$first = true;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $n = trim((string)($r['A'] ?? ''));
    $art = trim((string)($r['B'] ?? ''));
    if ($n === '' || $art === '') continue;
    $key = normDesc($art);
    if (isset($cliche[$key])) {
        $duplicati[] = "Cliché {$n} '{$art}' dup di {$cliche[$key]['cliche']} '{$cliche[$key]['raw']}'";
        continue;
    }
    $cliche[$key] = [
        'cliche' => $n,
        'raw'    => $art,
        'qta'    => $r['C'] ?? null,
        'note'   => $r['D'] ?? null,
        'scatola'=> $r['E'] ?? null,
    ];
}
echo "  Righe Excel caricate: " . count($cliche) . "\n";
if ($duplicati) {
    echo "  ATTENZIONE: " . count($duplicati) . " descrizioni duplicate nell'Excel:\n";
    foreach (array_slice($duplicati, 0, 10) as $d) echo "    - $d\n";
    if (count($duplicati) > 10) echo "    ... (+" . (count($duplicati)-10) . ")\n";
}

// ==================== STEP 2: query fasi MES stato 0-1 ====================
echo "\n=== STEP 2: query fasi MES stato 0-1 ===\n";
$fasi = DB::table('ordine_fasi as of')
    ->join('ordini as o', 'o.id', '=', 'of.ordine_id')
    ->whereIn('of.stato', [0, 1])
    ->whereNull('of.deleted_at')
    ->select('of.id as fase_id', 'of.fase', 'of.stato', 'of.priorita',
             'o.id as ordine_id', 'o.commessa', 'o.descrizione', 'o.cliente_nome')
    ->get();
echo "  Fasi stato 0-1 trovate: " . count($fasi) . "\n";

// ==================== STEP 3: match ====================
echo "\n=== STEP 3: matching ===\n";
$matched = [];
$unmatchedFasi = [];
$usati = []; // cliché usati

foreach ($fasi as $f) {
    $key = normDesc($f->descrizione);
    if ($key !== '' && isset($cliche[$key])) {
        $c = $cliche[$key];
        $matched[] = compact('f','c');
        $usati[$c['cliche']] = true;
    } else {
        $unmatchedFasi[] = $f;
    }
}

$unmatchedExcel = array_filter($cliche, fn($c) => !isset($usati[$c['cliche']]));

echo "  MATCH trovati:       " . count($matched) . "\n";
echo "  Fasi senza match:    " . count($unmatchedFasi) . "\n";
echo "  Cliché senza match:  " . count($unmatchedExcel) . " (su " . count($cliche) . ")\n";

// ==================== STEP 4: dettaglio ====================
echo "\n=== STEP 4: MATCH trovati (primi 30) ===\n";
foreach (array_slice($matched, 0, 30) as $m) {
    $f = $m['f']; $c = $m['c'];
    printf("  [CL %s] %-40s  →  commessa %s fase=%s stato=%d (id=%d)\n",
        $c['cliche'],
        mb_substr($c['raw'], 0, 40),
        $f->commessa, $f->fase, $f->stato, $f->fase_id
    );
}
if (count($matched) > 30) echo "  ... (+" . (count($matched)-30) . ")\n";

echo "\n=== STEP 5: fasi MES senza match (prime 30) ===\n";
foreach (array_slice($unmatchedFasi, 0, 30) as $f) {
    printf("  commessa=%s fase=%s stato=%d  desc='%s'\n",
        $f->commessa, $f->fase, $f->stato,
        mb_substr($f->descrizione ?? '', 0, 60)
    );
}
if (count($unmatchedFasi) > 30) echo "  ... (+" . (count($unmatchedFasi)-30) . ")\n";

// Statistiche utili: descrizioni uniche MES senza match
$descUnicheSenzaMatch = [];
foreach ($unmatchedFasi as $f) {
    $k = normDesc($f->descrizione);
    if ($k !== '') $descUnicheSenzaMatch[$k] = ($descUnicheSenzaMatch[$k] ?? 0) + 1;
}
arsort($descUnicheSenzaMatch);
echo "\n=== STEP 6: descrizioni uniche MES senza match (top 20) ===\n";
$i = 0;
foreach ($descUnicheSenzaMatch as $desc => $count) {
    if ($i++ >= 20) break;
    printf("  %3dx  %s\n", $count, mb_substr($desc, 0, 70));
}

echo "\nDone. Nessuna modifica al DB.\n";
