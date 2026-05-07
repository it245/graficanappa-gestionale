<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\ValueObjects;

/**
 * Indirizzo di destinazione di una spedizione.
 *
 * VO immutabile: alimentato da BRT (DESTINATARIO) o dall'anagrafica Onda.
 */
final class IndirizzoDestinazione
{
    public function __construct(
        public readonly string $via,
        public readonly string $citta,
        public readonly string $cap,
        public readonly string $provincia,
        public readonly string $paese = 'IT',
        public readonly ?string $telefono = null,
    ) {}

    /**
     * Indirizzo formattato in singola riga, utile per stampa/email/etichette.
     */
    public function completo(): string
    {
        $parti = array_filter([
            $this->via,
            trim($this->cap . ' ' . $this->citta),
            $this->provincia !== '' ? '(' . $this->provincia . ')' : null,
            $this->paese,
        ], static fn ($v) => $v !== null && $v !== '');

        return implode(', ', $parti);
    }

    public function vuoto(): bool
    {
        return $this->via === '' && $this->citta === '' && $this->cap === '';
    }
}
