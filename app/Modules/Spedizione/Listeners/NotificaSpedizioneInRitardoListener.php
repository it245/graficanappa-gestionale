<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Listeners;

use App\Modules\Spedizione\Events\SpedizioneInRitardo;

/**
 * Notifica owner + reparto spedizione (canali_critici) quando una
 * spedizione BRT è oltre la data di consegna prevista.
 *
 * TODO:
 *  - filtrare esiti BRT non bloccanti (es. -22 multi-spedizione, già escluso da email)
 *  - costruire payload con tracking number e link portale BRT
 *  - dispatch sui canali_critici
 */
final class NotificaSpedizioneInRitardoListener
{
    public function handle(SpedizioneInRitardo $event): void
    {
        // TODO
    }
}
