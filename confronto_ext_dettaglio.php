<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CONFRONTO DETTAGLIATO: FASI EXT nel MES vs ONDA ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le commesse con fasi EXT attive nel MES
$fasiExt = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordine_fasi.stato', '<', 4)
    ->where('ordine_fasi.esterno', 1)
    ->select('ordini.commessa', 'ordine_fasi.fase', 'ordine_fasi.id as fase_id', 'ordine_fasi.stato')
    ->orderBy('ordini.commessa')
    ->get()
    ->groupBy('commessa');

foreach ($fasiExt as $commessa => $fasiMes) {
    // Fasi Onda per questa commessa
    $fasiOnda = DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase, f.CodMacchina, f.QtaDaLavorare
        FROM PRDDocFasi f
        JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY f.CodFase
    ", [$commessa]);

    // Righe ATTDocRighe
    $righeAtt = DB::connection('onda')->select("
        SELECT r.CodArt, r.Qta
        FROM ATTDocRighe r
        JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
        WHERE t.CodCommessa = ?
        ORDER BY r.NrRiga
    ", [$commessa]);

    $fasiOndaNomi = array_map(fn($f) => $f->CodFase, $fasiOnda);
    $righeAttNomi = array_map(fn($r) => $r->CodArt, $righeAtt);

    echo "=== {$commessa} ===" . PHP_EOL;
    echo "  MES fasi EXT (" . $fasiMes->count() . "):" . PHP_EOL;
    foreach ($fasiMes as $f) {
        $inOnda = in_array($f->fase, $fasiOndaNomi) ? 'OK_ONDA' :
                  (in_array('EXT' . $f->fase, $fasiOndaNomi) ? 'OK_ONDA(EXT)' : 'NO_ONDA');
        $inRighe = in_array($f->fase, $righeAttNomi) ? 'OK_RIGHE' :
                   (in_array('EXT' . $f->fase, $righeAttNomi) ? 'OK_RIGHE(EXT)' : 'NO_RIGHE');
        echo "    {$f->fase} | stato:{$f->stato} | {$inOnda} | {$inRighe}" . PHP_EOL;
    }

    echo "  Onda PRDDocFasi:" . PHP_EOL;
    foreach ($fasiOnda as $fo) {
        echo "    {$fo->CodFase} | qta:{$fo->QtaDaLavorare}" . PHP_EOL;
    }

    echo "  Onda ATTDocRighe (solo fasi, no materiali):" . PHP_EOL;
    foreach ($righeAtt as $ra) {
        // Mostra solo righe che sembrano fasi (non materiali)
        if (preg_match('/^(EXT|BRT|STAMPA|PLA|FUST|PI0|FIN|TAGLIO|PERF|BROSS|ALLEST|ACCOPP|ARROT|UVSPOT|FOIL|PMDUPLO|SPIRC|CARTONATO|PUNT)/i', $ra->CodArt)) {
            echo "    {$ra->CodArt} | qta:{$ra->Qta}" . PHP_EOL;
        }
    }
    echo PHP_EOL;
}
