<?php

declare(strict_types=1);

namespace App\Modules\Audit\Enums;

/**
 * Entità di dominio tracciabili nei log di audit.
 *
 * Mappa (vagamente) sui FQCN dei model Eloquent ma resta libero (string)
 * per coprire anche eventi non-model (es. SESSION, EXPORT_FILE).
 *
 * Memorizzato in colonna `audit_logs.model` (compat con schema legacy).
 */
enum EntitaAudit: string
{
    case Ordine     = 'ordine';
    case OrdineFase = 'ordine_fase';
    case Commessa   = 'commessa';
    case Magazzino  = 'magazzino';
    case Movimento  = 'movimento_magazzino';
    case Fustella   = 'fustella';
    case Utente     = 'utente';
    case Operatore  = 'operatore';
    case Sessione   = 'sessione';
    case Documento  = 'documento';
    case Sistema    = 'sistema';

    public function label(): string
    {
        return match ($this) {
            self::Ordine     => 'Ordine',
            self::OrdineFase => 'Fase di ordine',
            self::Commessa   => 'Commessa',
            self::Magazzino  => 'Articolo magazzino',
            self::Movimento  => 'Movimento magazzino',
            self::Fustella   => 'Fustella',
            self::Utente     => 'Utente',
            self::Operatore  => 'Operatore',
            self::Sessione   => 'Sessione',
            self::Documento  => 'Documento',
            self::Sistema    => 'Sistema',
        };
    }
}
