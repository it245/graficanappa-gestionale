<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Contracts;

use App\Modules\Reportistica\ValueObjects\PeriodoReport;
use Illuminate\Support\Collection;

/**
 * Astrazione fonte dati per i report.
 *
 * Permette di sostituire la sorgente "live" (DB MySQL .60) con una sorgente
 * cached (Redis) o pre-aggregata (vista materializzata, future feature)
 * senza toccare i Service. Strangler Fig: il default implementator usa
 * Eloquent + DB direttamente, esattamente come faceva il controller.
 */
interface DataSourceInterface
{
    /**
     * Fasi terminate (stato >=3, !=5) nel periodo dato.
     *
     * @return Collection<int, object>
     */
    public function fasiTerminate(PeriodoReport $periodo): Collection;

    /**
     * Ore lavorate aggregate (in secondi) nel periodo, raggruppate per chiave.
     *
     * @return array<string,int>  chiave => secondi
     */
    public function oreLavoratePerChiave(PeriodoReport $periodo, string $chiave = 'commessa'): array;

    /**
     * Numero di commesse "attive" (almeno 1 fase con stato < 3) alla data corrente.
     */
    public function commesseAttive(): int;
}
