<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Listeners;

use App\Events\PhaseCompleted;
use App\Modules\Reportistica\Cache\ReportCache;

/**
 * Listener: invalida la cache dei report quando una fase viene completata.
 *
 * Hook su {@see \App\Events\PhaseCompleted} (lo stesso evento del listener
 * Mossa37 PropagateAvailability — registrazione in AppServiceProvider).
 *
 * Sync (non queueable) di proposito: la cache va aggiornata "al volo"
 * altrimenti la dashboard owner mostra dati vecchi fino a 5-15 minuti.
 */
final class InvalidaReportistica
{
    public function handle(PhaseCompleted $event): void
    {
        ReportCache::invalidaTutto();
    }
}
