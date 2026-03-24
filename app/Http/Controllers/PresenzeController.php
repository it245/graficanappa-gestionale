<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PresenzeController extends Controller
{
    /**
     * Pagina presenze completa: anagrafica, oggi, storico
     */
    public function index(Request $request)
    {
        $data = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataCarbon = Carbon::parse($data);

        // Anagrafica completa
        $anagrafica = DB::table('nettime_anagrafica')
            ->orderBy('cognome')
            ->get();

        // Timbrature del giorno selezionato
        $timbrature = DB::table('nettime_timbrature')
            ->whereDate('data_ora', $data)
            ->orderBy('data_ora')
            ->get();

        // Raggruppa timbrature per matricola
        $perDipendente = [];
        foreach ($timbrature as $t) {
            $matr = $t->matricola;
            if (!isset($perDipendente[$matr])) {
                $anag = $anagrafica->firstWhere('matricola', $matr);
                $perDipendente[$matr] = [
                    'matricola' => $matr,
                    'nome' => $anag ? "{$anag->cognome} {$anag->nome}" : "Matricola {$matr}",
                    'timbrature' => [],
                    'prima_entrata' => null,
                    'ultima_uscita' => null,
                    'ore_lavorate' => 0,
                    'presente' => false,
                ];
            }
            $perDipendente[$matr]['timbrature'][] = $t;
        }

        // Calcola ore lavorate e stato per ogni dipendente
        foreach ($perDipendente as $matr => &$dip) {
            $timb = $dip['timbrature'];
            $entrate = array_filter($timb, fn($t) => $t->verso === 'E');
            $uscite = array_filter($timb, fn($t) => $t->verso === 'U');

            if (!empty($entrate)) {
                $prima = min(array_map(fn($t) => $t->data_ora, $entrate));
                $dip['prima_entrata'] = Carbon::parse($prima)->format('H:i');
            }
            if (!empty($uscite)) {
                $ultima = max(array_map(fn($t) => $t->data_ora, $uscite));
                $dip['ultima_uscita'] = Carbon::parse($ultima)->format('H:i');
            }

            // L'ultimo verso determina se è presente
            $ultimaTimb = end($timb);
            $dip['presente'] = $ultimaTimb && $ultimaTimb->verso === 'E';

            // Pulisci timbrature: rimuovi E duplicate consecutive ravvicinate (< 2h = errore)
            // Se distanti (>= 2h) la seconda è rientro pausa → tieni entrambe
            $timbPulite = [];
            for ($i = 0; $i < count($timb); $i++) {
                $curr = $timb[$i];
                $next = $timb[$i + 1] ?? null;
                if ($curr->verso === 'E' && $next && $next->verso === 'E') {
                    $diffMin = Carbon::parse($curr->data_ora)->diffInMinutes(Carbon::parse($next->data_ora));
                    if ($diffMin < 120) {
                        continue; // < 2h: doppio badge, ignora la prima
                    }
                    // >= 2h: rientro pausa, tieni (manca la U)
                }
                $timbPulite[] = $curr;
            }
            $timb = $timbPulite;
            $dip['timbrature'] = $timb;

            // Ricalcola prima entrata/ultima uscita con timbrature pulite
            $entrate = array_filter($timb, fn($t) => $t->verso === 'E');
            $uscite = array_filter($timb, fn($t) => $t->verso === 'U');
            if (!empty($entrate)) {
                $dip['prima_entrata'] = Carbon::parse(min(array_map(fn($t) => $t->data_ora, $entrate)))->format('H:i');
            }
            if (!empty($uscite)) {
                $dip['ultima_uscita'] = Carbon::parse(max(array_map(fn($t) => $t->data_ora, $uscite)))->format('H:i');
            }

            // Calcola intervalli lavoro/pausa
            $intervalli = [];
            $ore = 0;
            $orePausa = 0;
            $entr = null;
            $idx = 0;
            foreach ($timb as $t) {
                if ($t->verso === 'E') {
                    $entr = Carbon::parse($t->data_ora);
                } elseif ($t->verso === 'U' && $entr) {
                    $uscita = Carbon::parse($t->data_ora);
                    $minuti = $entr->diffInMinutes($uscita);
                    $tipo = $idx === 0 ? 'lavoro' : 'lavoro'; // primo intervallo sempre lavoro
                    $intervalli[] = [
                        'da' => $entr->format('H:i'),
                        'a' => $uscita->format('H:i'),
                        'minuti' => $minuti,
                        'tipo' => 'lavoro',
                    ];
                    $ore += $minuti;
                    $entr = null;
                    $idx++;
                }
            }
            // Se ancora dentro (E senza U), conta fino ad adesso (solo se è oggi)
            if ($entr && $dataCarbon->isToday()) {
                $minuti = $entr->diffInMinutes(now());
                $intervalli[] = [
                    'da' => $entr->format('H:i'),
                    'a' => 'in corso',
                    'minuti' => $minuti,
                    'tipo' => 'lavoro',
                ];
                $ore += $minuti;
            }

            // Inserisci pause tra gli intervalli di lavoro
            $intervalliCompleti = [];
            for ($i = 0; $i < count($intervalli); $i++) {
                $intervalliCompleti[] = $intervalli[$i];
                if (isset($intervalli[$i + 1])) {
                    $finePrec = $intervalli[$i]['a'];
                    $inizioSucc = $intervalli[$i + 1]['da'];
                    $minPausa = Carbon::parse($data . ' ' . $finePrec)->diffInMinutes(Carbon::parse($data . ' ' . $inizioSucc));
                    $intervalliCompleti[] = [
                        'da' => $finePrec,
                        'a' => $inizioSucc,
                        'minuti' => $minPausa,
                        'tipo' => 'pausa',
                    ];
                    $orePausa += $minPausa;
                }
            }

            $dip['intervalli'] = $intervalliCompleti;
            $dip['ore_lavorate'] = $ore;
            $dip['minuti_pausa'] = $orePausa;
        }
        unset($dip);

        // Ordina per cognome
        usort($perDipendente, fn($a, $b) => strcmp($a['nome'], $b['nome']));

        // Giorni disponibili (ultimi 7 giorni con timbrature)
        $giorniDisponibili = DB::table('nettime_timbrature')
            ->select(DB::raw('DATE(data_ora) as giorno'))
            ->groupBy('giorno')
            ->orderByDesc('giorno')
            ->limit(14)
            ->pluck('giorno')
            ->toArray();

        return view('owner.presenze', [
            'data' => $dataCarbon,
            'dipendenti' => $perDipendente,
            'anagrafica' => $anagrafica,
            'giorniDisponibili' => $giorniDisponibili,
            'totalePresenti' => count(array_filter($perDipendente, fn($d) => $d['presente'])),
            'totaleUsciti' => count(array_filter($perDipendente, fn($d) => !$d['presente'])),
        ]);
    }

    /**
     * API: chi è presente adesso?
     * Restituisce lista dipendenti con ultima timbratura di oggi.
     */
    public function presenti()
    {
        $oggi = Carbon::today()->format('Y-m-d');
        $ieri = Carbon::yesterday()->format('Y-m-d');

        // Ultima timbratura di oggi per ogni matricola
        $ultime = DB::table('nettime_timbrature')
            ->select('matricola', DB::raw('MAX(data_ora) as ultima_timbratura'))
            ->whereDate('data_ora', $oggi)
            ->groupBy('matricola')
            ->get();

        // Turno notturno: chi ha timbrato ENTRATA ieri dopo le 20:00
        // e ha uscita oggi prima delle 08:00 (o è ancora dentro)
        $notturni = DB::table('nettime_timbrature')
            ->where('verso', 'E')
            ->where('data_ora', '>=', "{$ieri} 20:00:00")
            ->where('data_ora', '<', "{$oggi} 00:00:00")
            ->get();

        // Matricole già presenti oggi
        $matricoleOggi = $ultime->pluck('matricola')->toArray();

        // Aggiungi notturni che non hanno entrata oggi
        foreach ($notturni as $n) {
            if (!in_array($n->matricola, $matricoleOggi)) {
                // Controlla se ha un'uscita oggi
                $uscitaOggi = DB::table('nettime_timbrature')
                    ->where('matricola', $n->matricola)
                    ->whereDate('data_ora', $oggi)
                    ->where('verso', 'U')
                    ->first();

                $ultime->push((object)[
                    'matricola' => $n->matricola,
                    'ultima_timbratura' => $uscitaOggi ? $uscitaOggi->data_ora : $n->data_ora,
                    '_entrata_notturna' => Carbon::parse($n->data_ora)->format('H:i'),
                    '_uscita_notturna' => $uscitaOggi ? Carbon::parse($uscitaOggi->data_ora)->format('H:i') : null,
                ]);
                $matricoleOggi[] = $n->matricola;
            }
        }

        $presenti = [];
        $usciti = [];

        foreach ($ultime as $u) {
            // Prendi il verso dell'ultima timbratura
            $ultima = DB::table('nettime_timbrature')
                ->where('matricola', $u->matricola)
                ->where('data_ora', $u->ultima_timbratura)
                ->first();

            if (!$ultima) continue;

            // Anagrafica
            $anag = DB::table('nettime_anagrafica')
                ->where('matricola', $u->matricola)
                ->first();

            $nome = $anag ? "{$anag->cognome} {$anag->nome}" : "Matricola {$u->matricola}";

            $entry = [
                'matricola' => $u->matricola,
                'nome' => $nome,
                'ultima_timbratura' => Carbon::parse($u->ultima_timbratura)->format('H:i'),
                'verso' => $ultima->verso,
            ];

            // Entrata: turno notturno (ieri sera) o prima entrata oggi
            if (isset($u->_entrata_notturna)) {
                $entry['entrata'] = $u->_entrata_notturna . ' (ieri)';
            } else {
                $prima = DB::table('nettime_timbrature')
                    ->where('matricola', $u->matricola)
                    ->whereDate('data_ora', $oggi)
                    ->where('verso', 'E')
                    ->orderBy('data_ora')
                    ->first();

                if ($prima) {
                    $entry['entrata'] = Carbon::parse($prima->data_ora)->format('H:i');
                }
            }

            if ($ultima->verso === 'E') {
                $presenti[] = $entry;
            } else {
                $usciti[] = $entry;
            }
        }

        // Ordina per cognome
        usort($presenti, fn($a, $b) => strcmp($a['nome'], $b['nome']));
        usort($usciti, fn($a, $b) => strcmp($a['nome'], $b['nome']));

        return response()->json([
            'presenti' => $presenti,
            'usciti' => $usciti,
            'totale_presenti' => count($presenti),
            'totale_usciti' => count($usciti),
            'ultimo_sync' => now()->format('H:i:s'),
        ]);
    }

    /**
     * API: alert ritardi — chi doveva arrivare ma non ha timbrato entro 15 min.
     */
    public function alertRitardi()
    {
        $oggi = Carbon::today()->format('Y-m-d');
        $ora = Carbon::now();
        $tolleranza = config('turni.tolleranza_minuti', 15);
        $orari = config('turni.orari', []);

        // Turni di oggi
        $turniOggi = DB::table('turni')
            ->where('data', $oggi)
            ->whereNotIn('turno', ['F', 'R']) // escludi ferie e riposo
            ->get();

        if ($turniOggi->isEmpty()) {
            return response()->json(['ritardi' => [], 'messaggio' => 'Nessun turno programmato oggi']);
        }

        // Timbrature di oggi (entrate)
        $timbrature = DB::table('nettime_timbrature')
            ->whereDate('data_ora', $oggi)
            ->where('verso', 'E')
            ->get()
            ->groupBy('matricola');

        // Anagrafica per match nome → matricola
        $anagrafica = DB::table('nettime_anagrafica')->get();

        $ritardi = [];

        foreach ($turniOggi as $turno) {
            // Determina orario inizio turno
            $oraInizio = $turno->ora_inizio;
            if (!$oraInizio && isset($orari[$turno->turno])) {
                $oraInizio = $orari[$turno->turno]['inizio'];
            }
            if (!$oraInizio) continue;

            $inizioTurno = Carbon::parse($oggi . ' ' . $oraInizio);
            $scadenza = $inizioTurno->copy()->addMinutes($tolleranza);

            // Il turno deve essere già iniziato E la scadenza passata
            if ($ora->lt($scadenza)) continue;

            // Trova matricola dal cognome_nome
            $matricola = $this->trovaMatricola($turno->cognome_nome, $anagrafica);
            if (!$matricola) continue;

            // Controlla se ha timbrato entrata
            $entrate = $timbrature->get($matricola, collect());
            // Ha timbrato entrata oggi = è presente (anche se ore prima del turno)
            $haEntrata = $entrate->isNotEmpty();

            if (!$haEntrata) {
                $minutiRitardo = (int) abs($ora->diffInMinutes($inizioTurno));
                $ritardi[] = [
                    'nome' => $turno->cognome_nome,
                    'turno' => $turno->turno,
                    'ora_prevista' => $oraInizio,
                    'minuti_ritardo' => $minutiRitardo,
                    'ritardo_label' => $minutiRitardo >= 60
                        ? floor($minutiRitardo / 60) . 'h ' . ($minutiRitardo % 60) . 'm'
                        : $minutiRitardo . ' min',
                ];
            }
        }

        // Ordina per ritardo decrescente
        usort($ritardi, fn($a, $b) => $b['minuti_ritardo'] <=> $a['minuti_ritardo']);

        return response()->json([
            'ritardi' => $ritardi,
            'totale' => count($ritardi),
            'ora_check' => $ora->format('H:i'),
            'tolleranza' => $tolleranza,
        ]);
    }

    /**
     * Match cognome_nome dalla tabella turni → matricola in nettime_anagrafica.
     */
    protected function trovaMatricola(string $cognomeNome, $anagrafica): ?string
    {
        $parti = explode(' ', trim($cognomeNome), 2);
        $cognome = strtolower(trim($parti[0] ?? ''));
        $nome = strtolower(trim($parti[1] ?? ''));

        // Match esatto cognome + nome
        foreach ($anagrafica as $a) {
            if (strtolower(trim($a->cognome)) === $cognome && strtolower(trim($a->nome)) === $nome) {
                return $a->matricola;
            }
        }

        // Fallback: solo cognome
        foreach ($anagrafica as $a) {
            if (strtolower(trim($a->cognome)) === $cognome) {
                return $a->matricola;
            }
        }

        return null;
    }
}
