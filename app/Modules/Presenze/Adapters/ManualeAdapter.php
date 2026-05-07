<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Adapters;

use App\Modules\Presenze\Contracts\TimbratureSourceInterface;
use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use App\Modules\Presenze\ValueObjects\PeriodoPresenza;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Sorgente fallback: timbrature inserite a mano dall'admin via UI.
 *
 * Use case:
 *  - share NetTime offline (es. PC .34 spento)
 *  - operatore senza badge fisico (nuovo assunto)
 *  - correzione post-hoc di timbrature dimenticate
 *
 * Storage: la stessa tabella `nettime_timbrature` con `terminale =
 * 'MANUALE'` per distinguere — schema NON cambia (vincolo).
 *
 * NB: per ora l'adapter è in-memory (i record sono passati al
 * costruttore). Il merge con DB avverrà in TimbratureSyncService.
 */
final class ManualeAdapter implements TimbratureSourceInterface
{
    /** @param Collection<int, PeriodoPresenza> $periodi */
    public function __construct(
        private readonly Collection $periodi = new Collection(),
    ) {}

    public function sourceId(): string
    {
        return 'manuale';
    }

    public function isAvailable(): bool
    {
        return true; // sempre disponibile
    }

    public function timbratureDelGiorno(CarbonInterface $giorno): Collection
    {
        return $this->periodi->filter(
            fn (PeriodoPresenza $p) => $p->intersecaGiorno($giorno)
        )->values();
    }

    public function ultimaTimbratura(string $badge): ?CarbonInterface
    {
        $b = new BadgeOperatore($badge);
        $latest = null;
        foreach ($this->periodi as $p) {
            if (!$p->badge->equals($b)) {
                continue;
            }
            $candidato = $p->uscita ?? $p->ingresso;
            if ($latest === null || $candidato->gt($latest)) {
                $latest = $candidato;
            }
        }
        return $latest;
    }

    /**
     * Costruisce un adapter da una sequenza di array (ingresso, uscita, badge).
     * Utile per test e seeding manuale.
     */
    public static function da(array $rows): self
    {
        $periodi = collect();
        foreach ($rows as $r) {
            $periodi->push(new PeriodoPresenza(
                badge: new BadgeOperatore((string) $r['badge']),
                ingresso: Carbon::parse($r['ingresso']),
                uscita: isset($r['uscita']) ? Carbon::parse($r['uscita']) : null,
            ));
        }
        return new self($periodi);
    }
}
