<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Services;

use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\Enums\StatoWorkstep;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Logica di auto-terminazione fasi STAMPAXL106 sulla base dello stato
 * dei worksteps Prinect.
 *
 * Storia: questa logica era sparsa tra PrinectController, cron giornaliero
 * e il monolite PrinectSyncService. La concentriamo qui per:
 *  - centralizzare il bug fix 66811 (worksteps COMPLETED senza
 *    actualStartDate ma con activities reali);
 *  - poter testare i predicati puri senza DB;
 *  - lasciare PrinectSyncService come thin orchestrator (drop-in).
 *
 * IMPORTANTE: i predicati `deveTerminare()`, `deveRipristinare()`,
 * `eAbbandonata()` sono allineati BIT-FOR-BIT al comportamento legacy in
 * produzione (vedi memoria fix 66811). Modifiche richiedono confronto
 * diretto con PrinectSyncService originale.
 */
final class PrinectAutoTerminaService
{
    /**
     * Soglia "attività recente": se l'ultima attività Prinect è più
     * recente di questi minuti, la fase NON va auto-terminata.
     */
    public const SOGLIA_ATTIVITA_RECENTE_MIN = 60;

    /**
     * Soglia ore per considerare una fase "abbandonata": ultima attività
     * più di N ore fa E in giorno diverso da oggi.
     */
    public const SOGLIA_ABBANDONO_ORE = 4;

    public function __construct(
        private readonly PrinectApiInterface $api,
        private readonly PrinectJobsService $jobs,
    ) {
    }

    /**
     * Decide se una fase di stampa va terminata sulla base dei worksteps
     * Prinect e della quantità carta richiesta.
     *
     * Replica la logica legacy di PrinectSyncService::controllaCompletamentoPrinect:
     *  - Regola 1: TUTTI i worksteps in stato COMPLETED → terminare.
     *  - Regola 2: nessun WAITING + worksteps con actualEndDate +
     *              fogli buoni >= qta_carta → terminare.
     *  - Regola 6: stessa cosa ma confrontando fogli totali (buoni+scarto)
     *              vs qta_carta.
     *
     * @param  Collection<int,array<string,mixed>>  $worksteps  worksteps di stampa convenzionale
     * @param  int                                  $qtaCarta   quantità carta dell'ordine (0 = non definita)
     */
    public function deveTerminare(Collection $worksteps, int $qtaCarta): bool
    {
        if ($worksteps->isEmpty()) {
            return false;
        }

        $allCompleted   = $worksteps->every(
            fn(array $ws) => StatoWorkstep::tryFromApi($ws['status'] ?? null) === StatoWorkstep::Completed
        );
        $anyWaiting     = $worksteps->contains(
            fn(array $ws) => StatoWorkstep::tryFromApi($ws['status'] ?? null) === StatoWorkstep::Waiting
        );

        $totaleBuoni  = (int) $worksteps->sum(fn(array $ws) => $ws['amountProduced'] ?? 0);
        $totaleScarto = (int) $worksteps->sum(fn(array $ws) => $ws['wasteProduced']  ?? 0);
        $totaleFogli  = $totaleBuoni + $totaleScarto;

        if ($totaleBuoni <= 0) {
            return false;
        }

        // Regola 1
        if ($allCompleted) {
            return true;
        }

        // Regole 2 e 6: solo se NO WAITING e almeno un workstep terminato
        $wsTerminati = $worksteps->filter(fn(array $ws) =>
            !empty($ws['actualStartDate']) && !empty($ws['actualEndDate'])
        );

        if (!$anyWaiting && $wsTerminati->isNotEmpty() && $qtaCarta > 0) {
            if ($totaleBuoni >= $qtaCarta) return true;
            if ($totaleFogli >= $qtaCarta) return true;
        }

        return false;
    }

    /**
     * Decide se una fase erroneamente terminata vada riportata a stato
     * "Avviato" (stato 2). Vero se almeno un workstep NON è ancora COMPLETED
     * E c'è stata attività recente.
     *
     * Replica PrinectSyncService::ripristinaFasiAttive: si aspetta che il
     * caller abbia già verificato l'attività recente; questo metodo verifica
     * solo lo stato workstep.
     *
     * @param  Collection<int,array<string,mixed>>  $worksteps
     */
    public function deveRipristinare(Collection $worksteps): bool
    {
        if ($worksteps->isEmpty()) {
            return false;
        }

        return $worksteps->every(
            fn(array $ws) => StatoWorkstep::tryFromApi($ws['status'] ?? null) !== StatoWorkstep::Completed
        );
    }

    /**
     * Decide se l'ultima attività rappresenta un "abbandono": ultima
     * attività > SOGLIA_ABBANDONO_ORE ore fa E in giorno diverso da oggi.
     *
     * Replica PrinectSyncService::terminaFasiAbbandonate.
     */
    public function eAbbandonata(?DateTimeInterface $ultimaAttivita, ?DateTimeInterface $now = null): bool
    {
        if ($ultimaAttivita === null) {
            return false;
        }

        $now    = $now !== null ? Carbon::instance($now) : Carbon::now();
        $ultimo = Carbon::instance($ultimaAttivita);

        $orePassate     = $ultimo->diffInHours($now);
        $giornoAttivita = $ultimo->toDateString();
        $oggi           = $now->copy()->startOfDay()->toDateString();

        return $orePassate >= self::SOGLIA_ABBANDONO_ORE && $giornoAttivita !== $oggi;
    }

    /**
     * Indica se l'ultima attività è "recente" (< SOGLIA_ATTIVITA_RECENTE_MIN
     * minuti fa). Quando true, NON si deve auto-terminare/ripristinare.
     */
    public function attivitaRecente(?DateTimeInterface $ultima, ?DateTimeInterface $now = null): bool
    {
        if ($ultima === null) {
            return false;
        }
        $now = $now !== null ? Carbon::instance($now) : Carbon::now();
        return Carbon::instance($ultima)->diffInMinutes($now) < self::SOGLIA_ATTIVITA_RECENTE_MIN;
    }

    /**
     * Filtra i worksteps di stampa convenzionale per cui la stampa è
     * effettivamente confermata (delega a PrinectJobsService::stampaConfermata).
     *
     * Critico per il fix 66811: senza questa verifica si terminerebbero
     * fasi mai realmente stampate.
     *
     * @param  Collection<int,array<string,mixed>>  $worksteps
     */
    public function filtraConfermati(string $jobId, Collection $worksteps): Collection
    {
        return $worksteps->filter(fn(array $ws) => $this->jobs->stampaConfermata($jobId, $ws))
            ->values();
    }

    /**
     * Helper: data di fine "ultima" tra i worksteps (max actualEndDate).
     *
     * @param  Collection<int,array<string,mixed>>  $worksteps
     */
    public function ultimaDataFine(Collection $worksteps): ?Carbon
    {
        $last = $worksteps
            ->map(fn(array $ws) => $ws['actualEndDate'] ?? null)
            ->filter()
            ->sort()
            ->last();

        if (!$last) {
            return null;
        }

        try {
            return Carbon::parse($last);
        } catch (\Throwable $e) {
            Log::warning('Prinect sync: ultimaDataFine parse error', ['raw' => $last, 'err' => $e->getMessage()]);
            return null;
        }
    }
}
