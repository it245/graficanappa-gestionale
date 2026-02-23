<?php

namespace App\Http\Controllers;

use App\Http\Services\FieryService;
use App\Models\Ordine;
use App\Models\OrdineFase;
use Illuminate\Http\Request;

class FieryController extends Controller
{
    public function index(FieryService $fiery)
    {
        $status = $fiery->getServerStatus();

        if ($status) {
            $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);
        }

        return view('fiery.dashboard', compact('status'));
    }

    public function statusJson(FieryService $fiery)
    {
        $status = $fiery->getServerStatus();

        if (!$status) {
            return response()->json([
                'online' => false,
                'stato' => 'offline',
                'avviso' => 'Fiery non raggiungibile',
            ]);
        }

        $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);

        return response()->json($status);
    }

    /**
     * Estrae il numero dal nome job Fiery e cerca la commessa nel MES.
     * Job Fiery: "66539_Schede_BrochureMaurelliGroup_2026_33x48.pdf"
     * Commessa MES: "0066539-26" (prefisso 00, suffisso -26)
     */
    private function cercaCommessa(?string $jobName): ?array
    {
        if (!$jobName) return null;

        // Estrai il numero prima del primo underscore
        if (!preg_match('/^(\d+)_/', $jobName, $matches)) {
            return null;
        }

        $numero = $matches[1];
        $commessaCode = '00' . $numero . '-26';

        $ordine = Ordine::where('commessa', $commessaCode)->first();
        if (!$ordine) return null;

        // Cerca operatori assegnati alle fasi attive (stato 2 = avviata)
        $fasiAttive = OrdineFase::where('ordine_id', $ordine->id)
            ->where('stato', 2)
            ->with(['operatori' => function($q) {
                $q->select('operatori.id', 'operatori.nome');
            }])
            ->get();

        $operatori = $fasiAttive->flatMap(function($fase) {
            return $fase->operatori->pluck('nome');
        })->unique()->values()->toArray();

        return [
            'commessa' => $ordine->commessa,
            'cliente' => $ordine->cliente_nome,
            'descrizione' => $ordine->descrizione,
            'operatori' => $operatori,
        ];
    }
}
