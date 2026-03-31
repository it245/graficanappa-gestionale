<?php
// Statistiche reali del MES per la roadmap
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STATISTICHE MES GRAFICA NAPPA ===\n";
echo "Data: " . now()->format('d/m/Y H:i') . "\n\n";

// Commesse
$totCommesse = DB::table('ordini')->distinct()->count('commessa');
$commesseAttive = DB::table('ordini')
    ->whereExists(fn($q) => $q->select(DB::raw(1))->from('ordine_fasi')
        ->whereColumn('ordine_fasi.ordine_id', 'ordini.id')
        ->whereNull('ordine_fasi.deleted_at')
        ->whereRaw("ordine_fasi.stato REGEXP '^[0-9]+$' AND ordine_fasi.stato < 4"))
    ->distinct()->count('commessa');
$commesseCompletate = $totCommesse - $commesseAttive;

echo "COMMESSE:\n";
echo "  Totali gestite: {$totCommesse}\n";
echo "  Attive: {$commesseAttive}\n";
echo "  Completate: {$commesseCompletate}\n\n";

// Fasi
$totFasi = DB::table('ordine_fasi')->whereNull('deleted_at')->count();
$fasiCompletate = DB::table('ordine_fasi')->whereNull('deleted_at')->whereIn('stato', [3, 4])->count();
$fasiAttive = DB::table('ordine_fasi')->whereNull('deleted_at')->where('stato', 2)->count();

echo "FASI DI PRODUZIONE:\n";
echo "  Totali tracciate: {$totFasi}\n";
echo "  Completate (stato 3+4): {$fasiCompletate}\n";
echo "  In lavorazione ora: {$fasiAttive}\n\n";

// Consegne
$consegne = DB::table('ordine_fasi')->whereNull('deleted_at')->where('stato', 4)->count();
echo "CONSEGNE:\n";
echo "  Fasi consegnate (stato 4): {$consegne}\n\n";

// Operatori
$operatori = DB::table('operatori')->where('attivo', 1)->count();
echo "OPERATORI:\n";
echo "  Attivi: {$operatori}\n\n";

// Clienti
$clienti = DB::table('ordini')->distinct()->count('cliente_nome');
echo "CLIENTI:\n";
echo "  Gestiti: {$clienti}\n\n";

// Prinect
$prinectAttivita = DB::table('prinect_attivita')->count();
$fogliStampati = DB::table('ordine_fasi')->whereNull('deleted_at')
    ->where('fase', 'LIKE', 'STAMPAXL%')
    ->sum('fogli_buoni');
echo "STAMPA OFFSET (PRINECT):\n";
echo "  Attivita Prinect tracciate: {$prinectAttivita}\n";
echo "  Fogli buoni totali: " . number_format($fogliStampati, 0, ',', '.') . "\n\n";

// Presenze
$timbrature = DB::table('nettime_timbrature')->count();
$dipendenti = DB::table('nettime_anagrafica')->count();
echo "PRESENZE (NETTIME):\n";
echo "  Timbrature registrate: " . number_format($timbrature, 0, ',', '.') . "\n";
echo "  Dipendenti in anagrafica: {$dipendenti}\n\n";

// Audit
$auditCount = DB::table('audit_logs')->count();
echo "AUDIT LOG:\n";
echo "  Eventi registrati: {$auditCount}\n\n";

// Spedizioni BRT
$spedizioniBrt = DB::table('ddt_spedizioni')->where('vettore', 'LIKE', '%BRT%')->count();
echo "SPEDIZIONI BRT:\n";
echo "  DDT tracciati: {$spedizioniBrt}\n\n";

// Date
$primaCommessa = DB::table('ordini')->min('created_at');
$ultimaCommessa = DB::table('ordini')->max('created_at');
echo "PERIODO:\n";
echo "  Prima commessa: " . ($primaCommessa ? \Carbon\Carbon::parse($primaCommessa)->format('d/m/Y') : '-') . "\n";
echo "  Ultima commessa: " . ($ultimaCommessa ? \Carbon\Carbon::parse($ultimaCommessa)->format('d/m/Y') : '-') . "\n\n";

// Reparti
$reparti = DB::table('reparti')->count();
$fasiCatalogo = DB::table('fasi_catalogo')->count();
echo "CONFIGURAZIONE:\n";
echo "  Reparti: {$reparti}\n";
echo "  Fasi catalogo: {$fasiCatalogo}\n";
