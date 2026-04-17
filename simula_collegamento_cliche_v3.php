<?php
/**
 * Simulazione v3: strip rumore automatico prima del match.
 *
 * Strip rumore MES:
 *  - rimuove parentesi e contenuto
 *  - tronca da "STAMPA ", "F.TO ", " FS<cifre>", " DA <cifre> PAG"
 *  - normalizza "1 KG" ↔ "1KG" (regex)
 *  - rimuove punteggiatura, accenti, apostrofi
 *
 * Poi match esatto + fallback word-boundary.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:\\condivisa\\mes\\Numerazione_Clice_2000_2300 1 (1) (1).xlsx';
if (!file_exists($path)) die("File non trovato: $path\n");

error_reporting(E_ALL & ~E_DEPRECATED);

function stripAccenti(string $s): string {
    return strtr($s, [
        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
        'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
        'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
        'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
    ]);
}

/**
 * Normalizzazione "core": uppercase, rimuove punteggiatura, unifica KG, niente rumore.
 * Usata sia su Excel (pulito) sia su MES dopo stripping.
 */
function normCore(string $s): string {
    $s = mb_strtoupper($s, 'UTF-8');
    $s = stripAccenti($s);
    // apostrofi/virgolette → spazio
    $s = str_replace(["'", "'", '"', '"', '`', '´'], ' ', $s);
    // tutto ciò che non è A-Z0-9 spazio → spazio
    $s = preg_replace('/[^A-Z0-9 ]+/u', ' ', $s);
    // "1 KG" / "10 KG" → "1KG" / "10KG" (unifica forme Excel e MES)
    $s = preg_replace('/(\d+)\s+KG\b/u', '$1KG', $s);
    // collassa spazi
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

/**
 * Rimuove "rumore" tipico dalle descrizioni MES prima di normalizzare.
 */
function stripRumoreMes(string $s): string {
    $s = mb_strtoupper($s, 'UTF-8');
    $s = stripAccenti($s);
    // rimuove parentesi e contenuto (es. "(LASTRINA RISERVATA)")
    $s = preg_replace('/\([^)]*\)/u', ' ', $s);
    // tronca a prima occorrenza di pattern "rumore inizia qui"
    // "STAMPA "/"STAMPA\s+\d"/"F.TO "/"F TO "/"FTO "/" FS<cifre>"/" DA <cifre> PAG"
    $trunchers = [
        '/\bSTAMPA\b/u',
        '/\bF\s*\.?\s*TO\s/u',           // F.TO / F TO / FTO
        '/\bFS\d+/u',                     // FS0898
        '/\bDA\s+\d+\s+PAG/u',            // DA 16 PAG
        '/\bFTO\b/u',
        '/\bSTAMPA\s+A\s+CALDO/u',
        '/\bDRIP\s*OFF/u',
    ];
    $minPos = mb_strlen($s);
    foreach ($trunchers as $pat) {
        if (preg_match($pat, $s, $m, PREG_OFFSET_CAPTURE)) {
            if ($m[0][1] < $minPos) $minPos = $m[0][1];
        }
    }
    if ($minPos < mb_strlen($s)) $s = mb_substr($s, 0, $minPos);
    return $s;
}

// ==================== STEP 1: leggi Excel ====================
echo "=== STEP 1: lettura Excel ===\n";
$ss = IOFactory::load($path);
$rows = $ss->getActiveSheet()->toArray(null, false, false, true);

$cliche = [];
$clicheByNorm = [];
$first = true;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $n = trim((string)($r['A'] ?? ''));
    $art = trim((string)($r['B'] ?? ''));
    if ($n === '' || $art === '') continue;
    $norm = normCore($art);
    $entry = ['cliche'=>$n, 'raw'=>$art, 'norm'=>$norm, 'note'=>$r['D'] ?? null];
    $cliche[] = $entry;
    $clicheByNorm[$norm][] = $entry;
}
// Ordina per lunghezza norm DESC (più specifico vince nel contains fallback)
usort($cliche, fn($a,$b) => mb_strlen($b['norm']) - mb_strlen($a['norm']));
echo "  Righe Excel: " . count($cliche) . "\n";
echo "  Desc uniche: " . count($clicheByNorm) . "\n";

