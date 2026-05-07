<?php

namespace App\Http\Controllers;

use App\Modules\Presenze\Services\CalcoloOreService;
use App\Modules\Presenze\Services\PresenzeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PresenzeController — wrapper HTTP che delega ai Service del modulo
 * `App\Modules\Presenze`. Endpoint URL invariati per backward compat.
 *
 * Refactor def2.0: la logica di parsing/pairing timbrature è stata
 * estratta in NetTimeShareAdapter + PresenzeService. Qui restano solo
 * la traduzione request → service e la composizione della view.
 */
class PresenzeController extends Controller
{
    public function __construct(
        private readonly PresenzeService $presenze,
        private readonly CalcoloOreService $oreService,
    ) {
    }

    /**
     * Pagina presenze completa: anagrafica, oggi, storico
     */
    public function index(Request $request)
    {
        $data = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataCarbon = Carbon::parse($data);

        $dipendenti = $this->presenze->statoDelGiorno($dataCarbon);
        $anagrafica = $this->presenze->anagrafica();
        $giorniDisponibili = $this->presenze->giorniDisponibili(14);

        return view('owner.presenze', [
            'data' => $dataCarbon,
            'dipendenti' => $dipendenti,
            'anagrafica' => $anagrafica,
            'giorniDisponibili' => $giorniDisponibili,
            'totalePresenti' => count(array_filter($dipendenti, fn ($d) => $d['presente'])),
            'totaleUsciti' => count(array_filter($dipendenti, fn ($d) => !$d['presente'])),
        ]);
    }

    /**
     * API: chi è presente adesso?
     * Restituisce lista dipendenti con ultima timbratura di oggi.
     *
     * Cache di 10s preservata (riduce coda polling concorrenti).
     */
    public function presenti()
    {
        if (session()->isStarted()) {
            session()->save();
        }
        $payload = Cache::remember('owner_presenti_v2', 10, function () {
            return $this->buildPresentiPayload();
        });
        return response()->json($payload);
    }

    private function buildPresentiPayload(): array
    {
        $dipendenti = $this->presenze->statoDelGiorno(Carbon::today());

        $presenti = [];
        $usciti = [];
        foreach ($dipendenti as $d) {
            $entry = [
                'matricola' => $d['matricola'],
                'nome' => $d['nome'],
                'ultima_timbratura' => $d['presente']
                    ? ($d['intervalli'][count($d['intervalli']) - 1]['da'] ?? '-')
                    : ($d['ultima_uscita'] ?? '-'),
                'verso' => $d['presente'] ? 'E' : 'U',
                'entrata' => $d['prima_entrata'] ?? '-',
            ];
            if ($d['presente']) {
                $presenti[] = $entry;
            } else {
                $usciti[] = $entry;
            }
        }

        usort($presenti, fn ($a, $b) => strcmp($a['nome'], $b['nome']));
        usort($usciti, fn ($a, $b) => strcmp($a['nome'], $b['nome']));

        return [
            'presenti' => $presenti,
            'usciti' => $usciti,
            'totale_presenti' => count($presenti),
            'totale_usciti' => count($usciti),
            'ultimo_sync' => now()->format('H:i:s'),
        ];
    }

    /**
     * API: alert ritardi — chi doveva arrivare ma non ha timbrato entro 15 min.
     *
     * Logica turni preservata dal codice legacy (config('turni.*') + tabella
     * `turni`). Il match cognome→matricola è stato lasciato locale perché
     * i turni sono ancora identificati per "cognome_nome" (legacy schema).
     */
    public function alertRitardi()
    {
        $oggi = Carbon::today()->format('Y-m-d');
        $ora = Carbon::now();
        $tolleranza = config('turni.tolleranza_minuti', 15);
        $orari = config('turni.orari', []);

        $turniOggi = DB::table('turni')
            ->where('data', $oggi)
            ->whereNotIn('turno', ['F', 'R'])
            ->get();

        if ($turniOggi->isEmpty()) {
            return response()->json(['ritardi' => [], 'messaggio' => 'Nessun turno programmato oggi']);
        }

        $timbrature = DB::table('nettime_timbrature')
            ->whereDate('data_ora', $oggi)
            ->where('verso', 'E')
            ->get()
            ->groupBy('matricola');

        $anagrafica = $this->presenze->anagrafica();

        $ritardi = [];
        foreach ($turniOggi as $turno) {
            $oraInizio = $turno->ora_inizio;
            if (!$oraInizio && isset($orari[$turno->turno])) {
                $oraInizio = $orari[$turno->turno]['inizio'];
            }
            if (!$oraInizio) {
                continue;
            }

            $inizioTurno = Carbon::parse($oggi . ' ' . $oraInizio);
            $scadenza = $inizioTurno->copy()->addMinutes($tolleranza);
            if ($ora->lt($scadenza)) {
                continue;
            }

            $matricola = $this->trovaMatricola($turno->cognome_nome, $anagrafica);
            if (!$matricola) {
                continue;
            }

            $entrate = $timbrature->get($matricola, collect());
            if ($entrate->isEmpty()) {
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

        usort($ritardi, fn ($a, $b) => $b['minuti_ritardo'] <=> $a['minuti_ritardo']);

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

        foreach ($anagrafica as $a) {
            if (strtolower(trim($a->cognome)) === $cognome && strtolower(trim($a->nome)) === $nome) {
                return $a->matricola;
            }
        }
        foreach ($anagrafica as $a) {
            if (strtolower(trim($a->cognome)) === $cognome) {
                return $a->matricola;
            }
        }
        return null;
    }
}
