<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Models\OrdineFase;
use App\Modules\Reparti\Services\RepartoService;
use App\Modules\Reportistica\Cache\ReportCache;
use App\Services\DescrizioneParser;
use Carbon\Carbon;

/**
 * Report fustelle/cliché in produzione nei prossimi 30 giorni.
 *
 * Estratto da {@see \App\Http\Controllers\DashboardOwnerController::fustelleOverview}.
 *
 * Risolve dinamicamente i reparti "fustella*" via {@see RepartoService}
 * (no magic numbers) e parsa il codice fustella dalla descrizione/note
 * via {@see DescrizioneParser}.
 *
 * Output legacy preservato: array<string codice, array<string commessa, array>>.
 */
final class FustelleReportService
{
    public function __construct(
        private readonly RepartoService $reparti,
    ) {}

    /**
     * @return array<string, array<string, array{commessa:string,cliente:string,descrizione:string,data_consegna:?string,stato:mixed,fase:string}>>
     */
    public function overview(): array
    {
        return ReportCache::remember(
            ReportCache::KEY_FUSTELLE,
            ReportCache::TTL_PANORAMICA,
            fn () => $this->calcola(),
        );
    }

    /**
     * IDs dei reparti "fustella*" (fustella, fustella piana, fustella cilindrica).
     *
     * @return list<int>
     */
    public function repartiFustellaIds(): array
    {
        return $this->reparti->tutti()
            ->filter(fn ($r) => str_contains(strtolower((string) $r->nome), 'fustella'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function calcola(): array
    {
        $repartiFustella = $this->repartiFustellaIds();

        $fasi = OrdineFase::where('stato', '<', 3)
            ->where(fn ($q) => $q->where('esterno', false)->orWhereNull('esterno'))
            ->whereHas('faseCatalogo', function ($q) use ($repartiFustella) {
                $q->whereIn('reparto_id', $repartiFustella);
            })
            ->whereHas('ordine', function ($q) {
                $q->where('data_prevista_consegna', '<=', Carbon::today()->addDays(30));
            })
            ->with(['ordine', 'faseCatalogo.reparto'])
            ->get();

        $fustelleMap = [];
        foreach ($fasi as $fase) {
            $desc    = $fase->ordine->descrizione ?? '';
            $cliente = $fase->ordine->cliente_nome ?? '';
            $notePre = $fase->ordine->note_prestampa ?? '';

            $fsCodice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);
            if (!$fsCodice) {
                continue;
            }

            $codici = array_map('trim', explode('/', $fsCodice));
            foreach ($codici as $codice) {
                if (!isset($fustelleMap[$codice])) {
                    $fustelleMap[$codice] = [];
                }
                $commessa = $fase->ordine->commessa;
                if (!isset($fustelleMap[$codice][$commessa])) {
                    $fustelleMap[$codice][$commessa] = [
                        'commessa'      => $commessa,
                        'cliente'       => $fase->ordine->cliente_nome ?? '-',
                        'descrizione'   => $fase->ordine->descrizione ?? '-',
                        'data_consegna' => $fase->ordine->data_prevista_consegna,
                        'stato'         => $fase->stato,
                        'fase'          => $fase->faseCatalogo->reparto->nome ?? $fase->fase,
                    ];
                }
            }
        }

        ksort($fustelleMap);
        return $fustelleMap;
    }
}
