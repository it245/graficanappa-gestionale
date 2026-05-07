<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Rules;

final class CodiceArticoloRule
{
    /**
     * Pattern Onda: 02W.<famiglia>.<gradoMacchinabilita>.<grammatura>.<sequenza>
     * Esempio: 02W.ALASKA.GC1.300.003
     */
    private const PATTERN_ONDA = '/^02W\.[A-Z0-9]+\.[A-Z0-9]+\.\d+\.\d+$/';

    public function validaFormatoOnda(string $cod): bool
    {
        return preg_match(self::PATTERN_ONDA, trim($cod)) === 1;
    }

    public function estraeFamiglia(string $cod): string
    {
        $parti = explode('.', trim($cod));

        if (count($parti) < 2) {
            throw new \InvalidArgumentException("Codice articolo non valido: {$cod}");
        }

        return $parti[1];
    }

    /**
     * Restituisce tutti i segmenti decodificati o null se invalido.
     *
     * @return array{prefisso: string, famiglia: string, grado: string, grammatura: int, sequenza: int}|null
     */
    public function decompone(string $cod): ?array
    {
        if (!$this->validaFormatoOnda($cod)) {
            return null;
        }

        $parti = explode('.', trim($cod));

        return [
            'prefisso' => $parti[0],
            'famiglia' => $parti[1],
            'grado' => $parti[2],
            'grammatura' => (int) $parti[3],
            'sequenza' => (int) $parti[4],
        ];
    }
}
