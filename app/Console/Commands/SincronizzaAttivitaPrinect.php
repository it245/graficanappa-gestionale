<?php

namespace App\Console\Commands;

use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;
use Illuminate\Console\Command;

class SincronizzaAttivitaPrinect extends Command
{
    protected $signature = 'prinect:sync-attivita';

    protected $description = 'Importa lo storico attivita dalla macchina Prinect (Avviamento + Produzione)';

    public function handle(PrinectService $prinect): int
    {
        $syncService = new PrinectSyncService($prinect);
        $importate = $syncService->sincronizzaAttivita();

        $this->info("Attivita importate: {$importate}");

        return self::SUCCESS;
    }
}
