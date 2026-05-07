<?php

declare(strict_types=1);

namespace App\Modules\Operatori\Enums;

/**
 * Ruoli operatore — string backed enum.
 * Allineato ai valori usati nella colonna `operatori.ruolo`.
 */
enum RuoloOperatore: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Operatore = 'operatore';
    case Magazzino = 'magazzino';
    case Spedizione = 'spedizione';

    /**
     * Costruisce l'enum da un valore stringa libero (es. dal DB),
     * con fallback a Operatore se il valore non e' riconosciuto.
     */
    public static function fromStringOrDefault(?string $value): self
    {
        if ($value === null) {
            return self::Operatore;
        }

        return self::tryFrom(strtolower(trim($value))) ?? self::Operatore;
    }
}
