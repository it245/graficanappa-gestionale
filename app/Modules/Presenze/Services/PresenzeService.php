<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Services;

use App\Modules\Presenze\Contracts\TimbratureSourceInterface;
use App\Modules\Presenze\Enums\StatoPresenza;
use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use App\Modules\Presenze\ValueObjects\OreLavorate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servizio dominio Presenze.
 *
 * Espone le query "alto livello" usate da PresenzeController +
 * dashboard owner (`/owner/presenti`). Sostituisce le query inline
 * legacy mantenendo la stessa shape JSON dei payload (per non rompere
 * il frontend).
 */
final class PresenzeService
{
    public function __construct(
        private readonly TimbratureSourceInterface $source,
        private readonly CalcoloOreService $oreService,
    ) {}

    /**
     * Stato dipendenti per la dashboard del giorno.
     *
     * Replica la struttura attesa da `resources/views/owner/presenze.blade.php`:
     *  - matricola, nome, prima_entrata, ultima_uscita, presente,
     *    intervalli, ore_lavorate (minuti), minuti_pausa
     */
    public function statoDelGiorno(CarbonInterface $giorno): array
    {
        $anagrafica = DB::table('nettime_anagrafica')
            ->orderBy('cognome')
            ->get()
            ->keyBy('matricola');

        $periodi = $this->source->timbratureDelGiorno($giorno);
        $perMatricola = $periodi->groupBy(fn ($p) => (string) $p->badge);

        $now = Carbon::now();
        $isOggi = $giorno->copy()->isToday();
        $output = [];

        foreach ($perMatricola as $matricola => $listaPeriodi) {
            $anag = $anagrafica->get($matricola);
            $nome = $anag ? "{$anag->cognome} {$anag->nome}" : "Matricola {$matricola}";

            $intervalli = [];
            $oreMin = 0;
            $pausaMin = 0;
            $primaEntrata = null;
            $ultimaUscita = null;

            $listaPeriodi = $listaPeriodi->sortBy(fn ($p) => $p->ingresso->getTimestamp())->values();
            foreach ($listaPeriodi as $i => $p) {
                $da = $p->ingresso->format('H:i');
                $a  = $p->uscita?->format('H:i') ?? ($isOggi ? 'in corso' : '-');
                $minuti = (int) $p->durata($isOggi ? $now : null)->minutiTotali;

                $intervalli[] = ['da' => $da, 'a' => $a, 'minuti' => $minuti, 'tipo' => 'lavoro'];
                $oreMin += $minuti;

                if ($primaEntrata === null) {
                    $primaEntrata = $p->ingresso->isSameDay($giorno)
                        ? $p->ingresso->format('H:i')
                        : $p->ingresso->format('H:i') . ' (ieri)';
                }
                if ($p->uscita !== null) {
                    $ultimaUscita = $p->uscita->format('H:i');
                }

                $next = $listaPeriodi[$i + 1] ?? null;
                if ($next !== null && $p->uscita !== null) {
                    $minPausa = (int) abs($p->uscita->diffInMinutes($next->ingresso));
                    $intervalli[] = [
                        'da' => $p->uscita->format('H:i'),
                        'a' => $next->ingresso->format('H:i'),
                        'minuti' => $minPausa,
                        'tipo' => 'pausa',
                    ];
                    $pausaMin += $minPausa;
                }
            }

            $ultimoPeriodo = $listaPeriodi->last();
            $presente = $ultimoPeriodo !== null && $ultimoPeriodo->isAperto();

            $output[] = [
                'matricola' => $matricola,
                'nome' => $nome,
                'prima_entrata' => $primaEntrata,
                'ultima_uscita' => $ultimaUscita,
                'presente' => $presente,
                'intervalli' => $intervalli,
                'ore_lavorate' => $oreMin,
                'minuti_pausa' => $pausaMin,
            ];
        }

        usort($output, fn ($a, $b) => strcmp($a['nome'], $b['nome']));
        return $output;
    }

    /**
     * Stato istantaneo di un singolo operatore (per chip nella nav,
     * ecc.).
     */
    public function stato(BadgeOperatore $badge, ?CarbonInterface $a = null): StatoPresenza
    {
        $a = $a ?? Carbon::now();
        $periodi = $this->source->timbratureDelGiorno($a)
            ->filter(fn ($p) => $p->badge->equals($badge))
            ->sortBy(fn ($p) => $p->ingresso->getTimestamp())
            ->values();

        if ($periodi->isEmpty()) {
            return StatoPresenza::Assente;
        }

        $ultimo = $periodi->last();
        if ($ultimo->isAperto()) {
            return StatoPresenza::Presente;
        }

        // Ultimo periodo chiuso: se ne aspettiamo un rientro entro
        // mezz'ora, è "in pausa", altrimenti uscito.
        $minutiDaUscita = (int) abs($ultimo->uscita->diffInMinutes($a));
        return $minutiDaUscita <= 30 ? StatoPresenza::InPausa : StatoPresenza::Uscito;
    }

    /**
     * Riepilogo ore (giorno/settimana/mese) per cruscotto operatore.
     *
     * @return array{giorno: OreLavorate, settimana: OreLavorate, mese: OreLavorate}
     */
    public function riepilogoOre(BadgeOperatore $badge, ?CarbonInterface $a = null): array
    {
        $a = $a ?? Carbon::now();
        return [
            'giorno'    => $this->oreService->oreGiornaliere($badge, $a),
            'settimana' => $this->oreService->oreSettimanali($badge, $a),
            'mese'      => $this->oreService->oreMensili($badge, $a),
        ];
    }

    /**
     * Giorni con almeno una timbratura, ordinati DESC.
     * Usato dal selettore date in dashboard.
     *
     * @return array<int, string> Y-m-d
     */
    public function giorniDisponibili(int $limite = 14): array
    {
        return DB::table('nettime_timbrature')
            ->select(DB::raw('DATE(data_ora) as giorno'))
            ->groupBy('giorno')
            ->orderByDesc('giorno')
            ->limit($limite)
            ->pluck('giorno')
            ->toArray();
    }

    public function anagrafica(): Collection
    {
        return DB::table('nettime_anagrafica')->orderBy('cognome')->get();
    }
}
