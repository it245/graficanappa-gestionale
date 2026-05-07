<?php

declare(strict_types=1);

namespace App\Modules\Onda\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento dispatchato quando un cliente Onda viene riflesso nel MES
 * (solo cliente_nome sull'Ordine, no anagrafica dedicata per ora).
 *
 * Stub di estensione futura: quando il MES avrà una tabella `clienti`
 * questa diventerà la chiave per l'aggiornamento incrementale.
 */
final class ClienteSincronizzato
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $ragioneSociale,
        public readonly ?int $idAnagraficaOnda = null,
    ) {}
}
