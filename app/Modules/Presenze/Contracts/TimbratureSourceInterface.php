<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Contracts;

use App\Modules\Presenze\ValueObjects\PeriodoPresenza;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Sorgente astratta delle timbrature.
 *
 * Implementazioni:
 *  - NetTimeShareAdapter: legge file BKP da share \\.34\NetTime (primario)
 *    o \\.253\timbrature (fallback)
 *  - ManualeAdapter: input UI (utile come fallback se share offline)
 *  - in futuro: ApiAdapter (REST NetTime se mai esposto)
 *
 * NB: l'interfaccia ritorna **PeriodoPresenza già accoppiati** (E+U),
 * non timbrature grezze. Il pairing E↔U è responsabilità della
 * sorgente perché solo lei conosce i corner case (turno notturno,
 * doppio badge entro 2 minuti, ecc.). I Service consumano periodi
 * già "puliti".
 */
interface TimbratureSourceInterface
{
    /**
     * Restituisce i periodi di presenza coperti dal giorno indicato,
     * inclusi quelli iniziati il giorno prima (turni notturni).
     *
     * @return Collection<int, PeriodoPresenza>
     */
    public function timbratureDelGiorno(CarbonInterface $giorno): Collection;

    /**
     * Ultima timbratura registrata per un badge (a prescindere dal giorno).
     * Usata per stato istantaneo "presente / uscito".
     */
    public function ultimaTimbratura(string $badge): ?CarbonInterface;

    /**
     * Identificatore della sorgente (per log + dashboard).
     */
    public function sourceId(): string;

    /**
     * true se la sorgente è raggiungibile in questo momento
     * (es. share di rete montata correttamente).
     */
    public function isAvailable(): bool;
}
