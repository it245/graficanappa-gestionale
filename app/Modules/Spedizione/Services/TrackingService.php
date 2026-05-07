<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Services;

use App\Http\Services\BrtService;
use App\Models\DdtSpedizione;
use App\Modules\Spedizione\Enums\StatoSpedizione;
use App\Modules\Spedizione\Events\SpedizioneInRitardo;
use App\Modules\Spedizione\Rules\RitardoRule;
use App\Modules\Spedizione\ValueObjects\CodiceTracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Servizio applicativo: aggiorna tracking BRT sui DDT MES.
 *
 * NON modifica BrtService né DdtSpedizione: si limita a usarli come collaboratori.
 * Persistenze sui campi cache (brt_stato, brt_data_consegna, ...) restano nel model.
 */
final class TrackingService
{
    public function __construct(
        private readonly BrtService $brt,
        private readonly RitardoRule $ritardoRule,
    ) {}

    /**
     * Aggiorna lo stato BRT di un singolo DDT.
     * Dispatcha SpedizioneInRitardo se la regola scatta dopo l'update.
     */
    public function aggiornaStato(DdtSpedizione $ddt): void
    {
        if (empty($ddt->numero_ddt)) {
            return;
        }

        try {
            $tracking = $this->brt->getTrackingByDDT((string) $ddt->numero_ddt);
        } catch (\Throwable $e) {
            Log::warning('TrackingService: errore tracking BRT', [
                'ddt' => $ddt->numero_ddt,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if ($tracking === null) {
            return;
        }

        if (!empty($tracking['multi_spedizione'])) {
            $ddt->brt_stato = 'MULTI-SPEDIZIONE';
            $ddt->brt_cache_at = Carbon::now();
            $ddt->save();
            return;
        }

        $stato = (string) ($tracking['stato'] ?? '');
        $bolla = $tracking['bolla'] ?? [];

        $ddt->brt_stato = $stato;
        $ddt->brt_data_consegna = $bolla['data_consegna'] ?? $ddt->brt_data_consegna;
        $ddt->brt_destinatario = $bolla['destinatario_ragione_sociale'] ?? $ddt->brt_destinatario;
        $ddt->brt_colli = $bolla['colli'] ?? $ddt->brt_colli;
        $ddt->brt_cache_at = Carbon::now();
        $ddt->save();

        if ($this->ritardoRule->dovrebbeNotificare($ddt)) {
            event(new SpedizioneInRitardo(
                ddt: $ddt,
                giorniRitardo: $this->ritardoRule->giorniRitardo($ddt),
            ));
        }
    }

    /**
     * Aggiorna in bulk i DDT non ancora consegnati.
     * Restituisce il numero di DDT processati.
     */
    public function bulkUpdate(): int
    {
        $count = 0;

        DdtSpedizione::query()
            ->whereNotNull('numero_ddt')
            ->where(function ($q) {
                $q->whereNull('brt_stato')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('brt_stato')
                         ->where('brt_stato', 'NOT LIKE', '%CONSEGNATA%')
                         ->where('brt_stato', 'NOT LIKE', '%MULTI%');
                  });
            })
            ->chunkById(200, function ($ddts) use (&$count) {
                foreach ($ddts as $ddt) {
                    $this->aggiornaStato($ddt);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * URL pubblico di tracking per un DDT.
     */
    public function linkTracking(DdtSpedizione $ddt): string
    {
        if (empty($ddt->numero_ddt)) {
            return '';
        }

        $codice = new CodiceTracking(
            numeroDDT: (string) $ddt->numero_ddt,
            corriere: $this->corriereDi($ddt),
        );

        return $codice->urlTracking();
    }

    /**
     * Stato MES-friendly derivato dalla cache BRT.
     */
    public function statoMes(DdtSpedizione $ddt): StatoSpedizione
    {
        return StatoSpedizione::daDescrizioneBrt($ddt->brt_stato ?? null);
    }

    private function corriereDi(DdtSpedizione $ddt): string
    {
        $vettore = mb_strtoupper((string) ($ddt->vettore ?? ''));
        return str_contains($vettore, 'BRT') ? CodiceTracking::CORRIERE_BRT : CodiceTracking::CORRIERE_ALTRO;
    }
}
