<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use Illuminate\Support\Facades\DB;
use App\Services\FaseStatoService;

// 1. Elimina dati sporchi della 66394
$ordini = Ordine::where('commessa', '0066394-26')->get();
foreach ($ordini as $o) {
    $fasi = OrdineFase::where('ordine_id', $o->id)->get();
    foreach ($fasi as $f) {
        $f->operatori()->detach();
        $f->delete();
    }
    $o->delete();
    echo "Eliminato ordine #{$o->id}: {$o->descrizione}\n";
}
echo "Eliminati " . count($ordini) . " ordini\n\n";

// 2. Importa SOLO la commessa 66394 da Onda
$righe = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        p.OC_Descrizione,
        COALESCE(NULLIF(p.NCPRagioneSociale, ''), a.RagioneSociale) AS ClienteNome,
        p.QtaDaProdurre,
        p.DataPresConsegna,
        t.DataRegistrazione,
        carta.CodArt AS CodCarta,
        carta.Descrizione AS DescrizioneCarta,
        carta.Qta AS QtaCarta,
        carta.CodUnMis AS UMCarta,
        t.TotMerce,
        t.ncpcommentoprestampa AS NotePrestampa,
        t.ncprespocommessa AS Responsabile,
        t.OC_CommentoProduz AS CommentoProduzione,
        t.ncpordinecliente AS OrdineCliente,
        materiali.CostoMateriali,
        f.CodFase,
        f.CodMacchina,
        f.QtaDaLavorare,
        f.CodUnMis AS UMFase
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    OUTER APPLY (
        SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
        FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
        ORDER BY r.Sequenza
    ) carta
    OUTER APPLY (
        SELECT SUM(r2.Totale) AS CostoMateriali
        FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
    ) materiali
    WHERE t.TipoDocumento = '2'
      AND t.CodCommessa = '0066394-26'
");

if (empty($righe)) {
    echo "Nessun dato trovato in Onda per 0066394-26\n";
    exit;
}

echo "Trovate " . count($righe) . " righe in Onda\n\n";

$mappaReparti = \App\Services\OndaSyncService::getMappaReparti();
$mappaPriorita = config('fasi_priorita');

$scartiMacchine = collect(DB::connection('onda')->select(
    "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
))->pluck('OC_FogliScartoIniz', 'CodMacchina')->toArray();

$gruppi = collect($righe)->groupBy(function ($riga) {
    return $riga->CodCommessa . '|' . $riga->CodArt . '|' . $riga->OC_Descrizione;
});

foreach ($gruppi as $chiave => $righeGruppo) {
    $prima = $righeGruppo->first();
    $commessa = trim($prima->CodCommessa);
    $codArt = trim($prima->CodArt ?? '');
    $descrizione = trim($prima->OC_Descrizione ?? '');

    echo "Creo ordine: $commessa | $codArt | $descrizione\n";

    $ordine = Ordine::create([
        'commessa'               => $commessa,
        'cod_art'                => $codArt,
        'descrizione'            => $descrizione,
        'stato'                  => 0,
        'data_registrazione'     => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : now()->toDateString(),
        'cliente_nome'           => trim($prima->ClienteNome ?? ''),
        'data_prevista_consegna' => $prima->DataPresConsegna && date('Y', strtotime($prima->DataPresConsegna)) >= 2024 ? date('Y-m-d', strtotime($prima->DataPresConsegna)) : null,
        'qta_richiesta'          => $prima->QtaDaProdurre ?? 0,
        'cod_carta'              => trim($prima->CodCarta ?? ''),
        'carta'                  => trim($prima->DescrizioneCarta ?? ''),
        'qta_carta'              => $prima->QtaCarta ?? 0,
        'UM_carta'               => trim($prima->UMCarta ?? ''),
        'note_prestampa'         => trim($prima->NotePrestampa ?? ''),
        'responsabile'           => trim($prima->Responsabile ?? ''),
        'commento_produzione'    => trim($prima->CommentoProduzione ?? ''),
        'ordine_cliente'         => trim($prima->OrdineCliente ?? '') ?: null,
        'valore_ordine'          => ($prima->TotMerce ?? 0) > 0 ? (float) $prima->TotMerce : null,
        'costo_materiali'        => ($prima->CostoMateriali ?? 0) > 0 ? (float) $prima->CostoMateriali : null,
    ]);

    // BRT1
    $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
    $faseCatalogoBrt = FasiCatalogo::firstOrCreate(['nome' => 'BRT1'], ['reparto_id' => $repartoBrt->id]);
    OrdineFase::create([
        'ordine_id' => $ordine->id, 'fase' => 'BRT1', 'fase_catalogo_id' => $faseCatalogoBrt->id,
        'qta_fase' => $ordine->qta_richiesta ?? 0, 'um' => 'FG', 'priorita' => $mappaPriorita['BRT1'] ?? 96, 'stato' => 0,
    ]);

    // Fasi
    $fasiViste = [];
    foreach ($righeGruppo as $riga) {
        $faseNome = trim($riga->CodFase ?? '');
        if (!$faseNome) continue;

        if ($faseNome === 'STAMPA') {
            $macchina = trim($riga->CodMacchina ?? '');
            if (stripos($macchina, 'INDIGO') !== false) {
                $faseNome = stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false ? 'STAMPAINDIGOBN' : 'STAMPAINDIGO';
            } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                $faseNome = 'STAMPAXL106.' . $m[1];
            } elseif (stripos($macchina, 'XL106') !== false) {
                $faseNome = 'STAMPAXL106';
            }
        }

        $chiaveFase = $faseNome . '|' . ($riga->QtaDaLavorare ?? 0);
        if (isset($fasiViste[$chiaveFase])) continue;
        $fasiViste[$chiaveFase] = true;

        $repartoNome = $mappaReparti[$faseNome] ?? 'generico';
        $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
        $faseCatalogo = FasiCatalogo::firstOrCreate(['nome' => $faseNome], ['reparto_id' => $reparto->id]);

        OrdineFase::create([
            'ordine_id'        => $ordine->id,
            'fase'             => $faseNome,
            'fase_catalogo_id' => $faseCatalogo->id,
            'qta_fase'         => $riga->QtaDaLavorare ?? 0,
            'um'               => trim($riga->UMFase ?? 'FG'),
            'priorita'         => $mappaPriorita[$faseNome] ?? 500,
            'stato'            => 0,
            'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
        ]);

        echo "  → $faseNome (qta=" . ($riga->QtaDaLavorare ?? 0) . ")\n";
    }

    FaseStatoService::ricalcolaStati($ordine->id);
}

echo "\nFatto!\n";
