<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OrdineFase;
use Carbon\Carbon;

/**
 * Polling Y Digital API ogni minuto.
 * Per ogni sensore attivo: chiama /latest/, calcola delta vs ultima lettura,
 * incrementa qta_prod sulla fase stato 2 attiva della macchina associata.
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
            $url = "https://ysens.it/api/v1/sensors/{$s->device_id}/{$s->sensor_name}/latest/";
            try {
                $resp = Http::withHeaders(['X-API-Key' => $apiKey])->timeout(10)->get($url);
                if (!$resp->successful()) {
                    $this->warn("[{$s->device_id}/{$s->sensor_name}] HTTP " . $resp->status());
                    continue;
                }
                $data = $resp->json('data');
                if (!$data) continue;

                $value = $data['value_1'] ?? null;
                $ts = $data['timestamp'] ?? null;
                if ($value === null || !$ts) continue;

                $tsCarbon = Carbon::parse($ts);
                // Stesso timestamp ultimo → nessun nuovo dato
                if ($s->ultimo_ts && Carbon::parse($s->ultimo_ts)->equalTo($tsCarbon)) {
                    if ($debug) $this->info("[{$s->device_id}/{$s->sensor_name}] no change");
                    continue;
                }

                $delta = $s->ultimo_value !== null ? max(0, $value - $s->ultimo_value) : 0;

                // Trova fase attiva su questa macchina
                $faseAttivaId = null;
                if ($s->macchina && $delta > 0) {
                    $fase = OrdineFase::where('sched_macchina', $s->macchina)
                        ->where('stato', '2')
                        ->whereNull('deleted_at')
                        ->orderBy('data_inizio')
                        ->first();
                    if ($fase) {
                        $faseAttivaId = $fase->id;
                        $fase->increment('qta_prod', (int) $delta);
                        if ($debug) $this->info("Fase {$fase->id} ({$fase->fase}) +{$delta} (totale: " . ($fase->qta_prod + (int)$delta) . ")");
                    }
                }

                // Aggiorna sensore
                DB::table('sensori_ydigital')->where('id', $s->id)->update([
                    'ultimo_value' => $value,
                    'ultimo_ts' => $tsCarbon,
                    'ultimo_delta' => $delta,
                    'updated_at' => now(),
                ]);

                // Audit lettura
                DB::table('sensori_letture')->insert([
                    'sensore_id' => $s->id,
                    'value' => $value,
                    'delta' => $delta,
                    'letto_at' => $tsCarbon,
                    'ordine_fase_id' => $faseAttivaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info("[{$s->device_id}/{$s->sensor_name}] value={$value} delta=+{$delta}" . ($faseAttivaId ? " → fase $faseAttivaId" : ''));
            } catch (\Throwable $e) {
                Log::error("YdigitalPoll error {$s->device_id}/{$s->sensor_name}: " . $e->getMessage());
                $this->error("[{$s->device_id}/{$s->sensor_name}] " . $e->getMessage());
            }
        }

        return 0;
    }
}
