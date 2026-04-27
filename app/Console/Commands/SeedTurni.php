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
        $giorni = ['2026-04-27', '2026-04-28', '2026-04-29', '2026-04-30', '2026-05-01', '2026-05-02'];

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
            'GENNARO LA SCALA'          => ['2', '2', '2', '2', '2', ''],
            'MENALE LUIGI'              => ['2', '2', '2', '2', '2', ''],
            'MENALE FRANCESCO'          => ['1', '1', '1', '1', '1', ''],
            'TORROMACCO GIANNANTONIO'   => ['1', '1', '1', '1', '1', ''],
            'IULIANO PASQUALE'          => ['2', '2', '2', '2', '2', ''],
            'CIRO RAO'                  => ['T', 'T', 'T', 'T', 'T', ''],
            'SORBO LUCA'                => ['T', 'T', 'T', 'T', 'T', ''],
            'CASTELLANO ANTONIO'        => ['T', 'T', 'T', 'T', 'T', ''],
            "D'ORAZIO MIRKO"            => ['T', 'T', 'T', 'T', 'T', ''],
            'FRANCESE FRANCESCO'        => ['T', 'T', 'T', 'T', 'T', ''],
            'RUSSO MICHELE'             => ['T', 'T', 'T', 'T', 'T', ''],
            'GARGIULO VINCENZO'         => ['F', '2', '2', '2', '2', ''],
            'PAGANO DIEGO'              => ['T', '1', '1', '1', '1', ''],
            'MENALE BENITO'             => ['R', '1', '1', '2', '2', 'R'],
            'MARINO LUIGI'              => ['T', 'R', '1', '1', '2', ''],
            'MORMILE COSIMO'            => ['T', '3', 'R', '1', '1', '2'],
            'CHRISTIAN SIMONETTI'       => ['2', '3', '3', 'R', '1', ''],
            'VINCENZO MARRONE'          => ['2', '2', '3', '3', 'R', '1'],
            'BARBATO RAFFAELE'          => ['1', '2', '2', '3', '3', 'R'],
            'ZAMPELLA ALESSANDRO'       => ['1', '1', '2', '2', '3', 'R'],
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

        $this->info("Inseriti {$count} turni per la settimana 27 aprile - 2 maggio 2026.");
        return 0;
    }
}
