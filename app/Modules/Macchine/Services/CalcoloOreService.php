<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Services;

use App\Modules\Macchine\Models\MacchinaConfig;
use Carbon\Carbon;

/**
 * Servizio di calcolo ore lavoro / slot disponibili sulla base della
 * configurazione macchina.
 *
 * Logica volutamente "stateless" e priva di Eloquent: riceve un DTO
 * MacchinaConfig e ritorna numeri / Carbon, in modo da poter essere
 * usato sia dallo scheduler Mossa 37 che da preventivi/simulazioni.
 */
final class CalcoloOreService
{
    /**
     * Calcola le ore di lavoro stimate per produrre $copie fogli.
     *
     * Formula: avviamento + (copie / capacita_oraria).
     *
     * @param  MacchinaConfig  $m  Configurazione macchina
     * @param  int  $copie  Numero fogli da produrre
     * @param  int  $avviamentoMin  Minuti di avviamento (default 30)
     * @return float Ore totali stimate (avviamento + esecuzione)
     */
    public function calcolaOreLavoro(
        MacchinaConfig $m,
        int $copie,
        int $avviamentoMin = 30,
    ): float {
        if ($copie < 0) {
            $copie = 0;
        }

        if ($m->capacitaOraria <= 0) {
            // Difensivo: evita divisioni per zero su config malformate.
            return $avviamentoMin / 60.0;
        }

        $oreAvviamento = $avviamentoMin / 60.0;
        $oreEsecuzione = $copie / $m->capacitaOraria;

        return round($oreAvviamento + $oreEsecuzione, 2);
    }

    /**
     * Trova il prossimo slot temporale in cui la macchina e operativa,
     * a partire dal momento $da.
     *
     * Regole:
     *  - Se $da e dentro l'orario lavorativo del giorno, ritorna $da.
     *  - Altrimenti avanza al prossimo orarioInizio del giorno utile
     *    (skippa domenica, eventualmente sabato se !lavoraSabato).
     *
     * @param  MacchinaConfig  $m  Configurazione macchina
     * @param  Carbon  $da  Istante di partenza della ricerca
     * @return Carbon Prossimo istante in cui la macchina e disponibile
     */
    public function prossimoSlotDisponibile(MacchinaConfig $m, Carbon $da): Carbon
    {
        // Lavoriamo su una copia per non mutare l'argomento del chiamante.
        $cursor = $da->copy();

        // Caso 24h lun-ven: basta saltare a lunedi 00:00 se siamo nel weekend.
        $is24h = ($m->orarioInizio === 0 && $m->orarioFine === 24);

        // Massimo 14 iterazioni (2 settimane) come safety net.
        for ($i = 0; $i < 14; $i++) {
            $dow = (int) $cursor->dayOfWeekIso; // 1=lun ... 7=dom

            $isFeriale = $dow >= 1 && $dow <= 5;
            $isSabato = $dow === 6;
            $isDomenica = $dow === 7;

            // Domenica: salta sempre a lunedi 00:00 (poi rivaluta orario).
            if ($isDomenica) {
                $cursor->addDay()->startOfDay();
                continue;
            }

            // Sabato non lavorato: salta a lunedi.
            if ($isSabato && ! $m->lavoraSabato) {
                $cursor->addDays(2)->startOfDay();
                continue;
            }

            // Determina finestra oraria del giorno corrente.
            if ($isSabato && $m->lavoraSabato) {
                $startH = $m->orarioInizio;
                $endH = $m->orarioInizio + (int) $m->oreSabato;
            } elseif ($isFeriale) {
                $startH = $is24h ? 0 : $m->orarioInizio;
                $endH = $is24h ? 24 : $m->orarioFine;
            } else {
                // Difensivo: caso non previsto, avanza di un giorno.
                $cursor->addDay()->startOfDay();
                continue;
            }

            $oggiStart = $cursor->copy()->setTime($startH, 0, 0);
            // endH=24 e gestito come fine giornata
            $oggiEnd = $endH >= 24
                ? $cursor->copy()->endOfDay()
                : $cursor->copy()->setTime($endH, 0, 0);

            // Dentro la finestra: ok, ritorna ora corrente.
            if ($cursor->betweenIncluded($oggiStart, $oggiEnd)) {
                return $cursor;
            }

            // Prima dell'apertura: salta all'apertura odierna.
            if ($cursor->lt($oggiStart)) {
                return $oggiStart;
            }

            // Dopo la chiusura: passa al giorno successivo, ore 00:00.
            $cursor->addDay()->startOfDay();
        }

        // Fallback (non dovrebbe mai accadere): ritorna $da invariato.
        return $da->copy();
    }
}
