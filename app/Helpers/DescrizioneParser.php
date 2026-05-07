<?php

namespace App\Helpers;

/**
 * DescrizioneParser
 * -----------------
 * Estrae informazioni strutturate (colori stampa, codici fustella) dalle
 * descrizioni libere degli ordini provenienti dal gestionale Onda.
 *
 * I metodi pubblici sono SAFE rispetto a input null/empty: in quel caso
 * restituiscono il valore di default documentato e non sollevano eccezioni.
 *
 * @example
 *   DescrizioneParser::parseColori('Stampa a 4 colori su carta', 'CLIENTE X')   // "4C"
 *   DescrizioneParser::parseColori('stampa 5 colori + pantone 185', 'X')        // "5C + PANTONE 185"
 *   DescrizioneParser::parseFustella('Astuccio FS0902 ...')                     // "FS0902"
 */
class DescrizioneParser
{
    /** Colore di default quando non si riesce a dedurre nulla. */
    public const DEFAULT_COLORE = '4C';

    /** Override colore per il cliente Italiana Confetti senza info esplicita. */
    public const ITALIANA_CONFETTI_DEFAULT_COLORE = '4C + DRIP OFF';

    /** Reparto digitale: stampa sempre 4C indipendentemente dalla descrizione. */
    public const REPARTO_DIGITALE_KEYWORD = 'digitale';

    /** Cliente con regole speciali per fustella/colori. */
    public const CLIENTE_ITALIANA_CONFETTI = 'ITALIANA CONFETTI';
    public const CLIENTE_ITALIANO_CONFETTI = 'ITALIANO CONFETTI';

    /**
     * Pattern principale per riconoscere "stampa a N colori", "- N colori", "N COLORI".
     * Cattura: $1 = numero (anche 4/4 o 4+4), $2 = testo successivo (max 120 char).
     */
    private const PATTERN_NUM_COLORI =
        '/(?:stampa\s+(?:a\s+)?|[-,]\s*|\b)(\d+(?:[\/+]\d+)?)\s*colou?r[ei]?\b(.{0,120})/is';

    /** Codici fustella standard: FS#### / KS#### con 3-5 cifre. */
    private const PATTERN_FUSTELLA_STANDARD = '/\b((?:FS|KS)\d{3,5})(?:[_\-\s]|$|\b)/';

    /** Etichette in-mould: "ETI N 123". */
    private const PATTERN_ETI_N = '/\b(ETI\s*N\s*\d+)\b/i';

    /** "Fustella KS singola" → "KS SING". */
    private const PATTERN_FUSTELLA_KS_SINGOLA = '/\bfustella\s+(KS\s*sing(?:ola)?)\b/i';

    /**
     * Estrae la specifica colori dalla descrizione dell'ordine.
     *
     * Regole (in ordine di priorità):
     *  1. Reparto "digitale" → sempre {@see self::DEFAULT_COLORE}
     *  2. Pattern "N colori [+ extra]" presente nella descrizione → numero formattato + dettagli
     *  3. Cliente Italiana Confetti senza info → {@see self::ITALIANA_CONFETTI_DEFAULT_COLORE}
     *  4. Default → {@see self::DEFAULT_COLORE}
     *
     * @param string|null $descrizione  Testo libero ordine (può essere null/empty).
     * @param string|null $clienteNome  Ragione sociale cliente (per regole speciali).
     * @param string|null $repartoNome  Nome reparto (override "digitale").
     * @return string Stringa colore es. "4C", "5C + PANTONE 185", "2/1C".
     *
     * @example parseColori('stampa a 4 colori', 'X')                 // "4C"
     * @example parseColori('5 colori + pantone 185 su carta', 'X')   // "5C + pantone 185"
     * @example parseColori(null, 'ITALIANA CONFETTI SRL')            // "4C + DRIP OFF"
     * @example parseColori('foo', 'X', 'digitale')                   // "4C"
     */
    public static function parseColori(?string $descrizione, ?string $clienteNome, ?string $repartoNome = ''): string
    {
        $descrizione = $descrizione ?? '';
        $clienteNome = $clienteNome ?? '';
        $repartoNome = $repartoNome ?? '';

        // 1. Digitale: sempre 4C
        if ($repartoNome !== '' && stripos($repartoNome, self::REPARTO_DIGITALE_KEYWORD) !== false) {
            return self::DEFAULT_COLORE;
        }

        // 2. Pattern "N colori"
        if ($descrizione !== '' && preg_match(self::PATTERN_NUM_COLORI, $descrizione, $m)) {
            $numColori = trim($m[1]);
            $resto = trim($m[2]);

            $dettagli = self::estraiDettagliColore($resto);
            $numDisplay = self::formattaNumColori($numColori);

            return $dettagli !== '' ? $numDisplay . ' ' . $dettagli : $numDisplay;
        }

        // 3. Override Italiana Confetti
        if (stripos($clienteNome, self::CLIENTE_ITALIANA_CONFETTI) !== false) {
            return self::ITALIANA_CONFETTI_DEFAULT_COLORE;
        }

        // 4. Default
        return self::DEFAULT_COLORE;
    }

