<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Rules;

use App\Modules\Reparti\Enums\CodiceReparto;

/**
 * Regola pura: dato il codice di una fase di catalogo (es. STAMPAXL106,
 * PI01, FUSTBOBST75X106) restituisce il {@see CodiceReparto} target.
 *
 * Sorgente di verità del mapping: `database/seeders/FasiCatalogoSeeder.php`.
 * Qui replichiamo i mapping in un array statico per evitare query DB
 * ad ogni risoluzione (la funzione è pure, idempotente, testabile).
 *
 * NOTA: il seeder usa anche pseudo-reparti come "fustella piana",
 * "tagliacarte", "finitura digitale" che NON esistono nella tabella
 * reparti (12 righe canoniche). Qui mappiamo questi pseudo-reparti sul
 * reparto canonico più vicino:
 *   - "fustella piana" / "fustella cilindrica" → FUSTELLA
 *   - "tagliacarte"                            → LEGATORIA (taglio post-stampa)
 *   - "finitura digitale"                      → DIGITALE
 *   - "finestratura"                           → PIEGAINCOLLA
 */
final class AssegnazioneFaseReparto
{
    /**
     * Mappa codiceFase → CodiceReparto.
     * Tenuto come metodo statico per essere risolto a compile-time
     * (PHP cache-able) e per test isolati senza container.
     *
     * @return array<string, CodiceReparto>
     */
    private static function mappa(): array
    {
        return [
            // STAMPA OFFSET (XL106 + esterne)
            'STAMPA'                  => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106'             => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.1'           => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.2'           => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.3'           => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.4'           => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.5'           => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.6'           => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.7'           => CodiceReparto::STAMPA_OFFSET,

            // DIGITALE
            'STAMPAINDIGO'            => CodiceReparto::DIGITALE,
            'STAMPAINDIGOBN'          => CodiceReparto::DIGITALE,
            'STAMPAINDIGOBIANCO'      => CodiceReparto::DIGITALE,
            'TAGLIOINDIGO'            => CodiceReparto::DIGITALE,
            // finitura digitale → DIGITALE (pseudo-reparto seeder)
            'FOIL.MGI.30M'            => CodiceReparto::DIGITALE,
            'FOILMGI'                 => CodiceReparto::DIGITALE,
            'ZUND'                    => CodiceReparto::DIGITALE,
            'CORDONATURAPETRATTO'     => CodiceReparto::DIGITALE,
            'DEKIA-Difficile'         => CodiceReparto::DIGITALE,
            'DEKIA-semplice'          => CodiceReparto::DIGITALE,

            // STAMPA A CALDO (JOH)
            'STAMPACALDOJOH'          => CodiceReparto::STAMPA_A_CALDO,
            'STAMPACALDOJOH0,1'       => CodiceReparto::STAMPA_A_CALDO,
            'STAMPACALDO04'           => CodiceReparto::STAMPA_A_CALDO,
            'STAMPACALDOBR'           => CodiceReparto::STAMPA_A_CALDO,
            'STAMPALAMINAORO'         => CodiceReparto::STAMPA_A_CALDO,
            'RILIEVOASECCOJOH'        => CodiceReparto::STAMPA_A_CALDO,

            // PLASTIFICAZIONE
            'PLAOPA1LATO'             => CodiceReparto::PLASTIFICAZIONE,
            'PLAOPABV'                => CodiceReparto::PLASTIFICAZIONE,
            'PLALUX1LATO'             => CodiceReparto::PLASTIFICAZIONE,
            'PLALUXBV'                => CodiceReparto::PLASTIFICAZIONE,
            'PLAPOLIESARG1LATO'       => CodiceReparto::PLASTIFICAZIONE,
            'PLASAB1LATO'             => CodiceReparto::PLASTIFICAZIONE,
            'PLASOFTBV'               => CodiceReparto::PLASTIFICAZIONE,
            'PLASOFTTOUCH1'           => CodiceReparto::PLASTIFICAZIONE,

            // FUSTELLA (piana + cilindrica → fustella canonica)
            'FUSTBOBSTRILIEVI'        => CodiceReparto::FUSTELLA,
            'FUSTBIML75X106'          => CodiceReparto::FUSTELLA,
            'FUSTBOBST75X106'         => CodiceReparto::FUSTELLA,
            'FUSTIML75X106'           => CodiceReparto::FUSTELLA,
            'FUSTELLATURA72X51'       => CodiceReparto::FUSTELLA,
            'FUSTSTELG33.44'          => CodiceReparto::FUSTELLA,
            'FUSTSTELP25.35'          => CodiceReparto::FUSTELLA,
            'FUST.STARPACK.74X104'    => CodiceReparto::FUSTELLA,

            // PIEGAINCOLLA (incluso pseudo-reparto "finestratura")
            'PI01'                    => CodiceReparto::PIEGAINCOLLA,
            'PI02'                    => CodiceReparto::PIEGAINCOLLA,
            'PI03'                    => CodiceReparto::PIEGAINCOLLA,
            'FIN01'                   => CodiceReparto::PIEGAINCOLLA,
            'FIN03'                   => CodiceReparto::PIEGAINCOLLA,
            'FIN04'                   => CodiceReparto::PIEGAINCOLLA,
            'FINESTRATURA.MANUALE'    => CodiceReparto::PIEGAINCOLLA,
            'FINESTRATURA.INT'        => CodiceReparto::PIEGAINCOLLA,

            // LEGATORIA (incluso pseudo-reparto "tagliacarte")
            'TAGLIACARTE'             => CodiceReparto::LEGATORIA,
            'TAGLIACARTE.IML'         => CodiceReparto::LEGATORIA,
            'PIEGA2ANTECORDONE'       => CodiceReparto::LEGATORIA,
            'PIEGA2ANTESINGOLO'       => CodiceReparto::LEGATORIA,
            'PIEGA3ANTESINGOLO'       => CodiceReparto::LEGATORIA,
            'PIEGA8ANTESINGOLO'       => CodiceReparto::LEGATORIA,
            'PIEGA8TTAVO'             => CodiceReparto::LEGATORIA,
            'PIEGA6ANTESINGOLO'       => CodiceReparto::LEGATORIA,
            'PIEGAMANUALE'            => CodiceReparto::LEGATORIA,
            'PUNTOMETALLICO'          => CodiceReparto::LEGATORIA,
            'PUNTOMETAMANUALE'        => CodiceReparto::LEGATORIA,
            'INCOLLAGGIO.PATTINA'     => CodiceReparto::LEGATORIA,
            'INCOLLAGGIOBLOCCHI'      => CodiceReparto::LEGATORIA,
            'NUM.PROGR.'              => CodiceReparto::LEGATORIA,
            'PERF.BUC'                => CodiceReparto::LEGATORIA,
            'SPIRBLOCCOLIBROA3'       => CodiceReparto::LEGATORIA,
            'SPIRBLOCCOLIBROA4'       => CodiceReparto::LEGATORIA,
            'SPIRBLOCCOLIBROA5'       => CodiceReparto::LEGATORIA,

            // SPEDIZIONE
            'BRT1'                    => CodiceReparto::SPEDIZIONE,

            // ESTERNO (varie lavorazioni conto-terzi)
            'AVVIAMENTISTAMPA.EST1.1' => CodiceReparto::ESTERNO,
            'STAMPA.OFFSET11.EST'     => CodiceReparto::ESTERNO,
            'STAMPA.ESTERNA'          => CodiceReparto::ESTERNO,
            'STAMPABUSTE.EST'         => CodiceReparto::ESTERNO,
            'STAMPACALDOJOHEST'       => CodiceReparto::ESTERNO,
            'UVSPOT.MGI.30M'          => CodiceReparto::ESTERNO,
            'UVSPOT.MGI.9M'           => CodiceReparto::ESTERNO,
            'UVSPOTEST'               => CodiceReparto::ESTERNO,
            'UVSPOTSPESSEST'          => CodiceReparto::ESTERNO,
            'UVSERIGRAFICOEST'        => CodiceReparto::ESTERNO,
            '4graph'                  => CodiceReparto::ESTERNO,
            'ALL.COFANETTO.ISMAsrl'   => CodiceReparto::ESTERNO,
            'PMDUPLO36COP'            => CodiceReparto::ESTERNO,
            'BROSSCOPBANDELLAEST'     => CodiceReparto::ESTERNO,
            'BROSSCOPEST'             => CodiceReparto::ESTERNO,
            'CARTONATO.GEN'           => CodiceReparto::ESTERNO,
            'PUNTOMETALLICOEST'       => CodiceReparto::ESTERNO,
            'PUNTOMETALLICOESTCOPERT.'=> CodiceReparto::ESTERNO,
            'Allest.Manuale'          => CodiceReparto::ESTERNO,
            'ALLEST.SHOPPER'          => CodiceReparto::ESTERNO,
            'ALLEST.SHOPPER030'       => CodiceReparto::ESTERNO,
            'ALLESTIMENTO.ESPOSITORI' => CodiceReparto::ESTERNO,
            'APPL.BIADESIVO30'        => CodiceReparto::ESTERNO,
            'appl.laccetto'           => CodiceReparto::ESTERNO,
            'ARROT2ANGOLI'            => CodiceReparto::ESTERNO,
            'ARROT4ANGOLI'            => CodiceReparto::ESTERNO,
            'ACCOPPIATURA.FOG.33.48INT' => CodiceReparto::ESTERNO,
            'ACCOPPIATURA.FOGLI'      => CodiceReparto::ESTERNO,
            'accopp+fust'             => CodiceReparto::ESTERNO,
        ];
    }

    /**
     * Risolve la fase data al reparto target.
     *
     * @param  string  $codiceFase  Codice fase di catalogo (case-sensitive
     *                              per allineamento con seeder; per il
     *                              prefisso "EXT" e "est " la fase è
     *                              automaticamente ricondotta a ESTERNO).
     */
    public static function reparto(string $codiceFase): CodiceReparto
    {
        $codice = trim($codiceFase);

        // Convenzione Onda: prefisso EXT o "est " → reparto ESTERNO
        if (str_starts_with($codice, 'EXT') || str_starts_with($codice, 'est ')) {
            return CodiceReparto::ESTERNO;
        }

        $mappa = self::mappa();
        if (isset($mappa[$codice])) {
            return $mappa[$codice];
        }

        // Match case-insensitive di fallback (seeder ha duplicati con casing diverso)
        $lower = strtolower($codice);
        foreach ($mappa as $k => $v) {
            if (strtolower($k) === $lower) {
                return $v;
            }
        }

        // Fallback conservativo: lavorazione sconosciuta → PRODUZIONE
        // (zona di smistamento manuale del capo-reparto).
        return CodiceReparto::PRODUZIONE;
    }
}
