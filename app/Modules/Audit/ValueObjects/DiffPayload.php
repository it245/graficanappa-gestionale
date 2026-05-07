<?php

declare(strict_types=1);

namespace App\Modules\Audit\ValueObjects;

/**
 * Payload immutable di differenza prima/dopo per audit.
 *
 * Mantiene le array già *sanitizzate* (password/token mascherati a monte
 * via DatiSensibiliRule). Esposto solo via toArray() per consumo dei sink.
 */
final class DiffPayload
{
    /**
     * @param array<string,mixed>|null $prima
     * @param array<string,mixed>|null $dopo
     */
    public function __construct(
        public readonly ?array $prima = null,
        public readonly ?array $dopo = null,
    ) {}

    /**
     * Solo le chiavi effettivamente cambiate (utile per UPDATE).
     * Se prima/dopo è null restituisce array vuoto.
     *
     * @return array<string,array{prima:mixed,dopo:mixed}>
     */
    public function delta(): array
    {
        if ($this->prima === null || $this->dopo === null) {
            return [];
        }

        $delta = [];
        $chiavi = array_unique(array_merge(array_keys($this->prima), array_keys($this->dopo)));
        foreach ($chiavi as $k) {
            $a = $this->prima[$k] ?? null;
            $b = $this->dopo[$k] ?? null;
            if ($a !== $b) {
                $delta[$k] = ['prima' => $a, 'dopo' => $b];
            }
        }
        return $delta;
    }

    public function eVuoto(): bool
    {
        return ($this->prima === null || $this->prima === [])
            && ($this->dopo === null || $this->dopo === []);
    }
}