    /**
     * Estrae i dettagli colore (es. "+ PANTONE 185") dal testo che segue "colori".
     * Si ferma ai delimitatori noti (su carta, F.TO, FINESTRATURA, ecc.).
     *
     * @param string|null $resto Testo subito dopo la parola "colori/colore".
     * @return string Dettagli puliti, oppure stringa vuota.
     */
    protected static function estraiDettagliColore(?string $resto): string
    {
        if ($resto === null || $resto === '') {
            return '';
        }

        // Delimitatori che troncano il testo dei dettagli colore.
        $delimitatori = [
            '/\bsu\s+carta\b/i',
            '/\bsu\s+[A-Z][a-zA-Z]+\s+\d/i',          // "su ZENITH 350", "su Arena Extra"
            '/\bCARTA\s+(?:FSC|CLIENTE|GC|MICRO|PATINATA|SBS)/i',
            '/\bF\.TO\b/i',
            '/\bFINESTRATURA\b/i',
            '/\bFUSTELLATURA\b/i',
            '/\bSENZA\b/i',
            '/\(RISERVA\b/i',
            '/\bcon\s+riserve\b/i',
            '/\(\s*(?:USARE|CON\s+LASTRINA)\b/i',     // istruzioni tecniche tra parentesi
            '/\bBrossura\b/i',
            '/\bPunto\s+Metallico\b/i',
            '/\bspedizione\b/i',
            '/\bPLAST\.\s/i',
            '/\bPlastificazione\b/i',
            '/\bFascettatura\b/i',
            '/\b\d{2,}[\.,]\d{3}\b/',                 // quantità formattate: "50.500", "30.000"
            '/\bASTUCCI\b/i',
            '/\bTOTALI\b/i',
            '/\bStampa,/i',
            '/\bStella\s+\d/i',                        // "Stella 273-959"
        ];

        foreach ($delimitatori as $d) {
            $parts = preg_split($d, $resto, 2);
            if (is_array($parts) && count($parts) > 1) {
                $resto = $parts[0];
            }
        }

        $resto = trim($resto, " ,.\t\n\r");

        // Rimuove "+" finale isolato.
        $resto = preg_replace('/\+\s*$/', '', $resto) ?? $resto;
        $resto = trim($resto);

        // Bilancia parentesi non chiuse (es. "(USARE LASTRINA").
        $aperte = substr_count($resto, '(');
        $chiuse = substr_count($resto, ')');
        if ($aperte > $chiuse) {
            $resto .= str_repeat(')', $aperte - $chiuse);
        }

        return $resto;
    }

