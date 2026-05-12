<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Rules;

use App\Models\OrdineFase;

/**
 * Regola sull'ordinamento delle fasi nel ciclo produttivo.
 *
 * Sorgente: config/fasi_priorita.php (sequenza canonica).
 * Una fase è "iniziabile" solo se tutte le fasi della stessa
 * commessa con sequenza inferiore sono in stato terminato (>= 3)
 * o consegnato (=4).
 */
final class SequenzaFasiRule
{
    /** @var array<string,int>|null cache locale */
    private static ?array $tabella = null;

    public static function ordineFase(string $nomeFase): int
    {
        $tab = self::tabella();
        return $tab[$nomeFase] ?? PHP_INT_MAX;
    }

    /**
     * Nome della prima fase con sequenza > di $nomeFase nella tabella
     * (utile per propagare lo sblocco; il "successivo logico" reale
     * è dato dalle fasi della singola commessa).
     */
    public static function faseSuccessiva(string $nomeFase): ?string
    {
        $seqCorr = self::ordineFase($nomeFase);
        if ($seqCorr === PHP_INT_MAX) {
            return null;
        }

        $tab = self::tabella();
        asort($tab);

        foreach ($tab as $nome => $seq) {
            if ($seq > $seqCorr) {
                return $nome;
            }
        }

        return null;
    }

    /**
     * True se non esistono fasi precedenti della stessa commessa
     * non ancora terminate.
     */
    public static function puoIniziare(OrdineFase $fase): bool
    {
        $ordine = $fase->ordine ?? null;
        if ($ordine === null) {
            return true;
        }
        $commessa = $ordine->commessa ?? null;
        if (! $commessa) {
            return true;
        }

        $seq = self::ordineFase((string) $fase->fase);
        if ($seq === PHP_INT_MAX) {
            return true;
        }

        // Una commessa puo' avere piu' ordini (multi-articolo): controllo
        // tutte le fasi della commessa, non solo quelle dello stesso ordine_id.
        $tutteFasiCommessa = OrdineFase::with('ordine')
            ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
            ->whereNull('deleted_at')
            ->get();

        foreach ($tutteFasiCommessa as $altra) {
            if ($altra->id === $fase->id) {
                continue;
            }
            $altraSeq = self::ordineFase((string) $altra->fase);
            if ($altraSeq < $seq && (int) $altra->stato < 3) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string,int>
     */
    private static function tabella(): array
    {
        if (self::$tabella !== null) {
            return self::$tabella;
        }

        $path = function_exists('config_path')
            ? config_path('fasi_priorita.php')
            : __DIR__ . '/../../../../config/fasi_priorita.php';

        /** @var array<string,int> $tab */
        $tab = is_file($path) ? require $path : [];

        return self::$tabella = $tab;
    }

    /** Per i test: reset cache. */
    public static function flushCache(): void
    {
        self::$tabella = null;
    }
}
