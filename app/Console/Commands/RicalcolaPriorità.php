<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrdineFase;
use App\Http\Controllers\DashboardOwnerController;

class RicalcolaPriorita extends Command
{
    protected $signature = 'priorita:ricalcola';
    protected $description = 'Ricalcola le priorità di tutti gli ordini';

    public function handle()
    {
        $controller = new DashboardOwnerController();

        OrdineFase::with('ordine')->get()->each(function ($fase) use ($controller) {
            $controller->calcolaOreEPriorita($fase);
        });

        $this->info('Tutte le priorità sono state ricalcolate!');
    }
}