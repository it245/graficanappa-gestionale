<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Rules;

use App\Models\DdtSpedizione;
use App\Modules\Spedizione\Enums\StatoSpedizione;
use Carbon\Carbon;

/**
 * Regola di ritardo per un DDT di spedizione.
 *
 * Soglia di default: 48h dalla data DDT senza eventi di consegna.
 * Configurabile via config('spedizione.ritardo_soglia_ore', 48).
 */
final class RitardoRule
{
    private int $sogliaOre;
    private MultiSpedizioneRule $multiRule;

    public function __construct(?MultiSpedizioneRule $multiRule = null, ?int $sogliaOre = null)
    {
        $this->multiRule = $multiRule ?? new MultiSpedizioneRule();
        $this->sogliaOre = $sogliaOre ?? (int) config('spedizione.ritardo_soglia_ore', 48);
    }

    /**
     * True se il DDT è in ritardo: non consegnato e oltre soglia ore.
     */
    public function inRitardo(DdtSpedizione $ddt, ?Carbon $oggi = null): bool
    {
        $oggi ??= Carbon::now();

        $stato = StatoSpedizione::daDescrizioneBrt($ddt->brt_stato ?? null);
        if ($stato->eFinale()) {
            return false;
        }

        return $this->giorniRitardo($ddt, $oggi) > 0
            && $this->oreDallaPartenza($ddt, $oggi) >= $this->sogliaOre;
    }

    /**
     * Numero di giorni di ritardo: 0 se non ancora oltre la soglia.
     */
    public function giorniRitardo(DdtSpedizione $ddt, ?Carbon $oggi = null): int
    {
        $oggi ??= Carbon::now();
        $ore = $this->oreDallaPartenza($ddt, $oggi);

        if ($ore < $this->sogliaOre) {
            return 0;
        }

        return (int) floor(($ore - $this->sogliaOre) / 24) + 1;
    }

    /**
     * True se il ritardo va notificato:
     *  - è effettivamente in ritardo
     *  - NON è un DDT multi-spedizione (esito BRT -22).
     */
    public function dovrebbeNotificare(DdtSpedizione $ddt, ?Carbon $oggi = null): bool
    {
        if ($this->multiRule->escludiDaNotifica($ddt)) {
            return false;
        }

        return $this->inRitardo($ddt, $oggi);
    }

    private function oreDallaPartenza(DdtSpedizione $ddt, Carbon $oggi): float
    {
        if (!$ddt->data_ddt) {
            return 0.0;
        }

        $partenza = $ddt->data_ddt instanceof Carbon
            ? $ddt->data_ddt
            : Carbon::parse((string) $ddt->data_ddt);

        return abs($partenza->diffInHours($oggi, false));
    }
}
