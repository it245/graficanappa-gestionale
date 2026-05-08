<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Services;

use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\PausaOperatore;
use App\Modules\Fasi\Events\FaseAvviata;
use App\Modules\Fasi\Events\FaseTerminata;
use App\Modules\Fasi\Exceptions\FaseTransitionException;
use App\Modules\Fasi\Rules\PausaRule;
use App\Modules\Fasi\StateMachine\Transizioni;
use App\Services\FaseStatoService;
use Carbon\Carbon;
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
 * NOTA Strangler Fig (sessione 07/05/2026):
 *   I metodi avvia()/termina()/riapri()/setEsterna()/consegna() sono stati
 *   estratti dai controller legacy (ProduzioneController, DashboardOwnerController)
 *   per centralizzare la logica side-effect (pivot operatori, pause aperte,
 *   data_inizio/fine, ricalcolo stati a cascata).
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
     * Avvia una fase: stato = 2, attach operatore, set data_inizio.
     *
     * Side-effect: associa l'operatore al pivot fase_operatore se non presente,
     * salva data_inizio sulla fase se mancante, e (se passato) annota note
     * "Inviato a: <terzista>" — usato in flusso esterno parziale.
     *
     * @param  string|null  $terzista  Se valorizzato, scrive note "Inviato a: ..."
     * @return array{fase: OrdineFase, operatori: \Illuminate\Support\Collection}
     */
    public function avvia(
        OrdineFase $fase,
        ?int $operatoreId = null,
        ?string $terzista = null,
    ): array {
        DB::transaction(function () use ($fase, $operatoreId, $terzista): void {
            $fase->loadMissing('operatori');

            if ($operatoreId && ! $fase->operatori->contains($operatoreId)) {
                $fase->operatori()->attach($operatoreId, [
                    'data_inizio' => now(),
                    'data_fine'   => null,
                ]);
            }

            if ($terzista !== null && $terzista !== '') {
                $fase->note = 'Inviato a: '.$terzista;
            }

            $fase->stato = 2;
            if (! $fase->data_inizio) {
                $fase->data_inizio = now()->format('Y-m-d H:i:s');
            }
            $fase->save();
        });

        $operatore = $operatoreId ? Operatore::find($operatoreId) : null;
        $this->audit($fase, 'avvia', 2, $operatore);
        FaseAvviata::dispatch($fase, $operatore, false);

        $fase->load('operatori');

        return ['fase' => $fase, 'operatori' => $fase->operatori];
    }

    /**
     * Termina una fase: stato = 3, set data_fine, chiude pause aperte,
     * accumula secondi_pausa nel pivot, ricalcola fasi successive.
     *
     * @param  array{qta_prod?:int|null, scarti?:int|null, tiro?:int|null, rientro?:bool}  $payload
     * @return OrdineFase
     */
    public function termina(
        OrdineFase $fase,
        ?int $operatoreId = null,
        array $payload = [],
    ): OrdineFase {
        DB::transaction(function () use ($fase, $operatoreId, $payload): void {
            $fase->loadMissing(['operatori', 'ordine']);

            // Auto-chiusura pausa aperta (se l'operatore termina senza aver ripreso)
            $this->chiudiPausaAperta($fase, $operatoreId);

            // qta_prod fallback: qta_fase o qta_richiesta
            $qtaProdotta = $payload['qta_prod'] ?? null;
            if (! $qtaProdotta || $qtaProdotta <= 0) {
                $qtaProdotta = $fase->qta_fase ?: ($fase->ordine->qta_richiesta ?? 0);
            }
            $fase->qta_prod = $qtaProdotta;
            $fase->scarti   = $payload['scarti'] ?? 0;
            if (isset($payload['tiro']) && $payload['tiro'] !== null && $payload['tiro'] !== '') {
                $fase->tiro = (int) $payload['tiro'];
            }
            $fase->timeout = null;

            // Rientro da esterno: stato 1 + rimuovi flag esterno (richiede nuove lavorazioni)
            if (! empty($payload['rientro'])) {
                $fase->stato = 1;
                $fase->esterno = false;
                $fase->data_fine = null;
                $fase->save();
                FaseStatoService::ricalcolaStati($fase->ordine_id);
                return;
            }

            $fase->stato = 3;
            $fase->data_fine = now()->format('Y-m-d H:i:s');
            $fase->save();

            // Aggiorna data_fine pivot per operatore corrente
            if ($operatoreId && $fase->operatori->contains($operatoreId)) {
                $fase->operatori()->updateExistingPivot($operatoreId, [
                    'data_fine' => now(),
                ]);
            }

            // Ricalcola stati delle fasi successive (TUTTA la commessa)
            $commessa = $fase->ordine->commessa ?? null;
            if ($commessa) {
                FaseStatoService::ricalcolaCommessa($commessa);
            } else {
                FaseStatoService::ricalcolaStati($fase->ordine_id);
            }
        });

        $operatore = $operatoreId ? Operatore::find($operatoreId) : null;
        $this->audit($fase, 'termina', $fase->stato, $operatore);

        if ((int) $fase->stato === 3) {
            FaseTerminata::dispatch($fase, $operatore);
        }

        return $fase->refresh();
    }

    /**
     * Pausa la fase con motivo testuale. Shortcut con guard PausaRule.
     *
     * Side-effect: crea record PausaOperatore aperto, set timeout = now().
     * Per motivo "Acconto" salva qta_prod parziale.
     */
    public function pausa(
        OrdineFase $fase,
        string $motivo,
        ?Operatore $operatore = null,
        ?int $qtaProdotta = null,
    ): bool {
        if (! PausaRule::canPausa($fase)) {
            throw FaseTransitionException::pausaNonConsentita($fase->stato);
        }

        DB::transaction(function () use ($fase, $motivo, $operatore, $qtaProdotta): void {
            $operatoreId = $operatore?->getKey();
            if ($operatoreId) {
                PausaOperatore::create([
                    'operatore_id' => $operatoreId,
                    'ordine_id'    => $fase->ordine_id,
                    'fase'         => $fase->fase,
                    'motivo'       => $motivo,
                    'data_ora'     => now(),
                ]);
            }

            // Acconto: cumulativo qta_prod + storico in note (es. "Acconto 500 - 08/05 10:00 - Mario").
            // Permette logistica di vedere ogni invio parziale + somma totale gia spedita.
            if ($motivo === 'Acconto' && $qtaProdotta !== null && $qtaProdotta > 0) {
                $fase->qta_prod = (int) ($fase->qta_prod ?? 0) + (int) $qtaProdotta;

                $autore = $operatore !== null ? ($operatore->nome ?? 'sistema') : 'sistema';
                $timestamp = now()->format('d/m H:i');
                $rigaStorico = "Acconto {$qtaProdotta} - {$timestamp} - {$autore}";
                $noteCorrenti = trim((string) ($fase->note ?? ''));
                $fase->note = $noteCorrenti === ''
                    ? $rigaStorico
                    : $noteCorrenti . "\n" . $rigaStorico;
            }

            $fase->stato   = $motivo;
            $fase->timeout = now();
            $fase->save();
        });

        $this->audit($fase, 2, $motivo, $operatore);

        return true;
    }

    /**
     * Riprende dopo pausa, riportando la fase ad AVVIATA (2).
     *
     * Side-effect: chiude pausa aperta più recente per la fase,
     * accumula secondi_pausa nel pivot dell'operatore originale,
     * eventualmente attacca l'operatore nuovo se diverso.
     */
    public function riprendi(OrdineFase $fase, ?Operatore $operatore = null): bool
    {
        if (! PausaRule::canRiprendi($fase)) {
            throw FaseTransitionException::ripresaNonConsentita($fase->stato);
        }

        $vecchioStato = $fase->stato;
        $operatoreId  = $operatore?->getKey();

        DB::transaction(function () use ($fase, $operatoreId): void {
            $fase->loadMissing('operatori');

            // Chiudi pausa aperta (qualsiasi operatore: A pausa, B può riprendere)
            $pausa = PausaOperatore::where('ordine_id', $fase->ordine_id)
                ->where('fase', $fase->fase)
                ->whereNull('fine')
                ->latest('data_ora')
                ->first();

            if ($pausa) {
                $pausa->fine = now();
                $pausa->save();

                $durataSecondi = Carbon::parse($pausa->data_ora)
                    ->diffInSeconds(Carbon::parse($pausa->fine));
                $operatorePausa = $pausa->operatore_id;

                if ($fase->operatori->contains($operatorePausa)) {
                    $current = $fase->operatori->find($operatorePausa)->pivot->secondi_pausa ?? 0;
                    $fase->operatori()->updateExistingPivot($operatorePausa, [
                        'secondi_pausa' => $current + $durataSecondi,
                    ]);
                }
            }

            // Operatore nuovo (diverso da chi pausò): aggiungi al pivot
            if ($operatoreId && ! $fase->operatori->contains($operatoreId)) {
                $fase->operatori()->attach($operatoreId, [
                    'data_inizio' => now(),
                    'data_fine'   => null,
                ]);
            }

            $fase->stato   = 2;
            $fase->timeout = null;
            $fase->save();
        });

        $this->audit($fase, $vecchioStato, 2, $operatore);
        FaseAvviata::dispatch($fase, $operatore, true);

        return true;
    }

    /**
     * Riapri una fase (da terminata/consegnata torna a stato $a).
     *
     * Owner-only operation: ripristina la fase a stato 0/1/2 e pulisce
     * data_fine, flag esterno, nota "Inviato a:". Usata anche da Prinect/Fiery
     * quando arriva un evento di ripresa stampa.
     *
     * @param  int  $a  Stato target (0/1/2). Default 1 = pronta per riavvio.
     */
    public function riapri(
        OrdineFase $fase,
        int $a = 1,
        ?Operatore $operatore = null,
    ): bool {
        $vecchioStato = $fase->stato;

        DB::transaction(function () use ($fase, $a): void {
            $fase->stato = $a;

            if ($a === 2) {
                $fase->data_fine = null;
                $fase->terminata_manualmente = false;
            }

            if ($a <= 1) {
                $fase->data_fine = null;
                $fase->riaperta_at = now();
                $fase->qta_prod_at_riapertura = (int) ($fase->qta_prod ?? 0);
                $fase->terminata_manualmente = false;

                if ($fase->esterno) {
                    $fase->esterno = false;
                }

                if ($fase->note && preg_match('/Inviato a:/i', $fase->note)) {
                    $fase->note = preg_replace('/,?\s*Inviato a:.*$/i', '', $fase->note);
                    $fase->note = trim($fase->note) ?: null;
                }
            }

            $fase->save();
            FaseStatoService::ricalcolaStati($fase->ordine_id);
        });

        $this->audit($fase, $vecchioStato, $a, $operatore);

        return true;
    }

    /**
     * Marca una fase come "Esterna" (in lavorazione presso fornitore).
     * Side-effect: stato 5, esterno=true, data_inizio se mancante.
     */
    public function setEsterna(
        OrdineFase $fase,
        string $fornitore,
        ?Operatore $operatore = null,
    ): bool {
        $vecchioStato = $fase->stato;

        DB::transaction(function () use ($fase, $fornitore): void {
            $fase->stato = 5;
            $fase->data_inizio = $fase->data_inizio ?? now();
            $fase->esterno = true;
            // Aggiorna note senza sovrascrivere completamente se già presenti
            $noteCorrenti = $fase->note ? preg_replace('/,?\s*Inviato a:.*$/i', '', $fase->note) : '';
            $noteCorrenti = trim((string) $noteCorrenti);
            $fase->note = $noteCorrenti
                ? $noteCorrenti.', Inviato a: '.$fornitore
                : 'Inviato a: '.$fornitore;
            $fase->save();
        });

        $this->audit($fase, $vecchioStato, 5, $operatore);

        return true;
    }

    /**
     * Consegna una fase (transizione 3 -> 4).
     */
    public function consegnaFase(OrdineFase $fase, ?Operatore $operatore = null): bool
    {
        return $this->transizione($fase, 4, $operatore);
    }

    /**
     * Auto-chiude la pausa aperta dell'operatore al momento del termina.
     * Accumula i secondi nel pivot fase_operatore.
     */
    private function chiudiPausaAperta(OrdineFase $fase, ?int $operatoreId): void
    {
        if (! $operatoreId) {
            return;
        }

        $pausaAperta = PausaOperatore::where('operatore_id', $operatoreId)
            ->where('ordine_id', $fase->ordine_id)
            ->where('fase', $fase->fase)
            ->whereNull('fine')
            ->latest('data_ora')
            ->first();

        if (! $pausaAperta) {
            return;
        }

        $pausaAperta->fine = now();
        $pausaAperta->save();

        $durataSecondi = Carbon::parse($pausaAperta->data_ora)
            ->diffInSeconds(Carbon::parse($pausaAperta->fine));

        if ($fase->operatori->contains($operatoreId)) {
            $current = $fase->operatori->find($operatoreId)->pivot->secondi_pausa ?? 0;
            $fase->operatori()->updateExistingPivot($operatoreId, [
                'secondi_pausa' => $current + $durataSecondi,
            ]);
        }
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
