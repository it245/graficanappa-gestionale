<?php
// Uso: php check_fase.php 0066758-26       (cerca per commessa)
//      php check_fase.php 7786              (cerca per ID fase)

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$arg = $argv[1] ?? '0066758-26';

if (strpos($arg, '-') !== false) {
    $commessa = $arg;
    echo "=== Fasi STAMPA per commessa $commessa ===\n\n";

    $fasi = \App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->where('fase', 'LIKE', 'STAMPA%')
        ->with(['ordine', 'operatori'])
        ->get();

    if ($fasi->isEmpty()) {
        echo "Nessuna fase STAMPA trovata\n";
    }

    foreach ($fasi as $f) {
        echo "Fase: {$f->fase} | Stato: {$f->stato}\n";
        echo "  Fogli buoni: {$f->fogli_buoni} | Qta prod: {$f->qta_prod} | Scarti: {$f->fogli_scarto}\n";
        echo "  Data inizio: {$f->data_inizio} | Data fine: {$f->data_fine}\n";
        echo "  Avviamento: {$f->tempo_avviamento_sec}s | Esecuzione: {$f->tempo_esecuzione_sec}s\n";
        foreach ($f->operatori as $op) {
            echo "  Operatore: {$op->nome} - inizio: {$op->pivot->data_inizio} fine: {$op->pivot->data_fine}\n";
        }
        echo "\n";
    }

    echo "=== Tutte le fasi della commessa ===\n";
    $tutte = \App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->with('faseCatalogo')
        ->orderBy('priorita')
        ->get();
    foreach ($tutte as $f) {
        $nome = $f->faseCatalogo->nome ?? $f->fase;
        echo sprintf("  id=%-6d stato=%d prio=%-8s fase=%-25s qta_prod=%-5s fine=%s\n",
            $f->id, $f->stato, $f->priorita ?? '-', $nome, $f->qta_prod ?? '-', $f->data_fine ?? '-');
    }
} else {
    $faseId = (int)$arg;
    $fase = \App\Models\OrdineFase::with(['ordine', 'faseCatalogo', 'operatori'])->find($faseId);

    if (!$fase) {
        echo "Fase $faseId non trovata\n";
        exit(1);
    }

    echo "=== Fase id=$faseId ===\n";
    echo "Commessa: " . ($fase->ordine->commessa ?? '?') . "\n";
    echo "Fase: " . ($fase->faseCatalogo->nome ?? $fase->fase) . "\n";
    echo "Stato: {$fase->stato}\n";
    echo "Qta fase: {$fase->qta_fase} | Qta prodotta: {$fase->qta_prod}\n";
    echo "Data inizio: {$fase->data_inizio}\n";
    echo "Data fine: {$fase->data_fine}\n";
    echo "Operatori:\n";
    foreach ($fase->operatori as $op) {
        echo "  {$op->nome} {$op->cognome} - inizio: {$op->pivot->data_inizio} fine: {$op->pivot->data_fine} pausa: {$op->pivot->secondi_pausa}s\n";
    }

    echo "\n=== Tutte le fasi della commessa ===\n";
    $tutteFasi = \App\Models\OrdineFase::where('ordine_id', $fase->ordine_id)
        ->with('faseCatalogo')
        ->orderBy('priorita')
        ->get();
    foreach ($tutteFasi as $f) {
        $nome = $f->faseCatalogo->nome ?? $f->fase;
        echo sprintf("  id=%-6d stato=%d prio=%-6s fase=%-25s qta_prod=%-5s data_fine=%s\n",
            $f->id, $f->stato, $f->priorita, $nome, $f->qta_prod ?? '-', $f->data_fine ?? '-');
    }
}
