<?php
/**
 * Importa una singola commessa da Onda (anche se precedente alla data di sync).
 * Uso: php import_commessa_onda.php 0066363
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use App\Services\OndaSyncService;
use App\Services\FaseStatoService;
use Illuminate\Support\Facades\DB;

$codCommessa = $argv[1] ?? null;
if (!$codCommessa) {
    echo "Uso: php import_commessa_onda.php <CodCommessa>\n";
    echo "Esempio: php import_commessa_onda.php 0066363\n";
    exit(1);
}

echo "Importazione commessa {$codCommessa} da Onda...\n\n";

// Pre-fetch scarti previsti
$scartiMacchine = collect(DB::connection('onda')->select(
    "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
))->pluck('OC_FogliScartoIniz', 'CodMacchina')->toArray();

// Query Onda per la commessa specifica (senza filtro data)
$righeOnda = DB::connection('onda')->select("
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
      AND t.CodCommessa LIKE ?
", ["%{$codCommessa}%"]);

if (empty($righeOnda)) {
    echo "Commessa {$codCommessa} non trovata su Onda!\n";
    exit(1);
}

echo "Trovate " . count($righeOnda) . " righe su Onda\n";

$mappaReparti = OndaSyncService::getMappaReparti();
$tipiFase = OndaSyncService::getTipoReparto();
$mappaPriorita = config('fasi_priorita');
$oggi = now()->format('Y-m-d');

$ordiniCreati = 0;
$fasiCreate = 0;

// Raggruppa per (CodCommessa, CodArt, OC_Descrizione)
$gruppi = collect($righeOnda)->groupBy(function ($riga) {
    $desc = preg_replace('/\s+/', ' ', trim($riga->OC_Descrizione ?? ''));
    return $riga->CodCommessa . '|' . $riga->CodArt . '|' . $desc;
});

foreach ($gruppi as $chiave => $righe) {
    $prima = $righe->first();
    $commessa = trim($prima->CodCommessa ?? '');
    $codArt = trim($prima->CodArt ?? '');
    $descrizione = preg_replace('/\s+/', ' ', trim($prima->OC_Descrizione ?? ''));

    if (!$commessa) continue;

    // Upsert ordine
    $ordine = Ordine::where('commessa', $commessa)
        ->where('cod_art', $codArt)
        ->where('descrizione', $descrizione)
        ->first();

    if (!$ordine && $descrizione === '') {
        $ordine = Ordine::where('commessa', $commessa)
            ->where('cod_art', $codArt)
            ->first();
    }

    $datiOrdine = [
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
    ];

    if ($ordine) {
        $ordine->update($datiOrdine);
        echo "Ordine aggiornato: {$commessa} ({$codArt})\n";
    } else {
        $ordine = Ordine::create(array_merge([
            'commessa'    => $commessa,
            'cod_art'     => $codArt,
            'descrizione' => $descrizione,
            'stato'       => 0,
            'data_registrazione' => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : $oggi,
        ], $datiOrdine));
        $ordiniCreati++;
        echo "Ordine CREATO: {$commessa} ({$codArt}) - {$datiOrdine['cliente_nome']}\n";
    }

    // BRT1
    $hasBrt = OrdineFase::where(function ($q) {
            $q->where('fase', 'BRT1')->orWhere('fase', 'brt1');
        })
        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->exists();

    if (!$hasBrt) {
        $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
        $faseCatalogoBrt = FasiCatalogo::firstOrCreate(
            ['nome' => 'BRT1'],
            ['reparto_id' => $repartoBrt->id]
        );
        OrdineFase::create([
            'ordine_id'        => $ordine->id,
            'fase'             => 'BRT1',
            'fase_catalogo_id' => $faseCatalogoBrt->id,
            'qta_fase'         => $ordine->qta_richiesta ?? 0,
            'um'               => 'FG',
            'priorita'         => $mappaPriorita['BRT1'] ?? 96,
            'stato'            => 0,
        ]);
        $fasiCreate++;
        echo "  + BRT1 (auto)\n";
    }

    // Fasi
    $fasiViste = [];
    foreach ($righe as $riga) {
        $faseNome = trim($riga->CodFase ?? '');
        if (!$faseNome) continue;

        // Rimappa STAMPA generico
        if ($faseNome === 'STAMPA') {
            $macchina = trim($riga->CodMacchina ?? '');
            if (stripos($macchina, 'INDIGO') !== false) {
                $faseNome = (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false) ? 'STAMPAINDIGOBN' : 'STAMPAINDIGO';
            } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                $faseNome = 'STAMPAXL106.' . $m[1];
            } else {
                $faseNome = 'STAMPAXL106';
            }
        }

        $chiaveFase = $faseNome . '|' . ($riga->QtaDaLavorare ?? 0);
        if (isset($fasiViste[$chiaveFase])) continue;
        $fasiViste[$chiaveFase] = true;

        $repartoNome = $mappaReparti[$faseNome] ?? 'generico';
        $prioritaFase = $mappaPriorita[$faseNome] ?? 500;

        $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
        $faseCatalogo = FasiCatalogo::updateOrCreate(
            ['nome' => $faseNome],
            ['reparto_id' => $reparto->id]
        );

        // Check se esiste già
        $exists = OrdineFase::where('ordine_id', $ordine->id)
            ->where('fase_catalogo_id', $faseCatalogo->id)
            ->exists();

        if (!$exists) {
            OrdineFase::create([
                'ordine_id'        => $ordine->id,
                'fase'             => $faseNome,
                'fase_catalogo_id' => $faseCatalogo->id,
                'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                'um'               => trim($riga->UMFase ?? 'FG'),
                'priorita'         => $prioritaFase,
                'stato'            => 0,
                'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
            ]);
            $fasiCreate++;
            echo "  + {$faseNome} ({$repartoNome})\n";
        } else {
            echo "  = {$faseNome} (esiste già)\n";
        }
    }
}

// Ricalcola priorità e stati
echo "\nRicalcolo priorità e stati...\n";
$controller = app(\App\Http\Controllers\DashboardOwnerController::class);
$fasiCommessa = OrdineFase::with('ordine')
    ->whereHas('ordine', fn($q) => $q->where('commessa', 'like', "%{$codCommessa}%"))
    ->get();

foreach ($fasiCommessa as $fase) {
    $controller->calcolaOreEPriorita($fase);
    $fase->save();
}

FaseStatoService::ricalcolaTutti();

echo "\nDone! Ordini creati: {$ordiniCreati}, Fasi create: {$fasiCreate}\n";
