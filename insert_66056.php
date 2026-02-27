<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

$commessa = '0066056-26';

// Articolo 1: AST.1 KG MAXTRIS LA NAPOLETANITA' - 3000 pz
$o1 = Ordine::firstOrCreate(
    ['commessa' => $commessa, 'cod_art' => '02892.AS.ST.FI.TU.1077'],
    ['cliente_nome' => 'ITALIANA CONFETTI SRL', 'descrizione' => "AST.1 KG MAXTRIS LA NAPOLETANITA'", 'qta_richiesta' => 3000, 'um' => 'FG', 'stato' => 0, 'data_prevista_consegna' => '2026-02-20', 'data_registrazione' => '2026-02-20']
);

// Articolo 2: AST.1 KG LOVE FRUIT ARANCIA - 1000 pz
$o2 = Ordine::firstOrCreate(
    ['commessa' => $commessa, 'cod_art' => '02080.AS.ST.FI.TU.0940'],
    ['cliente_nome' => 'ITALIANA CONFETTI SRL', 'descrizione' => 'AST.1 KG LOVE FRUIT ARANCIA', 'qta_richiesta' => 1000, 'um' => 'FG', 'stato' => 0, 'data_prevista_consegna' => '2026-02-20', 'data_registrazione' => '2026-02-20']
);

// Reparti e fasi catalogo
$repartoLeg = Reparto::where('nome', 'legatoria')->first();
$repartoPi = Reparto::where('nome', 'piegaincolla')->first();
$repartoSped = Reparto::where('nome', 'spedizione')->first();

$fin01 = FasiCatalogo::firstOrCreate(['nome' => 'FIN01'], ['reparto_id' => $repartoLeg->id]);
$pi01 = FasiCatalogo::firstOrCreate(['nome' => 'PI01'], ['reparto_id' => $repartoPi->id]);
$brt1 = FasiCatalogo::firstOrCreate(['nome' => 'BRT1'], ['reparto_id' => $repartoSped->id]);

foreach ([$o1, $o2] as $o) {
    if ($o->fasi()->count() == 0) {
        $o->fasi()->createMany([
            ['fase' => 'FIN01', 'fase_catalogo_id' => $fin01->id, 'stato' => 0, 'qta_fase' => $o->qta_richiesta, 'um' => 'FG'],
            ['fase' => 'PI01', 'fase_catalogo_id' => $pi01->id, 'stato' => 0, 'qta_fase' => $o->qta_richiesta, 'um' => 'FG'],
            ['fase' => 'BRT1', 'fase_catalogo_id' => $brt1->id, 'stato' => 0, 'qta_fase' => $o->qta_richiesta, 'um' => 'FG', 'priorita' => 96],
        ]);
        echo "Creato ordine {$o->cod_art} con 3 fasi (FIN01, PI01, BRT1)\n";
    } else {
        echo "Ordine {$o->cod_art} gia' presente con " . $o->fasi()->count() . " fasi\n";
    }
}

echo "\nFatto.\n";
