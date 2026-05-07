<?php

declare(strict_types=1);

namespace App\Modules\Operatori\Permessi;

/**
 * Catalogo permessi azione del MES.
 * Da NON usare come stringhe magiche nei controller — passare per PermessiService.
 */
enum Permesso: string
{
    case AVVIA_FASE = 'avvia_fase';
    case TERMINA_FASE = 'termina_fase';
    case MODIFICA_PRIORITA = 'modifica_priorita';
    case SCARICO_CARTA = 'scarico_carta';
    case GESTISCE_DDT = 'gestisce_ddt';
    case CREA_OPERATORE = 'crea_operatore';
    case MODIFICA_FASE_REPARTO_ALTRO = 'modifica_fase_reparto_altro';
}
