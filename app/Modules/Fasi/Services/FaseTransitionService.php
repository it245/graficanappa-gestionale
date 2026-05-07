<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Services;

use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Modules\Fasi\Events\FaseAvviata;
use App\Modules\Fasi\Events\FaseTerminata;
use App\Modules\Fasi\Exceptions\FaseTransitionException;
use App\Modules\Fasi\Rules\PausaRule;
use App\Modules\Fasi\StateMachine\Transizioni;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servizio applicativo per transizioni di stato di OrdineFase.
 *
 * - Valida la transizione tramite il registry Transizioni.
 * - Persiste lo stato in modo atomico (DB transaction).
 * - Registra audit log.
 * - Dispatcha eventi di dominio (FaseAvviata, FaseTerminata).
 *
 * TODO: valutare dispatch async via queue (es. ShouldQueue listener)
 *       quando i listener cresceranno (notifiche WhatsApp/Telegram, ricalcoli).
 */
final class FaseTransitionService
{
    /**
     * Esegue la transizione verso $nuovoStato.
     *
     * @param  int|string  $nuovoStato  Stato target (int 0..5, 'EXT', motivo pausa)
     *
     * @throws FaseTransitionException
     */
    public function transizione(
        OrdineFase $fase,
        int|string $nuovoStato,
        ?Operatore $operatore = null,
    ): bool {
        $vecchioStato = $fase->stato;

        Transizioni::validate($vecchioStato, $nuovoStato);

        DB::transaction(function () use ($fase, $nuovoStato): void {
            $fase->stato = $nuovoStato;
            $fase->save();
        });

        $this->audit($fase, $vecchioStato, $nuovoStato, $operatore);
        $this->dispatchEvento($fase, $vecchioStato, $nuovoStato, $operatore);

        return true;
    }

    /**
     * Pausa la fase con motivo testuale. Shortcut con guard PausaRule.
     */
    public function pausa(OrdineFase $fase, string $motivo, ?Operatore $operatore = null): bool
    {
        if (! PausaRule::canPausa($fase)) {
            throw FaseTransitionException::pausaNonConsentita($fase->stato);
        }

        return $this->transizione($fase, $motivo, $operatore);
    }

    /**
     * Riprende dopo pausa, riportando la fase ad AVVIATA (2).
     */
    public function riprendi(OrdineFase $fase, ?Operatore $operatore = null): bool
    {
        if (! PausaRule::canRiprendi($fase)) {
            throw FaseTransitionException::ripresaNonConsentita($fase->stato);
        }

        return $this->transizione($fase, 2, $operatore);
    }

    private function audit(
        OrdineFase $fase,
        int|string $da,
        int|string $a,
        ?Operatore $operatore,
    ): void {
        Log::channel(config('logging.default'))->info('fase.transizione', [
            'fase_id'       => $fase->getKey(),
            'ordine_id'     => $fase->ordine_id ?? null,
            'da'            => $da,
            'a'             => $a,
            'operatore_id'  => $operatore?->getKey(),
            'operatore'     => $operatore?->codice ?? null,
            'at'            => now()->toIso8601String(),
        ]);
    }

    private function dispatchEvento(
        OrdineFase $fase,
        int|string $vecchio,
        int|string $nuovo,
        ?Operatore $operatore,
    ): void {
        // Pronta -> Avviata
        if ($this->isInt($vecchio, 1) && $this->isInt($nuovo, 2)) {
            FaseAvviata::dispatch($fase, $operatore, false);
            return;
        }

        // pausa stringa -> Avviata = ripresa
        if (is_string($vecchio) && ! ctype_digit($vecchio) && $this->isInt($nuovo, 2)) {
            FaseAvviata::dispatch($fase, $operatore, true);
            return;
        }

        // qualsiasi -> Terminata
        if ($this->isInt($nuovo, 3)) {
            FaseTerminata::dispatch($fase, $operatore);
        }
    }

    private function isInt(int|string $v, int $expected): bool
    {
        if (is_int($v)) {
            return $v === $expected;
        }

        return ctype_digit($v) && (int) $v === $expected;
    }
}
