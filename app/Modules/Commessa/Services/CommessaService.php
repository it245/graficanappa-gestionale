<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Modules\Commessa\Enums\StatoCommessa;
use App\Modules\Commessa\Rules\StatoDerivatoRule;
use Illuminate\Support\Collection;

/**
 * Servizio di alto livello sulla commessa: query DB + delega alle Rules pure.
 *
 * Non modifica i model esistenti (Ordine, OrdineFase): si limita a leggerli.
 *
 * @example
 *   $svc = app(CommessaService::class);
 *   $stato = $svc->getStatoAggregato('0067164-26');   // StatoCommessa::InCorso
 *   $svc->getQtaTotale('0067164-26');                  // 5000
 *   $svc->getQtaConsegnata('0067164-26');              // 2500
 */
final class CommessaService
{
    public function __construct(
        private readonly StatoDerivatoRule $statoRule = new StatoDerivatoRule(),
    ) {}

    /**
     * Stato aggregato della commessa (deriva da tutte le fasi di tutti gli ordini).
     */
    public function getStatoAggregato(string $codCommessa): StatoCommessa
    {
        $fasi = OrdineFase::query()
            ->whereHas('ordine', fn ($q) => $q->where('commessa', $codCommessa))
            ->get(['id', 'ordine_id', 'stato']);

        return $this->statoRule->calcolaStato($fasi);
    }

    /**
     * Somma `qta_richiesta` esclusi i semilavorati (cod_art LIKE 'SEMILAV%').
     * Convenzione MES: i semilavorati sono pezzi intermedi, non vendibili.
     */
    public function getQtaTotale(string $codCommessa): int
    {
        return (int) Ordine::query()
            ->where('commessa', $codCommessa)
            ->where(function ($q) {
                $q->whereNull('cod_art')
                  ->orWhere('cod_art', 'NOT LIKE', 'SEMILAV%');
            })
            ->sum('qta_richiesta');
    }

    /**
     * Quantità complessivamente fatturata/consegnata (qta_ddt_vendita).
     */
    public function getQtaConsegnata(string $codCommessa): int
    {
        return (int) Ordine::query()
            ->where('commessa', $codCommessa)
            ->where(function ($q) {
                $q->whereNull('cod_art')
                  ->orWhere('cod_art', 'NOT LIKE', 'SEMILAV%');
            })
            ->sum('qta_ddt_vendita');
    }

    /**
     * Ordini articoli finiti della commessa (esclude semilavorati).
     *
     * @return Collection<int, Ordine>
     */
    public function getOrdiniArticoliFiniti(string $codCommessa): Collection
    {
        return Ordine::query()
            ->where('commessa', $codCommessa)
            ->where(function ($q) {
                $q->whereNull('cod_art')
                  ->orWhere('cod_art', 'NOT LIKE', 'SEMILAV%');
            })
            ->orderBy('cod_art')
            ->get();
    }
}
