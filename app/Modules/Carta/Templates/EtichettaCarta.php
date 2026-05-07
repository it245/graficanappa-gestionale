<?php

declare(strict_types=1);

namespace App\Modules\Carta\Templates;

use App\Models\Articolo;
use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;

/**
 * Generatore payload etichetta carta (Data Matrix).
 *
 * Convenzione MES Grafica Nappa: Data Matrix plain (non GS1), con i campi
 * principali separati da `|` per parsing veloce dagli scanner industriali.
 *
 * Layout payload:
 *   COD|MARCA|TIPO|GR|SEQ|QTA8|UM
 * dove QTA8 è la qta_richiesta zero-padded a 8 cifre (compat. lettore corrente).
 *
 * @example
 *   Articolo cod_art=02W.ALASKA.GC1.300.003 qta=1500 um=FG
 *   payload = "02W.ALASKA.GC1.300.003|ALASKA|GC1|300|003|00001500|FG"
 */
final class EtichettaCarta
{
    /**
     * Genera la struttura dati per stampare un'etichetta.
     *
     * @return array{
     *   cod_art: string,
     *   marca: ?string,
     *   tipo: ?string,
     *   grammatura: ?int,
     *   sequenza: ?int,
     *   descrizione: ?string,
     *   qta: float,
     *   um: ?string,
     *   datamatrix_payload: string,
     *   valido: bool,
     * }
     */
    public function genera(Articolo $art): array
    {
        $codArt = (string) ($art->cod_art ?? '');
        $vo = CodiceArticoloOnda::provaDaStringa($codArt);

        $qta = (float) ($art->qta_richiesta ?? 0);
        $um = $art->um !== null ? (string) $art->um : null;

        $marca = $vo?->marca;
        $tipo = $vo?->tipo;
        $grammatura = $vo?->grammatura;
        $sequenza = $vo?->sequenza;

        $payload = sprintf(
            '%s|%s|%s|%s|%s|%s|%s',
            $codArt,
            $marca ?? '',
            $tipo ?? '',
            $grammatura !== null ? (string) $grammatura : '',
            $sequenza !== null ? str_pad((string) $sequenza, 3, '0', STR_PAD_LEFT) : '',
            str_pad((string) (int) round($qta), 8, '0', STR_PAD_LEFT),
            $um ?? '',
        );

        return [
            'cod_art' => $codArt,
            'marca' => $marca,
            'tipo' => $tipo,
            'grammatura' => $grammatura,
            'sequenza' => $sequenza,
            'descrizione' => $art->descrizione !== null ? (string) $art->descrizione : null,
            'qta' => $qta,
            'um' => $um,
            'datamatrix_payload' => $payload,
            'valido' => $vo?->eValido() ?? false,
        ];
    }
}
