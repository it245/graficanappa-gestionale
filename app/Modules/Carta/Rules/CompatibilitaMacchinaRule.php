<?php

declare(strict_types=1);

namespace App\Modules\Carta\Rules;

use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;

/**
 * Regole di compatibilità tra carta (cod_art Onda) e macchina di stampa.
 *
 * Limiti basati sui datasheet macchina Grafica Nappa:
 *  - XL106 (offset Heidelberg): 70x100, grammatura 80-400gr
 *  - XL75  (offset Heidelberg): 50x70,  grammatura 80-400gr
 *  - INDIGO (HP Indigo 12000):  53x75,  grammatura 70-400gr ma carta certificata
 *  - JOH   (caldo a foglio):    72x102, grammatura 100-450gr
 *  - BOBST (fustella/rilievo):  70x100, grammatura 150-500gr (cartoncino)
 *  - ZUND  (taglio digitale):   qualunque, fino a 600gr
 *  - MGI   (UV spot):           53x75,  grammatura 135-450gr
 */
final class CompatibilitaMacchinaRule
{
    /**
     * Limiti grammatura per macchina (min, max) in g/m².
     */
    private const LIMITI_GRAMMATURA = [
        'XL106'  => [80, 400],
        'XL75'   => [80, 400],
        'INDIGO' => [70, 400],
        'JOH'    => [100, 450],
        'BOBST'  => [150, 500],
        'ZUND'   => [40, 600],
        'MGI'    => [135, 450],
    ];

    /**
     * Famiglie/tipi NON compatibili per macchina.
     * Es: Indigo non stampa su PVC senza primer, JOH non lavora carte adesive.
     */
    private const TIPI_INCOMPATIBILI = [
        'INDIGO' => ['PVC', 'MICROONDA', 'TELA'],
        'JOH'    => ['ADESIVO', 'PVC'],
        'XL106'  => ['MICROONDA'],
        'XL75'   => ['MICROONDA'],
    ];

    /**
     * Verifica se l'articolo può essere stampato/lavorato sulla macchina indicata.
     *
     * @param CodiceArticoloOnda $art        articolo Onda parsato
     * @param string             $macchinaId identificativo macchina (es. 'XL106')
     */
    public function puoStampareSu(CodiceArticoloOnda $art, string $macchinaId): bool
    {
        $id = strtoupper(trim($macchinaId));

        if (! isset(self::LIMITI_GRAMMATURA[$id])) {
            // Macchina sconosciuta: meglio prudente, non compatibile.
            return false;
        }

        [$min, $max] = self::LIMITI_GRAMMATURA[$id];

        if ($art->grammatura < $min || $art->grammatura > $max) {
            return false;
        }

        $tipiNo = self::TIPI_INCOMPATIBILI[$id] ?? [];
        if (in_array($art->tipo, $tipiNo, true)) {
            return false;
        }

        return $art->eValido();
    }

    /**
     * Ritorna il motivo per cui l'articolo NON è compatibile, o null se compatibile.
     * Utile per messaggi UI/log.
     */
    public function motivoIncompatibilita(CodiceArticoloOnda $art, string $macchinaId): ?string
    {
        $id = strtoupper(trim($macchinaId));

        if (! isset(self::LIMITI_GRAMMATURA[$id])) {
            return "Macchina '{$macchinaId}' non riconosciuta";
        }

        [$min, $max] = self::LIMITI_GRAMMATURA[$id];

        if ($art->grammatura < $min) {
            return "Grammatura {$art->grammatura}gr < minimo {$min}gr per {$id}";
        }
        if ($art->grammatura > $max) {
            return "Grammatura {$art->grammatura}gr > massimo {$max}gr per {$id}";
        }

        $tipiNo = self::TIPI_INCOMPATIBILI[$id] ?? [];
        if (in_array($art->tipo, $tipiNo, true)) {
            return "Tipo '{$art->tipo}' non supportato da {$id}";
        }

        return null;
    }

    /**
     * Ritorna l'elenco macchine compatibili per un articolo.
     *
     * @return list<string>
     */
    public function macchineCompatibili(CodiceArticoloOnda $art): array
    {
        $out = [];
        foreach (array_keys(self::LIMITI_GRAMMATURA) as $m) {
            if ($this->puoStampareSu($art, $m)) {
                $out[] = $m;
            }
        }

        return $out;
    }
}
