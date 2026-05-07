<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Services;

use App\Models\Fustella;
use App\Models\Operatore;
use App\Models\Ordine;
use App\Modules\Fustelle\Enums\StatoFustella;
use App\Modules\Fustelle\Events\FustellaPrelevata;
use App\Modules\Fustelle\Events\FustellaRestituita;
use Carbon\Carbon;

/**
 * Tracking prelievi/restituzioni fustelle dal magazzino.
 *
 * NB: la persistenza di "chi ha preso cosa quando" è demandata agli
 * eventi (listener applicativo / tabella audit). Questo servizio è
 * responsabile della transizione di stato e dell'emissione evento.
 */
final class PrelievoFustellaService
{
    public function __construct(
        private readonly FustellaService $fustellaService,
    ) {
    }

    /**
     * Preleva la fustella per la commessa indicata.
     *
     * @return string|null posizione magazzino prima del prelievo, se nota.
     *
     * @throws \DomainException se la fustella non è disponibile (stato != PRONTA).
     */
    public function preleva(Fustella $fustella, Operatore $operatore, Ordine $commessa): ?string
    {
        if (!$fustella->stato->eDisponibile()) {
            throw new \DomainException(
                "Fustella {$fustella->codice} non disponibile (stato: {$fustella->stato->value})"
            );
        }

        $posizione = $fustella->posizione_magazzino;

        $this->fustellaService->cambiaStato($fustella, StatoFustella::IN_USO);

        FustellaPrelevata::dispatch(
            $fustella->refresh(),
            $operatore,
            $commessa,
            Carbon::now(),
        );

        return $posizione;
    }

    /**
     * Restituisce la fustella al magazzino.
     *
     * @throws \DomainException se la fustella non risulta IN_USO.
     */
    public function restituisci(
        Fustella $fustella,
        Operatore $operatore,
        ?string $nuovaPosizione = null,
    ): void {
        if ($fustella->stato !== StatoFustella::IN_USO) {
            throw new \DomainException(
                "Fustella {$fustella->codice} non risulta in uso (stato: {$fustella->stato->value})"
            );
        }

        if ($nuovaPosizione !== null) {
            $this->fustellaService->aggiorna($fustella, [
                'posizione_magazzino' => trim($nuovaPosizione),
            ]);
        }

        $this->fustellaService->cambiaStato($fustella, StatoFustella::PRONTA);

        FustellaRestituita::dispatch(
            $fustella->refresh(),
            $operatore,
            Carbon::now(),
            $nuovaPosizione,
        );
    }
}
