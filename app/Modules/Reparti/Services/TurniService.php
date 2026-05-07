<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Services;

use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\ValueObjects\OrarioTurno;
use Carbon\CarbonInterface;

/**
 * Service di dominio: gestione turni per reparto e per data.
 *
 * Logica Grafica Nappa:
 *  - lun-ven: turni standard (vedi sotto per reparto)
 *  - sabato: solo eventuale turno mattina (straordinario, opzionale)
 *  - domenica + festivi: nessun turno
 *
 * Festivi italiani gestiti staticamente (non via API esterna). La lista
 * va aggiornata annualmente o estratta da una tabella `festivi` se
 * verrà introdotta in futuro.
 */
final class TurniService
{
    /**
     * Lista festivi italiani fissi + Pasquetta variabile.
     * Formato: 'm-d'.
     *
     * @return list<string>
     */
    private const FESTIVI_FISSI = [
        '01-01', // Capodanno
        '01-06', // Epifania
        '04-25', // Liberazione
        '05-01', // Festa dei Lavoratori
        '06-02', // Festa della Repubblica
        '08-15', // Ferragosto
        '11-01', // Ognissanti
        '12-08', // Immacolata
        '12-25', // Natale
        '12-26', // Santo Stefano
    ];

    /**
     * Turni applicabili al reparto in un dato giorno.
     *
     * Reparti H24 (XL106 offset, JOH caldo): mattina + pomeriggio + notte feriali.
     * Reparti diurni (resto): mattina + pomeriggio feriali.
     * Sabato: solo mattina (straordinario opzionale, restituito dal service).
     * Domenica/festivi: array vuoto.
     *
     * @return list<OrarioTurno>
     */
    public function turniPerReparto(CodiceReparto $reparto, CarbonInterface $giorno): array
    {
        if ($this->isFestivo($giorno) || $this->isDomenica($giorno)) {
            return [];
        }

        if ($this->isSabato($giorno)) {
            // Sabato: una mattina straordinaria, valida per qualsiasi reparto attivo
            return [OrarioTurno::mattina()];
        }

        // Lun-Ven
        return match ($reparto) {
            CodiceReparto::STAMPA_OFFSET => [
                OrarioTurno::mattina(),
                OrarioTurno::pomeriggio(),
                OrarioTurno::notte(),
            ],
            CodiceReparto::STAMPA_A_CALDO => [
                OrarioTurno::mattina(),
                OrarioTurno::pomeriggio(),
            ],
            CodiceReparto::PRODUZIONE,
            CodiceReparto::MAGAZZINO,
            CodiceReparto::ESTERNO => [
                OrarioTurno::mattina(), // 8h diurne
            ],
            default => [
                OrarioTurno::mattina(),
                OrarioTurno::pomeriggio(),
            ],
        };
    }

    /**
     * Ore lavorative totali per reparto in una data (somma durate turni).
     */
    public function oreLavorativeGiorno(CodiceReparto $reparto, CarbonInterface $giorno): int
    {
        $totale = 0;
        foreach ($this->turniPerReparto($reparto, $giorno) as $turno) {
            $totale += $turno->durataOre();
        }
        return $totale;
    }

    public function isFestivo(CarbonInterface $giorno): bool
    {
        $md = $giorno->format('m-d');
        if (in_array($md, self::FESTIVI_FISSI, true)) {
            return true;
        }
        // Pasquetta = lunedì dopo Pasqua
        $pasqua = self::pasquaPerAnno((int) $giorno->format('Y'));
        $pasquetta = $pasqua + 86400;
        return (int) $giorno->copy()->startOfDay()->getTimestamp() === $pasquetta;
    }

    public function isSabato(CarbonInterface $giorno): bool
    {
        return (int) $giorno->dayOfWeek === CarbonInterface::SATURDAY;
    }

    public function isDomenica(CarbonInterface $giorno): bool
    {
        return (int) $giorno->dayOfWeek === CarbonInterface::SUNDAY;
    }

    /**
     * Algoritmo Gauss per Pasqua, restituisce timestamp UTC mezzanotte.
     */
    private static function pasquaPerAnno(int $anno): int
    {
        // easter_date() richiede l'estensione calendar; usiamo formula manuale
        $a = $anno % 19;
        $b = intdiv($anno, 100);
        $c = $anno % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mese = intdiv($h + $l - 7 * $m + 114, 31);
        $giorno = (($h + $l - 7 * $m + 114) % 31) + 1;
        return (int) mktime(0, 0, 0, $mese, $giorno, $anno);
    }
}
