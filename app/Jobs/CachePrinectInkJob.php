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
            $worksteps = collect($wsData['worksteps'] ?? [])
                ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []))
                ->filter(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');

            if ($worksteps->isEmpty()) {
                Cache::put("prinect_ink_total_{$this->commessa}", null, 86400);
                return;
            }

            $tot = 0;
            foreach ($worksteps as $ws) {
                $ink = $service->getWorkstepInkConsumption($jobId, $ws['id']);
                foreach (($ink['inkConsumptions'] ?? []) as $c) {
                    $tot += (float) ($c['estimatedConsumption'] ?? 0);
                }
            }

            $grammi = round($tot * 1000, 1);
            Cache::put("prinect_ink_total_{$this->commessa}", $grammi, 86400 * 7);
            Log::info("CachePrinectInkJob: commessa {$this->commessa} = {$grammi}g");
        } catch (\Throwable $e) {
            Log::warning("CachePrinectInkJob errore commessa {$this->commessa}: " . $e->getMessage());
        }
    }
}