// ==================== STEP 2: fasi MES ====================
echo "\n=== STEP 2: fasi MES stato 0-1 ===\n";
$fasi = DB::table('ordine_fasi as of')
    ->join('ordini as o', 'o.id', '=', 'of.ordine_id')
    ->whereIn('of.stato', [0, 1])
    ->whereNull('of.deleted_at')
    ->select('of.id as fase_id', 'of.fase', 'of.stato',
             'o.id as ordine_id', 'o.commessa', 'o.descrizione')
    ->get();
echo "  Fasi: " . count($fasi) . "\n";

// ==================== STEP 3: match ====================
$matchedExact = 0;
$matchedContains = 0;
$unmatched = [];
$usati = [];
$matched = [];

foreach ($fasi as $f) {
    $raw = (string)($f->descrizione ?? '');
    $stripped = stripRumoreMes($raw);
    $mesNorm = normCore($stripped);

    if ($mesNorm === '') { $unmatched[] = [$f, $raw, $mesNorm, 'vuoto']; continue; }

    // 1) Match esatto
    $found = null; $matchType = null;
    if (isset($clicheByNorm[$mesNorm])) {
        $found = $clicheByNorm[$mesNorm][0];
        $matchType = 'exact';
        $matchedExact++;
    } else {
        // 2) Fallback: cliché contenuto come parola intera, prefer-longest
        foreach ($cliche as $c) {
            if ($c['norm'] === '') continue;
            $pat = '/(?:^|\s)' . preg_quote($c['norm'], '/') . '(?:\s|$)/u';
            if (preg_match($pat, $mesNorm)) {
                $found = $c; $matchType = 'contains';
                $matchedContains++;
                break;
            }
        }
    }

    if ($found) {
        $matched[] = ['f'=>$f, 'c'=>$found, 'type'=>$matchType, 'mesNorm'=>$mesNorm];
        $usati[$found['cliche']] = ($usati[$found['cliche']] ?? 0) + 1;
    } else {
        $unmatched[] = [$f, $raw, $mesNorm, 'no-match'];
    }
}

echo "\n=== STEP 3: RISULTATI ===\n";
echo "  MATCH esatti post-strip: $matchedExact\n";
echo "  MATCH contains (fallback): $matchedContains\n";
echo "  TOTALE MATCH:              " . count($matched) . "\n";
echo "  Fasi senza match:          " . count($unmatched) . "\n";
echo "  Cliché usati:              " . count($usati) . " / " . count($cliche) . "\n";

echo "\n=== STEP 4: MATCH (primi 40) ===\n";
foreach (array_slice($matched, 0, 40) as $m) {
    printf("  [CL %4s][%-8s] '%s'  ← %s | '%s'\n",
        $m['c']['cliche'], $m['type'],
        mb_substr($m['c']['raw'], 0, 35),
        $m['f']->commessa,
        mb_substr($m['mesNorm'], 0, 45)
    );
}
if (count($matched) > 40) echo "  ... (+" . (count($matched)-40) . ")\n";

// Statistiche per commessa
$commesseMatch = [];
foreach ($matched as $m) $commesseMatch[$m['f']->commessa] = true;
$commesseTot = [];
foreach ($fasi as $f) $commesseTot[$f->commessa] = true;
echo "\n  Commesse con >=1 match: " . count($commesseMatch) . " / " . count($commesseTot) . "\n";

// ==================== STEP 5: senza match ====================
$descUniche = [];
foreach ($unmatched as $u) {
    [$f, $raw, $norm, $why] = $u;
    if ($norm !== '') $descUniche[$norm] = ($descUniche[$norm] ?? 0) + 1;
}
arsort($descUniche);
echo "\n=== STEP 5: top 25 descrizioni post-strip senza match ===\n";
$i = 0;
foreach ($descUniche as $d => $cnt) {
    if ($i++ >= 25) break;
    printf("  %3dx  %s\n", $cnt, mb_substr($d, 0, 75));
}

echo "\n=== STEP 6: esempi strip (prime 20 fasi raw → norm) ===\n";
$i = 0;
foreach ($fasi as $f) {
    if ($i++ >= 20) break;
    $raw = (string)($f->descrizione ?? '');
    $stripped = stripRumoreMes($raw);
    $norm = normCore($stripped);
    printf("  RAW: %s\n  → NORM: %s\n\n",
        mb_substr($raw, 0, 80),
        mb_substr($norm, 0, 80)
    );
}

echo "Done. Nessuna modifica al DB.\n";
