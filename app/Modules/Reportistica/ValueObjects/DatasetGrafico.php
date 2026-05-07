<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\ValueObjects;

use App\Modules\Reportistica\Enums\TipoGrafico;

/**
 * Dataset compat Chart.js (labels[] + values[]).
 *
 * Il metodo {@see toChartJs()} produce direttamente la struttura
 * `{ labels, datasets: [...] }` consumata dal frontend esistente.
 */
final class DatasetGrafico
{
    /**
     * @param list<string>             $labels
     * @param list<int|float>          $values
     */
    public function __construct(
        public readonly array $labels,
        public readonly array $values,
        public readonly TipoGrafico $tipo = TipoGrafico::LINE,
        public readonly string $titolo = '',
        public readonly ?string $colore = null,
    ) {}

    public function toChartJs(): array
    {
        return [
            'type'   => $this->tipo->value,
            'labels' => $this->labels,
            'datasets' => [[
                'label'           => $this->titolo,
                'data'            => $this->values,
                'backgroundColor' => $this->colore ?? 'rgba(59,130,246,0.5)',
                'borderColor'     => $this->colore ?? 'rgba(59,130,246,1)',
                'borderWidth'     => 1,
            ]],
        ];
    }

    /**
     * Costruisce dal pattern collection ricorrente `[label => valore]`.
     *
     * @param iterable<string,int|float> $map
     */
    public static function fromMap(iterable $map, TipoGrafico $tipo = TipoGrafico::LINE, string $titolo = ''): self
    {
        $labels = [];
        $values = [];
        foreach ($map as $k => $v) {
            $labels[] = (string) $k;
            $values[] = is_numeric($v) ? $v + 0 : 0;
        }
        return new self($labels, $values, $tipo, $titolo);
    }
}
