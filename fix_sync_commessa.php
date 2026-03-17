<?php
// Uso: php fix_sync_commessa.php [numero_commessa]
// Forza il ricalcolo fogli Prinect per una commessa specifica
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '66648';
$commessa = '00' . $cerca . '-26';

echo "Forzando sync Prinect per commessa $commessa...\n";

// Conta attività Prinect per questa commessa
$attivita = DB::table('prinect_attivita')->where('commessa_gestionale', $commessa)->count();
$buoni = DB::table('prinect_attivita')->where('commessa_gestionale', $commessa)->sum('good_cycles');
echo "Attivita Prinect in DB: $attivita (fogli buoni totali: $buoni)\n";

if ($attivita == 0) {
    echo "Nessuna attivita Prinect trovata per $commessa. Niente da fare.\n";
    exit;
}

// Chiama aggiornaFogliCommessa (metodo protected, usiamo reflection)
$syncService = app('App\Http\Services\PrinectSyncService');
$method = new ReflectionMethod($syncService, 'aggiornaFogliCommessa');
$method->setAccessible(true);
$method->invoke($syncService, $commessa);

echo "Sync completato. Verifico fasi:\n";

$fasi = App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->where(function($q) {
        $q->where('fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('fase', 'STAMPA')
          ->orWhere('fase', 'LIKE', 'STAMPA XL%');
    })
    ->get();

foreach ($fasi as $f) {
    $stati = ['Non iniziata', 'Pronto', 'Avviato', 'Terminato', 'Consegnato'];
    $statoLabel = isset($stati[$f->stato]) ? $stati[$f->stato] : '?';
    echo "  {$f->fase} | Stato: {$f->stato} ({$statoLabel}) | Fogli buoni: {$f->fogli_buoni} | Qta prod: {$f->qta_prod}\n";
}
