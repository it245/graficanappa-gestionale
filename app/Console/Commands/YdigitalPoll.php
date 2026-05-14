<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OrdineFase;
use Carbon\Carbon;

/**
 * Polling Y Digital API.
 * Per ogni sensore attivo: chiama /data/?start=ultimo_ts&end=now, somma delta
 * gestendo reset (value < prev → delta = value), incrementa qta_prod fase stato 2
 * della macchina associata.
 */
class YdigitalPoll extends Command
{
    protected $signature = 'ydigital:poll {--debug}';

    protected $description = 'Polling sensori Y Digital + aggiorna qta_prod fasi attive';

    public function handle(): int
    {
        $apiKey = env('YDIGITAL_API_KEY');
        if (!$apiKey) {
            $this->error('YDIGITAL_API_KEY mancante nel .env');
            return 1;
        }

        $sensori = DB::table('sensori_ydigital')->where('attivo', true)->get();
        if ($sensori->isEmpty()) {
            $this->warn('Nessun sensore attivo configurato.');
            return 0;
        }

        $debug = $this->option('debug');

        foreach ($sensori as $s) {
            // Range: dall'ultimo timestamp persistito (o ultimi 10 min se mai letto) a ora.
            $startCarbon = $s->ultimo_ts
                ? Carbon::parse($s->ultimo_ts)->copy()->addMillisecond()
                : Carbon::now()->subMinutes(10);
            $endCarbon = Carbon::now();

            $start = $startCarbon->utc()->format('Y-m-d\TH:i:s\Z');
            $end = $endCarbon->utc()->format('Y-m-d\TH:i:s\Z');

            $url = "https://ysens.it/api/v1/sensors/{$s->device_id}/{$s->sensor_name}/data/?start={$start}&end={$end}";

            try {
                $resp = Http::withoutVerifying()
                    ->withHeaders(['X-API-Key' => $apiKey])
                    ->timeout(15)
                    ->get($url);
                if (!$resp->successful()) {
                    $this->warn("[{$s->device_id}/{$s->sensor_name}] HTTP " . $resp->status());
                    continue;
                }
                $points = $resp->json('data') ?? [];
                if (empty($points)) {
                    if ($debug) $this->info("[{$s->device_id}/{$s->sensor_name}] no new points (start={$start})");
                    continue;
                }

                $prevValue = (int) ($s->ultimo_value ?? 0);
                $deltaTotale = 0;
                $ultimoTs = null;
                $ultimoValue = $prevValue;

                foreach ($points as $p) {
                    $v = $p['value_1'] ?? null;
                    $t = $p['timestamp'] ?? null;
                    if ($v === null || !$t) continue;
                    $v = (int) $v;

                    // Reset counter: value scende sotto prev → nuovo burst, delta = value
                    if ($v < $prevValue) {
                        $deltaTotale += $v;
                    } else {
                        $deltaTotale += ($v - $prevValue);
                    }
                    $prevValue = $v;
                    $ultimoTs = $t;
                    $ultimoValue = $v;
                }

                if (!$ultimoTs) continue;

                $faseAttivaId = null;
                if ($s->macchina && $deltaTotale > 0) {
                    $fase = OrdineFase::where('sched_macchina', $s->macchina)
                        ->where('stato', '2')
                        ->whereNull('deleted_at')
                        ->orderBy('data_inizio')
                        ->first();
                    if ($fase) {
                        $faseAttivaId = $fase->id;
                        $fase->increment('qta_prod', $deltaTotale);
                        if ($debug) $this->info("Fase {$fase->id} ({$fase->fase}) +{$deltaTotale}");
                    }
                }

                DB::table('sensori_ydigital')->where('id', $s->id)->update([
                    'ultimo_value' => $ultimoValue,
                    'ultimo_ts' => Carbon::parse($ultimoTs),
                    'ultimo_delta' => $deltaTotale,
                    'updated_at' => now(),
                ]);

                DB::table('sensori_letture')->insert([
                    'sensore_id' => $s->id,
                    'value' => $ultimoValue,
                    'delta' => $deltaTotale,
                    'letto_at' => Carbon::parse($ultimoTs),
                    'ordine_fase_id' => $faseAttivaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info("[{$s->device_id}/{$s->sensor_name}] punti=" . count($points) . " delta=+{$deltaTotale}" . ($faseAttivaId ? " → fase $faseAttivaId" : ''));
            } catch (\Throwable $e) {
                Log::error("YdigitalPoll error {$s->device_id}/{$s->sensor_name}: " . $e->getMessage());
                $this->error("[{$s->device_id}/{$s->sensor_name}] " . $e->getMessage());
            }
        }

        return 0;
    }
}
