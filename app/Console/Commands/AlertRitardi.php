<?php

namespace App\Console\Commands;

use App\Models\OrdineFase;
use App\Mail\AlertRitardi as AlertRitardiMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class AlertRitardi extends Command
{
    protected $signature = 'ritardi:alert {--dry-run : Solo stampa, non invia email} {--to= : Destinatari separati da virgola}';
    protected $description = 'Invia alert email con commesse critiche (scadute o entro 2gg) non ancora avviate';

    public function handle()
    {
        $this->info('Controllo commesse a rischio...');

        // Fasi con scadenza entro 2gg, non ancora terminate, non avviate in pronto/caricato
        $oggi = Carbon::today();
        $limiteCritico = $oggi->copy()->addDays(2);

        $fasiRischio = OrdineFase::with(['ordine:id,commessa,cliente_nome,descrizione,data_prevista_consegna,qta_richiesta', 'faseCatalogo:id,nome,reparto_id', 'faseCatalogo.reparto:id,nome'])
            ->whereHas('ordine', function ($q) use ($limiteCritico) {
                $q->whereNotNull('data_prevista_consegna')
                  ->whereDate('data_prevista_consegna', '<=', $limiteCritico);
            })
            ->whereIn('stato', ['0', '1', '2'])
            ->whereRaw('CAST(stato AS UNSIGNED) < 3')
            ->get()
            ->filter(fn($f) => $f->ordine && $f->ordine->data_prevista_consegna);

        $critiche = [];
        $scadute = [];

        foreach ($fasiRischio as $fase) {
            $consegna = Carbon::parse($fase->ordine->data_prevista_consegna);
            $gg = $consegna->startOfDay()->diffInDays($oggi, false);

            $item = [
                'commessa' => $fase->ordine->commessa,
                'cliente' => $fase->ordine->cliente_nome,
                'descrizione' => \Illuminate\Support\Str::limit($fase->ordine->descrizione, 60),
                'fase' => $fase->fase,
                'reparto' => $fase->faseCatalogo->reparto->nome ?? '-',
                'stato' => $fase->stato,
                'consegna' => $consegna->format('d/m/Y'),
                'gg' => $gg,
            ];

            if ($gg < 0) {
                $scadute[] = $item;
            } else {
                $critiche[] = $item;
            }
        }

        $tot = count($scadute) + count($critiche);
        $this->info("Trovate: {$tot} fasi a rischio ({" . count($scadute) . "} scadute, " . count($critiche) . " critiche ≤2gg)");

        if ($tot === 0) {
            $this->info('Nessun alert da inviare.');
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->line("\n=== SCADUTE ===");
            foreach ($scadute as $s) $this->line("  {$s['commessa']} — {$s['cliente']} — {$s['fase']} ({$s['gg']}gg)");
            $this->line("\n=== CRITICHE ===");
            foreach ($critiche as $c) $this->line("  {$c['commessa']} — {$c['cliente']} — {$c['fase']} ({$c['gg']}gg)");
            $this->info("\n[DRY-RUN] Nessuna email inviata.");
            return 0;
        }

        $destinatari = $this->option('to') ?: env('ALERT_RITARDI_TO', 'anappa@graficanappa.com');
        $toList = array_map('trim', explode(',', $destinatari));

        try {
            Mail::to($toList)->send(new AlertRitardiMail($scadute, $critiche));
            $this->info("Email alert inviata a: " . implode(', ', $toList));
        } catch (\Throwable $e) {
            $this->error("Errore invio email: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
