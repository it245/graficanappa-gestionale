<?php

namespace App\Helpers;

class DescrizioneParser
{
    /**
     * Estrae le informazioni sui colori dalla descrizione dell'ordine.
     *
     * Regole:
     * - Digitale: sempre "4C"
     * - Se nella descrizione c'è "stampa a X colori ...": estrae la specifica
     * - Se non c'è info colore:
     *   - ITALIANA CONFETTI → "4C + DRIP OFF"
     *   - Tutti gli altri → "4C"
     */
    public static function parseColori(string $descrizione, string $clienteNome, string $repartoNome = ''): string
    {
        // Digitale: sempre 4C
        if (stripos($repartoNome, 'digitale') !== false) {
            return '4C';
        }

        // Cerca pattern: "stampa [a] NUM colori", "- NUM colori", ", NUM colori", o "NUM COLORI" standalone
        $pattern = '/(?:stampa\s+(?:a\s+)?|[-,]\s*|\b)(\d+(?:[\/+]\d+)?)\s*colou?r[ei]?\b(.{0,120})/is';

        if (preg_match($pattern, $descrizione, $m)) {
            $numColori = trim($m[1]);
            $resto = trim($m[2]);

            // Estrai dettagli utili dal resto, fermandosi ai delimitatori
            $dettagli = self::estraiDettagliColore($resto);

            // Formatta il numero
            $numDisplay = self::formattaNumColori($numColori);

            if ($dettagli) {
                return $numDisplay . ' ' . $dettagli;
            }
            return $numDisplay;
        }

        // Nessuna info colore trovata → default
        if (stripos($clienteNome, 'ITALIANA CONFETTI') !== false) {
            return '4C + DRIP OFF';
        }

        return '4C';
    }

    /**
     * Estrae i dettagli colore dal testo dopo "colori/colore".
     * Si ferma ai delimitatori noti (su carta, F.TO, FINESTRATURA, ecc.)
     */
    protected static function estraiDettagliColore(string $resto): string
    {
        if (empty($resto)) return '';

        // Taglia ai delimitatori
        $delimitatori = [
            '/\bsu\s+carta\b/i',
            '/\bsu\s+[A-Z][a-zA-Z]+\s+\d/i',      // "su ZENITH 350", "su Arena Extra"
            '/\bCARTA\s+(?:FSC|CLIENTE|GC|MICRO|PATINATA|SBS)/i',
            '/\bF\.TO\b/i',
            '/\bFINESTRATURA\b/i',
            '/\bFUSTELLATURA\b/i',
            '/\bSENZA\b/i',
            '/\(RISERVA\b/i',
            '/\bcon\s+riserve\b/i',
            '/\(\s*(?:USARE|CON\s+LASTRINA)\b/i',  // istruzioni tecniche tra parentesi
            '/\bBrossura\b/i',
            '/\bPunto\s+Metallico\b/i',
            '/\bspedizione\b/i',
            '/\bPLAST\.\s/i',
            '/\bPlastificazione\b/i',
            '/\bFascettatura\b/i',
            '/\b\d{2,}[\.,]\d{3}\b/',              // quantità formattate: "50.500", "30.000"
            '/\bASTUCCI\b/i',                       // tipo prodotto
            '/\bTOTALI\b/i',                        // totali
            '/\bStampa,/i',                          // "Stampa," come separatore di lavorazioni
            '/\bStella\s+\d/i',                      // "Stella 273-959"
        ];

        foreach ($delimitatori as $d) {
            $parts = preg_split($d, $resto, 2);
            if (count($parts) > 1) {
                $resto = $parts[0];
            }
        }

        $resto = trim($resto, " ,.\t\n\r");

        // Pulisci "+" finale isolato
        $resto = preg_replace('/\+\s*$/', '', $resto);
        $resto = trim($resto);

        // Pulisci parentesi non bilanciate
        $aperte = substr_count($resto, '(');
        $chiuse = substr_count($resto, ')');
        if ($aperte > $chiuse) {
            $resto .= str_repeat(')', $aperte - $chiuse);
        }

        return $resto;
    }

    /**
     * Formatta il numero di colori.
     * 4 → "4C", 4/4 → "4C", 5/5 → "5/5C", 2/1 → "2/1C"
     */
    protected static function formattaNumColori(string $num): string
    {
        // Normalizza: 4+4 → 4/4
        $num = str_replace('+', '/', $num);

        // Fronte/retro uguali a 4: standard CMYK
        if (in_array($num, ['4', '4/4'])) {
            return '4C';
        }

        // Fronte/retro uguali: mostra solo un lato
        if (preg_match('/^(\d+)\/\1$/', $num, $nm)) {
            return $nm[1] . 'C';
        }

        return $num . 'C';
    }

    /**
     * Estrae tutti i codici fustella dalla descrizione e/o note prestampa.
     * Cerca: FS#### (standard), ETI N ### (etichette in-mould).
     * Controlla sia descrizione che note_prestampa (es. "NUOVA FUSTELLA FS1610").
     */
    public static function parseFustella(string $descrizione, string $clienteNome = '', string $notePrestampa = ''): ?string
    {
        $codes = [];

        // Cerca in entrambi i campi
        $testi = array_filter([$descrizione, $notePrestampa]);

        foreach ($testi as $testo) {
            // Pattern FS#### (codici fustella standard, anche con suffisso _273-1097)
            if (preg_match_all('/\b(FS\d{3,5})(?:[_\-\s]|$|\b)/', $testo, $m)) {
                $codes = array_merge($codes, $m[1]);
            }

            // Pattern ETI N ### (etichette in-mould = tipo fustella)
            if (preg_match_all('/\b(ETI\s*N\s*\d+)\b/i', $testo, $m)) {
                foreach ($m[1] as $etiCode) {
                    $codes[] = preg_replace('/\s+/', ' ', strtoupper(trim($etiCode)));
                }
            }
        }

        if (!empty($codes)) {
            return implode(' / ', array_unique($codes));
        }

        // Fallback Italiana Confetti: FS dalla descrizione articolo
        if (stripos($clienteNome, 'ITALIANA CONFETTI') !== false || stripos($clienteNome, 'ITALIANO CONFETTI') !== false) {
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
