<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\FieryService;

class FieryReport extends Command
{
    protected $signature = 'fiery:report
        {--da= : Data inizio (Y-m-d, es. 2026-01-13)}
        {--a= : Data fine (Y-m-d, es. 2026-02-28)}
        {--commessa= : Filtra per commessa specifica}
        {--date-range : Mostra range date disponibili}';

    protected $description = 'Report storico stampe dal Fiery Accounting API';

    public function handle()
    {
        $fiery = app(FieryService::class);

        $this->info("Recupero dati Accounting dal Fiery...\n");

        $json = $this->getAccountingRaw($fiery);
        if (!$json) {
            $this->error('Impossibile recuperare dati Accounting.');
            return 1;
        }

        $items = $json['data']['items'] ?? $json;
        if (!is_array($items) || count($items) === 0) {
            $this->error('Nessun dato Accounting trovato.');
            return 1;
        }

        $this->info("Totale entries nell'Accounting: " . count($items));

        // Mostra range date
        if ($this->option('date-range')) {
            $this->mostraRangeDate($items);
            return 0;
        }

        $da = $this->option('da');
        $a = $this->option('a');
        $filtroCommessa = $this->option('commessa');

        // Filtra per date se specificate
        $filtrati = collect($items)->map(function ($entry) use ($fiery) {
            $title = $entry['title'] ?? '';
            $commessa = $fiery->estraiCommessaDaTitolo($title);

            // Parsa la data — il formato Fiery varia, proviamo i più comuni
            $dateStr = $entry['date'] ?? $entry['print date'] ?? '';
            $date = $this->parseDate($dateStr);

            return [
                'title' => $title,
                'commessa' => $commessa,
                'date' => $date,
                'date_raw' => $dateStr,
                'fogli' => (int) ($entry['total sheets printed'] ?? 0),
                'copie' => (int) ($entry['copies printed'] ?? $entry['num copies'] ?? 0),
                'pagine_colore' => (int) ($entry['total color pages printed'] ?? 0),
                'pagine_bn' => (int) ($entry['total bw pages printed'] ?? 0),
                'media_size' => $entry['media size'] ?? '',
                'state' => $entry['state'] ?? $entry['print status'] ?? '',
            ];
        });

        // Filtra per data
        if ($da) {
            $daDate = strtotime($da);
            $filtrati = $filtrati->filter(fn($e) => $e['date'] && $e['date'] >= $daDate);
        }
        if ($a) {
            $aDate = strtotime($a . ' 23:59:59');
            $filtrati = $filtrati->filter(fn($e) => $e['date'] && $e['date'] <= $aDate);
        }
        if ($filtroCommessa) {
            $filtrati = $filtrati->filter(fn($e) =>
                $e['commessa'] && stripos($e['commessa'], $filtroCommessa) !== false
            );
        }

        if ($filtrati->isEmpty()) {
            $this->warn('Nessun job trovato con i filtri specificati.');
            return 0;
        }

        // Riepilogo totale
        $totFogli = $filtrati->sum('fogli');
        $totCopie = $filtrati->sum('copie');
        $totColore = $filtrati->sum('pagine_colore');
        $totBN = $filtrati->sum('pagine_bn');
        $numJobs = $filtrati->count();

        $periodo = '';
        if ($da) $periodo .= "dal $da ";
        if ($a) $periodo .= "al $a";
        if (!$periodo) $periodo = 'tutti i dati';

        $this->info("\n========================================");
        $this->info(" RIEPILOGO STAMPE — {$periodo}");
        $this->info("========================================");
        $this->line("  Job totali:        {$numJobs}");
        $this->line("  Fogli totali:      " . number_format($totFogli, 0, ',', '.'));
        $this->line("  Copie totali:      " . number_format($totCopie, 0, ',', '.'));
        $this->line("  Pagine colore:     " . number_format($totColore, 0, ',', '.'));
        $this->line("  Pagine B/N:        " . number_format($totBN, 0, ',', '.'));

        // Raggruppa per commessa
        $perCommessa = $filtrati
            ->filter(fn($e) => $e['commessa'])
            ->groupBy('commessa')
            ->map(fn($group) => [
                'commessa' => $group->first()['commessa'],
                'fogli' => $group->sum('fogli'),
                'copie' => $group->sum('copie'),
                'colore' => $group->sum('pagine_colore'),
                'bn' => $group->sum('pagine_bn'),
                'jobs' => $group->count(),
                'formati' => $group->pluck('media_size')->filter()->unique()->values()->toArray(),
                'titoli' => $group->pluck('title')->unique()->values()->toArray(),
            ])
            ->sortByDesc('fogli');

        $this->info("\n--- Dettaglio per commessa ---\n");
        $this->line(
            str_pad('COMMESSA', 16) .
            str_pad('FOGLI', 10) .
            str_pad('COPIE', 10) .
            str_pad('COLORE', 10) .
            str_pad('B/N', 10) .
            str_pad('RUN', 6) .
            'FORMATO'
        );
        $this->line(str_repeat('-', 90));

        foreach ($perCommessa as $c) {
            $formati = implode(', ', $c['formati']);
            $this->line(
                str_pad($c['commessa'], 16) .
                str_pad(number_format($c['fogli'], 0, ',', '.'), 10) .
                str_pad(number_format($c['copie'], 0, ',', '.'), 10) .
                str_pad(number_format($c['colore'], 0, ',', '.'), 10) .
                str_pad(number_format($c['bn'], 0, ',', '.'), 10) .
                str_pad($c['jobs'], 6) .
                $formati
            );
        }

        // Job senza commessa riconosciuta
        $senzaCommessa = $filtrati->filter(fn($e) => !$e['commessa']);
        if ($senzaCommessa->isNotEmpty()) {
            $this->info("\n--- Job senza commessa riconosciuta ({$senzaCommessa->count()}) ---\n");
            foreach ($senzaCommessa->take(20) as $j) {
                $data = $j['date'] ? date('Y-m-d', $j['date']) : '?';
                $this->line("  {$j['title']} | Fogli: {$j['fogli']} | {$data}");
            }
        }

        $this->info("\n=== Fine report ===");
        return 0;
    }

