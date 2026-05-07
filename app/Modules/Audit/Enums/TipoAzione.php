<?php

declare(strict_types=1);

namespace App\Modules\Audit\Enums;

/**
 * Tipologia di azione tracciata in audit log.
 *
 * Volutamente lasciato come `string` (non `int`) per leggibilità diretta
 * nei dump e nelle query SQL della tabella `audit_logs.action`.
 *
 * Allineato ai valori già presenti nello schema legacy (login/logout/update/...).
 */
enum TipoAzione: string
{
    case Create  = 'create';
    case Update  = 'update';
    case Delete  = 'delete';
    case Read    = 'read';
    case Login   = 'login';
    case Logout  = 'logout';
    case Export  = 'export';
    case Sync    = 'sync';
    case Failed  = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Creazione',
            self::Update => 'Aggiornamento',
            self::Delete => 'Cancellazione',
            self::Read   => 'Lettura',
            self::Login  => 'Login',
            self::Logout => 'Logout',
            self::Export => 'Export dati',
            self::Sync   => 'Sincronizzazione',
            self::Failed => 'Tentativo fallito',
        };
    }

    /**
     * Azioni considerate "scrittura": utili per filtri di compliance
     * (audit RSU vuole vedere chi ha modificato cosa, le READ sono rumore).
     */
    public function eScrittura(): bool
    {
        return in_array($this, [self::Create, self::Update, self::Delete], true);
    }
}
