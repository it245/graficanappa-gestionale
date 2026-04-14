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
        $giorni = ['2026-04-13', '2026-04-14', '2026-04-15', '2026-04-16', '2026-04-17', '2026-04-18'];

        $dati = [
            'MENALE FIORE'              => ['T', 'T', 'T', 'T', 'T', ''],
            'SCOTTI FRANCESCO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'CARDILLO ANTONIO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'CARDILLO MARCO'            => ['T', 'T', 'T', 'T', 'T', ''],
            'PECORARO MARCO'            => ['T', 'T', 'T', 'T', 'T', ''],
            'CRISANTI LORENZO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'SCARANO GIANGIUSEPPE'      => ['T', 'T', 'T', 'T', 'T', ''],
            'VERDE FRANCESCO'           => ['T', 'T', 'T', 'T', 'T', ''],
            'MARFELLA DOMENICO'         => ['1', '1', '1', '1', '1', ''],
            'BORTONE PAOLO'             => ['2', '2', '2', '2', '2', ''],
            'MENALE LUIGI'              => ['2', '2', '2', '2', '2', ''],
            'MENALE FRANCESCO'          => ['1', '1', '1', '1', '1', ''],
            'TORROMACCO GIANNANTONIO'   => ['T', 'T', 'T', 'T', 'T', ''],
            'IULIANO PASQUALE'          => ['2', '2', '2', '2', '2', ''],
            'CIRO RAO'                  => ['1', '1', '1', '1', '1', ''],
            'SORBO LUCA'                => ['T', 'T', 'T', 'T', 'T', ''],
            'CASTELLANO ANTONIO'        => ['T', 'T', 'T', 'T', 'T', ''],
            "D'ORAZIO MIRKO"            => ['T', 'T', 'T', 'T', 'T', ''],
            'FRANCESE FRANCESCO'        => ['T', 'T', 'T', 'T', 'T', ''],
            'RUSSO MICHELE'             => ['T', 'T', 'T', 'T', 'T', ''],
            'GARGIULO VINCENZO'         => ['1', '1', '1', '1', '1', ''],
            'PAGANO DIEGO'              => ['2', '2', '2', '2', '2', ''],
            'MENALE BENITO'             => ['1', '2', '2', '2', '2', 'R'],
            'MARINO LUIGI'              => ['1', '1', '1', '2', '2', 'R'],
            'MORMILE COSIMO'            => ['R', '1', '1', '1', '2', 'R'],
            'CHRISTIAN SIMONETTI'       => ['F', 'F', 'F', 'F', 'F', 'F'],
            'VINCENZO MARRONE'          => ['2', '2', 'R', '1', '1', 'R'],
            'BARBATO RAFFAELE'          => ['2', '2', '2', 'R', '1', '1'],
            'ZAMPELLA ALESSANDRO'       => ['T', 'T', 'T', 'T', 'R', '1'],
            'SANTORO MARIO'             => ['T', 'T', 'T', 'T', 'T', ''],
            'GENNARO LA SCALA'          => ['T', 'T', 'T', 'T', 'T', ''],
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

        $this->info("Inseriti {$count} turni per la settimana 13-18 aprile 2026.");
        return 0;
    }
}
