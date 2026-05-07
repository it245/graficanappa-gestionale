<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Nota fustella immutable: autore + testo + timestamp.
 *
 * Le note sono prefissate con "[Autore - dd/mm HH:MM]" e accodate al campo
 * `note` esistente su `ordine_fasi` (mantenendo lo storico).
 *
 * Esempio formattato:
 *   [Mirko - 23/04 14:30] cambiata lama, sostituita nel BOBST
 */
final readonly class NotaFustella
{
    public function __construct(
        public string $autore,
        public string $testo,
        public Carbon $timestamp,
    ) {
        if (trim($autore) === '') {
            throw new InvalidArgumentException('Autore nota fustella obbligatorio');
        }
        if (trim($testo) === '') {
            throw new InvalidArgumentException('Testo nota fustella vuoto');
        }
    }

    /**
     * Helper di costruzione con timestamp = now().
     */
    public static function ora(string $autore, string $testo): self
    {
        return new self(
            autore: trim($autore),
            testo: trim($testo),
            timestamp: Carbon::now(),
        );
    }

    /**
     * Formato canonico: "[Autore - dd/mm HH:MM] testo nota".
     */
    public function format(): string
    {
        return sprintf(
            '[%s - %s] %s',
            $this->autore,
            $this->timestamp->format('d/m H:i'),
            $this->testo,
        );
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function toArray(): array
    {
        return [
            'autore' => $this->autore,
            'testo' => $this->testo,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