    /**
     * Formatta il numero di colori in notazione standard del reparto stampa.
     *
     * @param string $num Es. "4", "4/4", "4+4", "5/5", "2/1".
     * @return string     Es. "4C", "4C", "4C", "5C", "2/1C".
     *
     * @example formattaNumColori('4')   // "4C"
     * @example formattaNumColori('4/4') // "4C"   (fronte/retro identico, mostrato compatto)
     * @example formattaNumColori('5/5') // "5C"
     * @example formattaNumColori('2/1') // "2/1C"
     */
    protected static function formattaNumColori(string $num): string
    {
        // Normalizza "+": 4+4 → 4/4
        $num = str_replace('+', '/', $num);

        // 4 e 4/4 → standard CMYK compatto
        if (in_array($num, ['4', '4/4'], true)) {
            return self::DEFAULT_COLORE;
        }

        // Fronte/retro identici (5/5, 6/6) → mostra solo un lato
        if (preg_match('/^(\d+)\/\1$/', $num, $nm)) {
            return $nm[1] . 'C';
        }

        return $num . 'C';
    }

    /**
     * Estrae i codici fustella dalla descrizione e/o dalle note di prestampa.
     *
     * Pattern riconosciuti:
     *  - FS#### / KS#### standard (3-5 cifre, opzionale suffisso "_273-1097")
     *  - "ETI N ###" (etichette in-mould)
     *  - "Fustella KS singola" → "KS SING"
     *  - Fallback Italiana Confetti: "AST 1 KG"→FS0898, "AST 500"→FS0044
     *
     * @param string|null $descrizione   Descrizione ordine (può essere null/empty).
     * @param string|null $clienteNome   Cliente (per fallback Italiana Confetti).
     * @param string|null $notePrestampa Note prestampa (es. "NUOVA FUSTELLA FS1610").
     * @return string|null Codice/i fustella separati da " / " oppure null se non trovato.
     *
     * @example parseFustella('Astuccio FS0902 ...')                  // "FS0902"
     * @example parseFustella('art. KS1234 / FS5678')                 // "KS1234 / FS5678"
     * @example parseFustella('Etichetta ETI N 12 ...')               // "ETI N 12"
     * @example parseFustella('AST. 1 KG cioccolatini', 'ITALIANA CONFETTI') // "FS0898"
     * @example parseFustella(null)                                   // null
     */
    public static function parseFustella(?string $descrizione, ?string $clienteNome = '', ?string $notePrestampa = ''): ?string
    {
        $descrizione = $descrizione ?? '';
        $clienteNome = $clienteNome ?? '';
        $notePrestampa = $notePrestampa ?? '';

        $codes = [];

        $testi = array_filter([$descrizione, $notePrestampa], static fn ($t) => $t !== '');

        foreach ($testi as $testo) {
            // FS#### / KS#### standard
            if (preg_match_all(self::PATTERN_FUSTELLA_STANDARD, $testo, $m)) {
                $codes = array_merge($codes, $m[1]);
            }

            // ETI N ###
            if (preg_match_all(self::PATTERN_ETI_N, $testo, $m)) {
                foreach ($m[1] as $etiCode) {
                    $codes[] = preg_replace('/\s+/', ' ', strtoupper(trim($etiCode)));
                }
            }

            // Fustella KS singola
            if (preg_match(self::PATTERN_FUSTELLA_KS_SINGOLA, $testo, $m)) {
                $codes[] = strtoupper(preg_replace('/\s+/', ' ', trim($m[1])));
            }

            // Nota: "fustella bobst" senza codice è ignorata (lasciato per documentazione storica)
        }

        if (!empty($codes)) {
            return implode(' / ', array_unique($codes));
        }

        // Fallback Italiana Confetti
        if (
            stripos($clienteNome, self::CLIENTE_ITALIANA_CONFETTI) !== false
            || stripos($clienteNome, self::CLIENTE_ITALIANO_CONFETTI) !== false
        ) {
            if (preg_match('/^AST\.?\s*1\s*KG/i', $descrizione)) {
                return 'FS0898';
            }
            if (preg_match('/^AST\.?\s*500/i', $descrizione)) {
                return 'FS0044';
            }
        }

        return null;
    }
}
