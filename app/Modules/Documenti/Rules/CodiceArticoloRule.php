<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Rules;

use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;

/**
 * Wrapper di compatibilità per il dominio Documenti.
 *
 * Strangler Fig: la logica di parsing del cod_art Onda
 * (`02W.MARCA.TIPO.GRAMMATURA.SEQ`) è single source of truth nel
 * VO {@see CodiceArticoloOnda} del modulo Carta. Questa Rule resta come
 * facciata stabile per i consumer del modulo Documenti.
 */
final class CodiceArticoloRule
{
    /**
     * Pattern Onda: 02W.<famiglia>.<gradoMacchinabilita>.<grammatura>.<sequenza>
     * Esempio: 02W.ALASKA.GC1.300.003
     */
    public function validaFormatoOnda(string $cod): bool
    {
        return CodiceArticoloOnda::provaDaStringa($cod) !== null;
    }

    /**
     * Estrae il segmento "marca" (storicamente chiamato qui "famiglia").
     *
     * @throws \InvalidArgumentException se il cod_art non è in formato Onda
     */
    public function estraeFamiglia(string $cod): string
    {
        return CodiceArticoloOnda::daStringa($cod)->marca;
    }

    /**
     * Restituisce tutti i segmenti decodificati o null se invalido.
     *
     * Mantiene la shape storica `[prefisso, famiglia, grado, grammatura, sequenza]`
     * per non rompere i consumer esistenti del modulo Documenti.
     *
     * @return array{prefisso: string, famiglia: string, grado: string, grammatura: int, sequenza: int}|null
     */
    public function decompone(string $cod): ?array
    {
        $vo = CodiceArticoloOnda::provaDaStringa($cod);
        if ($vo === null) {
            return null;
        }

        return [
            'prefisso' => CodiceArticoloOnda::PREFISSO,
            'famiglia' => $vo->marca,
            'grado' => $vo->tipo,
            'grammatura' => $vo->grammatura,
            'sequenza' => $vo->sequenza,
        ];
    }
}
