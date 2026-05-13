<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;
use App\Models\PrinectAttivita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "\n=== DRY-RUN: Fasi che verrebbero auto-terminate ===\n\n";

$fasi = OrdineFase::with('ordine')
    ->where('fogli_buoni', '>=', 0)
    ->where('stato', '2')
    ->where(function ($q) {
        $q->where('fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('fase', 'STAMPA')
          ->orWhere('fase', 'LIKE', 'STAMPA XL%');
    })
    ->get();

echo "Totale fasi attive STAMPA: " . count($fasi) . "\n\n";

foreach ($fasi as $fase) {
    $commessa = $fase->ordine->commessa ?? '';
    if (!$commessa) continue;

    $ultimaAttivita = PrinectAttivita::where('commessa_gestionale', $commessa)
        ->orderByDesc('start_time')
        ->first();

    if (!$ultimaAttivita || !$ultimaAttivita->start_time) {
        echo "$commessa fase {$fase->fase} -> SKIP (no attività Prinect)\n";
        continue;
    }

    $ultimoTempo = Carbon::parse($ultimaAttivita->end_time ?? $ultimaAttivita->start_time);
    $orePassate = $ultimoTempo->diffInHours(now());
    $giornoAttivita = $ultimoTempo->toDateString();
    $oggi = Carbon::today()->toDateString();

    $abbandonata = false;
    $motivo = '';

    if ($orePassate >= 4 && $giornoAttivita !== $oggi) {
        $abbandonata = true;
        $motivo = ">4h + giorno diverso";
    }

    if (!$abbandonata && $giornoAttivita === $oggi) {
        // Prima e ultima attività di oggi sulla macchina
        $primaAttOggi = PrinectAttivita::where('device_id', $ultimaAttivita->device_id)
            ->whereDate('start_time', $oggi)
            ->orderBy('start_time')
            ->first();
        $ultimaAttOggi = PrinectAttivita::where('device_id', $ultimaAttivita->device_id)
            ->whereDate('start_time', $oggi)
            ->orderByDesc('start_time')
            ->first();
        // Caso: oggi macchina ha iniziato con questa commessa MA dopo e' passata ad altre
        if ($primaAttOggi && $ultimaAttOggi
            && $primaAttOggi->commessa_gestionale === $commessa
            && $ultimaAttOggi->commessa_gestionale !== $commessa) {
            $abbandonata = true;
            $motivo = "primo avviamento mattutino (poi operatore passato ad altre commesse)";
        }
    }

    if ($abbandonata) {
        $aggr = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->selectRaw('SUM(good_cycles) as buoni, SUM(waste_cycles) as scarto')
            ->first();
        $buoni = (int)($aggr->buoni ?? 0);
        $scarto = (int)($aggr->scarto ?? 0);

        echo sprintf("✓ TERMINEREBBE: %s fase=%s ultima_att=%s (%s)\n",
            $commessa, $fase->fase, $ultimoTempo->format('d/m H:i'), $motivo);
        echo sprintf("    Fogli da popolare: buoni=%d scarto=%d (qta_prod %d→%d)\n",
            $buoni, $scarto, $fase->qta_prod, $buoni);
    }
}

echo "\nDRY-RUN: niente scritto.\n";
