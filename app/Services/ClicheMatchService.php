<?php

namespace App\Services;

use App\Models\ClicheAnagrafica;
use App\Models\Ordine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Match ordini → cliché (logica v7 del simulatore).
 *
 * - Tokenize con synonym (AST=ASTUCCIO=ASTUCCI) + stoplist contestuale
 * - Match exact via hash token set
 * - Match subset: tutti token cliché ⊆ token MES; cliché con più token vince
 */
class ClicheMatchService
{
    public const SYNONYM = [
        'ASTUCCIO' => 'AST', 'ASTUCCI' => 'AST',
        'VASS' => 'VASSOIO', 'VASSOI' => 'VASSOIO',
        'SCAT' => 'SCATOLA', 'COP' => 'COPERCHIO', 'FOND' => 'FONDO',
    ];

    public const STOPLIST = ['DI','LA','IL','E','DEL','DELLA','CON','AL','ALLA','DA'];

    public const CONTEXTUAL_STOPLIST = [
        'NUANCE' => ['ENZO', 'MICCIO'],
    ];

    public static function stripAccenti(string $s): string
    {
        return strtr($s, [
            'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
            'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
            'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
        ]);
    }

    public static function normCore(string $s): string
    {
        $s = mb_strtoupper($s, 'UTF-8');
        $s = self::stripAccenti($s);
        $s = str_replace(["'", "'", '"', '"', '`', '´'], ' ', $s);
        $s = preg_replace('/[^A-Z0-9 ]+/u', ' ', $s);
        $s = preg_replace('/(\d+)\s+KG\b/u', '$1KG', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    public static function stripRumore(string $s): string
    {
        $s = mb_strtoupper($s, 'UTF-8');
        $s = self::stripAccenti($s);
        $s = preg_replace('/\([^)]*\)/u', ' ', $s);
        $s = preg_replace('/\bSTAMPA\b.*$/u', ' ', $s);
        $s = preg_replace('/\bFS\d+\b/u', ' ', $s);
        $s = preg_replace('/\bF\s*\.?\s*TO\s*[\d.,]+\s*X\s*[\d.,]+(\s*CM|\s*MM)?/u', ' ', $s);
        $s = preg_replace('/\bDA\s+\d+\s+PAG\w*/u', ' ', $s);
        $s = preg_replace('/\bDRIP\s*OFF\b/u', ' ', $s);
        $s = preg_replace('/\bLASTRINA\b.*$/u', ' ', $s);
        $s = preg_replace('/\b\d+\s*[\/X+]?\s*\d*\s*COLORI\b/u', ' ', $s);
        $s = preg_replace('/\bPANTONE?\s+\w+(\s+\d+)?/u', ' ', $s);
        return $s;
    }

    public static function tokenize(string $norm): array
    {
        $toks = preg_split('/\s+/u', trim($norm));
        $pass1 = [];
        foreach ($toks as $t) {
            if ($t === '' || in_array($t, self::STOPLIST, true)) continue;
            $pass1[] = self::SYNONYM[$t] ?? $t;
        }
        $set = array_flip($pass1);
        $out = [];
        foreach ($pass1 as $t) {
            if (isset(self::CONTEXTUAL_STOPLIST[$t])) {
                $allPresent = true;
                foreach (self::CONTEXTUAL_STOPLIST[$t] as $r) {
                    if (!isset($set[$r])) { $allPresent = false; break; }
                }
                if ($allPresent) continue;
            }
            $out[] = $t;
        }
        return $out;
    }

    public static function expandExcelRow(string $art): array
    {
        if (strpos($art, ',') === false) return [$art];
        $parts = array_map('trim', explode(',', $art));
        $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
        if (count($parts) < 2) return [$art];
        $first = $parts[0];
        $firstWords = preg_split('/\s+/u', $first);
        $second = $parts[1];
        $secondWords = preg_split('/\s+/u', $second);
        if (count($secondWords) < 1) return [$art];
        $idx = array_search($secondWords[0], $firstWords, true);
        if ($idx === false || $idx === 0) return [$art];
        $prefix = implode(' ', array_slice($firstWords, 0, $idx));
        if (mb_strlen($prefix) < 4) return [$art];
        $out = [$first];
        for ($i = 1; $i < count($parts); $i++) {
            $p = trim($parts[$i]);
            if ($p !== '') $out[] = $prefix . ' ' . $p;
        }
        return $out;
    }

    /**
     * Costruisce index in memoria di tutti i cliché (varianti espanse).
     * Ritorna ['byHash'=>[...], 'list'=>[...]]
     */
    public static function buildClicheIndex(): array
    {
        $all = ClicheAnagrafica::all();
        $list = [];
        $byHash = [];
        foreach ($all as $cl) {
            foreach (self::expandExcelRow($cl->descrizione_raw) as $v) {
                $norm = self::normCore($v);
                if ($norm === '') continue;
                $tokens = self::tokenize($norm);
                if (empty($tokens)) continue;
                $sorted = $tokens;
                sort($sorted);
                $hash = implode(' ', $sorted);
                $entry = [
                    'numero' => $cl->numero,
                    'tokens' => $tokens,
                    'ntok'   => count($tokens),
                    'hash'   => $hash,
                ];
                $list[] = $entry;
                if (!isset($byHash[$hash])) $byHash[$hash] = $entry;
            }
        }
        return ['byHash' => $byHash, 'list' => $list];
    }

    /**
     * Trova cliché numero per una descrizione MES. Null se no match.
     */
    public static function match(string $descrizioneMes, array $index): ?int
    {
        $stripped = self::stripRumore($descrizioneMes);
        $norm = self::normCore($stripped);
        $tokens = self::tokenize($norm);
        if (empty($tokens)) return null;
        $sorted = $tokens; sort($sorted);
        $hash = implode(' ', $sorted);

        if (isset($index['byHash'][$hash])) {
            return (int) $index['byHash'][$hash]['numero'];
        }

        $set = array_flip($tokens);
        $best = null; $bestScore = 0;
        foreach ($index['list'] as $c) {
            if ($c['ntok'] < 2) continue;
            $ok = true;
            foreach ($c['tokens'] as $t) {
                if (!isset($set[$t])) { $ok = false; break; }
            }
            if ($ok && $c['ntok'] > $bestScore) {
                $best = $c; $bestScore = $c['ntok'];
            }
        }
        return $best ? (int) $best['numero'] : null;
    }

    /**
     * Matcha tutti gli ordini con almeno 1 fase stato 0-1, non ancora override manuale.
     * Ritorna ['matched' => N, 'updated' => N].
     */
    public static function matchAll(): array
    {
        $index = self::buildClicheIndex();
        if (empty($index['list'])) {
            Log::warning('ClicheMatch: anagrafica vuota, skip');
            return ['matched' => 0, 'updated' => 0];
        }

        // Ordini con almeno 1 fase stato 0-1 attiva
        $ordiniIds = DB::table('ordine_fasi')
            ->whereIn('stato', [0, 1])
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('ordine_id');

        $ordini = Ordine::whereIn('id', $ordiniIds)
            ->where(fn($q) => $q->whereNull('cliche_match_type')
                                 ->orWhere('cliche_match_type', 'auto'))
            ->get();

        $matched = 0; $updated = 0;
        foreach ($ordini as $o) {
            $num = self::match((string)($o->descrizione ?? ''), $index);
            if ($num === null) continue;
            $matched++;
            if ((int)$o->cliche_numero !== $num) {
                $o->cliche_numero = $num;
                $o->cliche_match_type = 'auto';
                $o->cliche_matched_at = now();
                $o->save();
                $updated++;
            }
        }
        Log::info("ClicheMatch: matched=$matched updated=$updated su " . count($ordini) . " ordini");
        return ['matched' => $matched, 'updated' => $updated];
    }
}
