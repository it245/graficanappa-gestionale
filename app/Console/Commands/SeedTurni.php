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
        $giorni = ['2026-05-04', '2026-05-05', '2026-05-06', '2026-05-07', '2026-05-08', '2026-05-09'];

        $dati = [
            'MENALE FIORE'              => ['T', 'T', 'T', 'T', 'T', ''],
            'SCOTTI FRANCESCO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'CARDILLO ANTONIO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'CARDILLO MARCO'            => ['T', 'T', 'T', 'T', 'T', ''],
            'PECORARO MARCO'            => ['T', 'T', 'T', 'T', 'T', ''],
            'CRISANTI LORENZO'          => ['T', 'T', 'T', 'T', 'T', ''],
            'SCARANO GIANGIUSEPPE'      => ['T', 'T', 'T', 'T', 'T', ''],
            'VERDE FRANCESCO'           => ['T', 'T', 'T', 'T', 'T', ''],
            'MARFELLA DOMENICO'         => ['2', '2', '2', '2', '2', ''],
            'BORTONE PAOLO'             => ['1', '1', '1', '1', '1', ''],
            'GENNARO LA SCALA'          => ['1', '1', '1', '1', '1', ''],
            'MENALE LUIGI'              => ['1', '1', '1', '1', '1', ''],
            'MENALE FRANCESCO'          => ['2', '2', '2', '2', '2', ''],
            'TORROMACCO GIANNANTONIO'   => ['T', 'T', 'T', 'T', 'T', ''],
            'IULIANO PASQUALE'          => ['1', '1', '1', '1', '1', ''],
            'CIRO RAO'                  => ['2', '2', '2', '2', '2', ''],
            'SORBO LUCA'                => ['T', 'T', 'T', 'T', 'T', ''],
            'CASTELLANO ANTONIO'        => ['T', 'T', 'T', 'T', 'T', ''],
            "D'ORAZIO MIRKO"            => ['T', 'T', 'T', 'T', 'T', ''],
            'FRANCESE FRANCESCO'        => ['T', 'T', 'T', 'T', 'T', ''],
            'RUSSO MICHELE'             => ['T', 'T', 'T', 'T', 'T', ''],
            'GARGIULO VINCENZO'         => ['1', '1', '1', '1', '1', ''],
            'PAGANO DIEGO'              => ['2', '2', '2', '2', '2', ''],
            'MENALE BENITO'             => ['2', 'R', '1', '1', '2', '2'],
            'MARINO LUIGI'              => ['1', '3', 'R', '1', '1', '2'],
            'MORMILE COSIMO'            => ['2', '3', '3', 'R', '1', '1'],
            'VINCENZO MARRONE'          => ['2', '2', '3', '3', 'R', '1'],
            'CHRISTIAN SIMONETTI'       => ['1', '2', '2', '3', '3', 'R'],
            'BARBATO RAFFAELE'          => ['1', '1', '2', '2', '3', 'R'],
            'ZAMPELLA ALESSANDRO'       => ['R', '1', '1', '2', '2', 'R'],
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

        $this->info("Inseriti {$count} turni per la settimana 04 - 09 maggio 2026.");
        return 0;
    }
}
