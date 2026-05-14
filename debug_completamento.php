<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\PrinectService;
use App\Models\OrdineFase;
use App\Models\Ordine;

$service = app(PrinectService::class);

foreach (['67379', '67382'] as $jobId) {
    echo "\n=== Job $jobId ===\n";
    try {
        $wsData = $service->getJobWorksteps($jobId);
        $worksteps = collect($wsData['worksteps'] ?? []);
        echo "Totale worksteps API: " . $worksteps->count() . "\n";
        $convWs = $worksteps->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));
        echo "Filtrati ConventionalPrinting: " . $convWs->count() . "\n";
        foreach ($convWs as $ws) {
            echo "  WS id={$ws['id']} status={$ws['status']} amountProduced=" . ($ws['amountProduced'] ?? 'N/A')
                 . " wasteProduced=" . ($ws['wasteProduced'] ?? 'N/A')
                 . " actualStart=" . ($ws['actualStartDate'] ?? 'NULL')
                 . " actualEnd=" . ($ws['actualEndDate'] ?? 'NULL') . "\n";
        }

        $allCompleted = $convWs->every(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');
        $totaleBuoni = $convWs->sum(fn($ws) => $ws['amountProduced'] ?? 0);
        echo "allCompleted=" . ($allCompleted ? 'true' : 'false') . " totaleBuoni=$totaleBuoni\n";

        $commessa = str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-26';
        $ordineIds = Ordine::where('commessa', $commessa)->pluck('id');
        $fasi = OrdineFase::whereIn('ordine_id', $ordineIds)
            ->where(function($q){ $q->where('fase','like','STAMPAXL%')->orWhere('fase','like','STAMPA XL%')->orWhere('fase','STAMPA'); })
            ->whereIn('stato', [0,1,2,3])
            ->get(['id','fase','stato','terminata_manualmente']);
        echo "Fasi MES match: " . $fasi->count() . "\n";
        foreach ($fasi as $f) {
            echo "  Fase id={$f->id} [{$f->fase}] stato={$f->stato} terminata_manualmente=" . ($f->terminata_manualmente ? 'true' : 'false') . "\n";
        }
    } catch (\Exception $e) {
        echo "ECCEZIONE: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
}
