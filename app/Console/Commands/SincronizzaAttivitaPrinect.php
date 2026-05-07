<?php

namespace App\Console\Commands;

use App\Http\Services\PrinectSyncService;
use Illuminate\Console\Command;

class SincronizzaAttivitaPrinect extends Command
{
    protected $signature = 'prinect:sync-attivita';

    protected $description = 'Importa lo storico attivita dalla macchina Prinect (Avviamento + Produzione)';

    /**
     * Risolve PrinectSyncService dal container Laravel: dopo il refactor
     * Strangler Fig il constructor inietta 4 service modulo Prinect, quindi
     * non si può più istanziarlo a mano. Il container li auto-risolve.
     */
    public function handle(PrinectSyncService $syncService): int
    {
        $importate = $syncService->sincronizzaAttivita();

        $this->info("Attivita importate: {$importate}");

        return self::SUCCESS;
    }
}
