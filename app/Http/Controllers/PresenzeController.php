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

        // Ultima timbratura di oggi per ogni matricola
        $ultime = DB::table('nettime_timbrature')
            ->select('matricola', DB::raw('MAX(data_ora) as ultima_timbratura'))
            ->whereDate('data_ora', $oggi)
            ->groupBy('matricola')
            ->get();

        $presenti = [];
        $usciti = [];

        foreach ($ultime as $u) {
            // Prendi il verso dell'ultima timbratura
            $ultima = DB::table('nettime_timbrature')
                ->where('matricola', $u->matricola)
                ->where('data_ora', $u->ultima_timbratura)
                ->first();

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

            // Prima timbratura di oggi (entrata)
            $prima = DB::table('nettime_timbrature')
                ->where('matricola', $u->matricola)
                ->whereDate('data_ora', $oggi)
                ->where('verso', 'E')
                ->orderBy('data_ora')
                ->first();

            if ($prima) {
                $entry['entrata'] = Carbon::parse($prima->data_ora)->format('H:i');
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
}
