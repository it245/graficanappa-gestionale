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

        // Cerca pattern: "stampa [a] NUM[/NUM] colori/colore [DETTAGLI]"
        $pattern = '/stampa\s+(?:a\s+)?(\d+(?:\/\d+)?)\s*colou?r[ei]?\b(.{0,120})/is';

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
            '/\(CON\s+LASTRINA\b/i',
            '/\bBrossura\b/i',
            '/\bPunto\s+Metallico\b/i',
            '/\bspedizione\b/i',
            '/\bPLAST\.\s/i',
            '/\bPlastificazione\b/i',
            '/\bFascettatura\b/i',
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
     * Estrae il codice fustella (FSxxxx) dalla descrizione.
     * Prende il primo codice FS trovato.
     */
    public static function parseFustella(string $descrizione): ?string
    {
        if (preg_match('/\b(FS\d{3,5}(?:_[A-Z0-9\-]+)?)\b/', $descrizione, $m)) {
            // Restituisci solo il codice base FSxxxx
            if (preg_match('/^(FS\d{3,5})/', $m[1], $base)) {
                return $base[1];
            }
            return $m[1];
        }
        return null;
    }
}
