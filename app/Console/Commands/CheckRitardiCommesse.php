<?php

namespace App\Console\Commands;

use App\Models\Ordine;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckRitardiCommesse extends Command
{
    protected $signature = 'commesse:check-ritardi';
    protected $description = 'Controlla commesse in ritardo o a rischio e invia notifiche push all\'owner';

    public function handle(): int
    {
        $oggi = Carbon::today();
        $notifiche = [];

        // Commesse con fasi attive (stato < 3) raggruppate per commessa
        $commesse = Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
            ->whereNotNull('data_prevista_consegna')
            ->select('commessa', 'cliente_nome', 'data_prevista_consegna', 'descrizione')
            ->groupBy('commessa', 'cliente_nome', 'data_prevista_consegna', 'descrizione')
            ->get()
            ->groupBy('commessa');

        foreach ($commesse as $numCommessa => $ordini) {
            $primo = $ordini->first();
            $dataConsegna = Carbon::parse($primo->data_prevista_consegna)->startOfDay();
            $diffGiorni = $oggi->diffInDays($dataConsegna, false);
            $cliente = $primo->cliente_nome ?? 'N/D';

            // Scaduta (consegna passata)
            if ($diffGiorni < 0) {
                $notifiche[] = [
                    'title' => "SCADUTA: {$numCommessa}",
                    'body' => "{$cliente} — scaduta da " . abs($diffGiorni) . " giorni",
                    'tag' => "ritardo-{$numCommessa}",
                    'url' => "/owner/commessa/{$numCommessa}",
                    'requireInteraction' => true,
                ];
            }
            // A rischio (consegna domani o oggi)
            elseif ($diffGiorni <= 1) {
                $quando = $diffGiorni === 0 ? 'OGGI' : 'DOMANI';
                $notifiche[] = [
                    'title' => "Consegna {$quando}: {$numCommessa}",
                    'body' => "{$cliente}",
                    'tag' => "urgente-{$numCommessa}",
                    'url' => "/owner/commessa/{$numCommessa}",
                ];
            }
        }

        if (empty($notifiche)) {
            $this->info('Nessun ritardo critico trovato.');
            return 0;
        }

        $this->info("Trovate " . count($notifiche) . " commesse critiche. Invio notifiche...");

        foreach ($notifiche as $n) {
            WebPushService::notificaPerRuolo('owner', $n);
            WebPushService::notificaPerRuolo('owner_readonly', $n);
        }

        $this->info('Notifiche inviate.');
        return 0;
    }
}
