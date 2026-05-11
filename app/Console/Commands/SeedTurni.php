<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTurni extends Command
{
    protected $signature = 'turni:seed';
    protected $description = 'Popola la tabella turni con i dati settimanali';

    public function handle()
    {
        $giorni = ['2026-05-11', '2026-05-12', '2026-05-13', '2026-05-14', '2026-05-15', '2026-05-16'];

        $dati = [
            'MENALE FIORE'              => ['T', 'T', 'T', 'T', 'T', ''],
            'SCOTTI FRANCESCO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'CARDILLO ANTONIO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'CARDILLO MARCO'            => ['T', 'T', 'T', 'T', 'T', ''],
            'PECORARO MARCO'            => ['T', 'T', 'T', 'T', 'T', ''],
            'CRISANTI LORENZO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'SCARANO GIANGIUSEPPE'      => ['T', 'T', 'T', 'T', 'T', ''],
            'VERDE FRANCESCO'           => ['T', 'T', 'T', 'T', 'T', ''],
            'MARFELLA DOMENICO'         => ['F', '1', '1', '1', '1', ''],
            'BORTONE PAOLO'             => ['T', '2', '2', '2', '2', ''],
            'GENNARO LA SCALA'          => ['T', 'T', 'T', 'T', 'T', ''],
            'MENALE LUIGI'              => ['2', '2', '2', '2', '2', ''],
            'MENALE FRANCESCO'          => ['1', '2', '2', '2', '2', ''],
            'TORROMACCO GIANNANTONIO'   => ['T', 'T', 'T', 'T', 'T', ''],
            'IULIANO PASQUALE'          => ['2', '2', '2', '2', '2', ''],
            'CIRO RAO'                  => ['1', '1', '1', '1', '1', ''],
            'SORBO LUCA'                => ['T', 'T', 'T', 'T', 'T', ''],
            'CASTELLANO ANTONIO'        => ['T', 'T', 'T', 'T', 'T', ''],
            "D'ORAZIO MIRKO"            => ['T', 'T', 'T', 'T', 'T', ''],
            'FRANCESE FRANCESCO'        => ['T', 'PSR', 'T', 'T', 'T', ''],
            'RUSSO MICHELE'             => ['T', 'T', 'T', 'T', 'T', ''],
            'GARGIULO VINCENZO'         => ['2', '2', '2', '2', '2', ''],
            'PAGANO DIEGO'              => ['1', '1', '1', '1', '1', ''],
            'MENALE BENITO'             => ['2', 'PSR', '2', 'R', '1', '1'],
            'MARINO LUIGI'              => ['2', '2', '2', '3', 'R', '1'],
            'MORMILE COSIMO'            => ['1', '2', '2', '3', '3', 'R'],
            'VINCENZO MARRONE'          => ['1', '1', '2', '2', '3', 'R'],
            'CHRISTIAN SIMONETTI'       => ['R', '1', '1', '2', '2', 'R'],
            'BARBATO RAFFAELE'          => ['3', 'R', '1', '1', '2', '2'],
            'ZAMPELLA ALESSANDRO'       => ['3', 'T', 'R', '1', '1', '2'],
            'SANTORO MARIO'             => ['T', 'T', 'T', 'T', 'T', ''],
        ];

        $count = 0;
        foreach ($dati as $nome => $turni) {
            foreach ($turni as $i => $turno) {
                if ($turno === '') continue;
                DB::table('turni')->updateOrInsert(
                    ['cognome_nome' => $nome, 'data' => $giorni[$i]],
                    ['turno' => $turno, 'updated_at' => now(), 'created_at' => now()]
                );
                $count++;
            }
        }

        $this->info("Inseriti {$count} turni per la settimana 11 - 16 maggio 2026.");
        return 0;
    }
}
