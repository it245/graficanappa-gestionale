<?php

namespace App\Jobs;

use App\Http\Services\PrinectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CachePrinectInkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(public string $commessa)
    {
    }

    public function handle(PrinectService $service): void
    {
        $jobId = ltrim(explode('-', $this->commessa)[0] ?? '', '0');
        if (!$jobId || !is_numeric($jobId)) return;

        try {
            $wsData = $service->getJobWorksteps($jobId);
            if (!$wsData) {
                Log::info("CachePrinectInk {$this->commessa}: API Prinect no response (job {$jobId} non trovato)");
                Cache::put("prinect_ink_total_{$this->commessa}", null, 3600);
                return;
            }
            $allWs = collect($wsData['worksteps'] ?? []);
            $printing = $allWs->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));
            $completed = $printing->filter(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');

            if ($completed->isEmpty()) {
                Log::info("CachePrinectInk {$this->commessa}: tot ws=" . $allWs->count() . ", printing=" . $printing->count() . ", completed=" . $completed->count() . " → NO DATA");
                Cache::put("prinect_ink_total_{$this->commessa}", null, 86400);
                return;
            }

            $tot = 0;
            $countInk = 0;
            foreach ($completed as $ws) {
                $ink = $service->getWorkstepInkConsumption($jobId, $ws['id']);
                foreach (($ink['inkConsumptions'] ?? []) as $c) {
                    $tot += (float) ($c['estimatedConsumption'] ?? 0);
                    $countInk++;
                }
            }

            $grammi = round($tot * 1000, 1);
            Cache::put("prinect_ink_total_{$this->commessa}", $grammi, 86400 * 7);
            Log::info("CachePrinectInk {$this->commessa}: {$completed->count()} ws completed, {$countInk} ink entries = {$grammi}g");
        } catch (\Throwable $e) {
            Log::warning("CachePrinectInk errore {$this->commessa}: " . $e->getMessage());
        }
    }
}