    /**
     * Mostra le date minima e massima presenti nell'Accounting
     */
    private function mostraRangeDate(array $items): void
    {
        $dates = [];
        foreach ($items as $entry) {
            $dateStr = $entry['date'] ?? $entry['print date'] ?? '';
            $ts = $this->parseDate($dateStr);
            if ($ts) $dates[] = $ts;
        }

        if (empty($dates)) {
            $this->warn('Nessuna data trovata nei dati.');
            return;
        }

        sort($dates);
        $this->info("Entries con data: " . count($dates) . " / " . count($items));
        $this->info("Data più vecchia:  " . date('Y-m-d H:i', $dates[0]));
        $this->info("Data più recente:  " . date('Y-m-d H:i', end($dates)));

        // Distribuzione per mese
        $this->info("\nDistribuzione per mese:");
        $perMese = collect($dates)->groupBy(fn($ts) => date('Y-m', $ts));
        foreach ($perMese->sortKeys() as $mese => $group) {
            $this->line("  {$mese}: {$group->count()} job");
        }
    }

    /**
     * Parsa date in vari formati Fiery
     */
    private function parseDate(string $dateStr): ?int
    {
        if (empty($dateStr)) return null;

        // Prova formati comuni
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:sP',
            'm/d/Y H:i:s',
            'd/m/Y H:i:s',
            'Y-m-d',
            'm/d/Y',
            'd/m/Y',
        ];

        foreach ($formats as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $dateStr);
            if ($d) return $d->getTimestamp();
        }

        // Fallback strtotime
        $ts = strtotime($dateStr);
        return $ts !== false ? $ts : null;
    }

    /**
     * Recupera dati raw dall'Accounting API (senza cache)
     */
    private function getAccountingRaw(FieryService $fiery)
    {
        // Usa reflection per accedere all'apiGet privato, oppure facciamo la chiamata diretta
        $host = config('fiery.host');
        $baseUrl = 'https://' . $host;

        // Login
        try {
            $loginR = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(15)
                ->post($baseUrl . '/live/api/v5/login', [
                    'username' => config('fiery.username'),
                    'password' => config('fiery.password'),
                    'accessrights' => config('fiery.api_key'),
                ]);

            if (!$loginR->successful()) {
                return null;
            }

            $cookies = [];
            foreach ($loginR->cookies() as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }

            $r = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(60)
                ->withCookies($cookies, $host)
                ->get($baseUrl . '/live/api/v5/accounting');

            // Logout
            try {
                \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withCookies($cookies, $host)
                    ->post($baseUrl . '/live/api/v5/logout');
            } catch (\Exception $e) {}

            return $r->successful() ? $r->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
